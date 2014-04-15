<?php

namespace Tcpdf\Extension\Table;

use Tcpdf\Extension\Helper;
use Tcpdf\Extension\Attribute\BackgroundFormatterOptions;

/**
 * Tcpdf\Extension\Table\TableConverter
 *
 * @author naitsirch
 */
class TableConverter
{
    private $cacheDir;
    private $table;
    private $fontSettings;
    private $rowHeights;
    private $cellWidths;
    private $rowspanInfos;

    /**
     * Converts a table and puts it on the PDF.
     *
     * @param \Tcpdf\Extension\Table\Table $table
     * @param string $cacheDir If the cache directory is given, resized images could be cached.
     */
    public function __construct(Table $table, $cacheDir = null)
    {
        $this->table = $table;
        $this->cacheDir = $cacheDir;
        $this->convert();
    }

    private function _addCellBackgroundImage(Cell $cell, $x = 0, $y = 0, $width = 0, $height = 0)
    {
        $backgroundImage = $cell->getBackgroundImage();
        $formatter = $cell->getBackground()->getFormatter();
        if (!$backgroundImage && !$formatter) {
            return;
        }

        // execute background formatter
        $dpi = $cell->getBackground()->getDpi() ?: 72;
        if ($formatter = $cell->getBackground()->getFormatter()) {
            $options = new BackgroundFormatterOptions($backgroundImage, $width, $height);
            $formatter($options);
            $backgroundImage = $options->getImage();
            $width = $options->getWidth();
            $height = $options->getHeight();
//            if ($options->getDpi()) {
//                $dpi = $options->getDpi();
//            }
        }

        if (!$backgroundImage) {
            return;
        }

        if (strlen($backgroundImage) > 1024 || !file_exists($backgroundImage)) {
            $imageInfo = getimagesizefromstring($backgroundImage);
            $imageOriginal = imagecreatefromstring($backgroundImage);
        } else {
            $imageInfo = getimagesize($backgroundImage);
            switch ($imageInfo[2]) {
                case IMAGETYPE_GIF:
                    $imageOriginal = imagecreatefromgif($backgroundImage);
                    break;
                case IMAGETYPE_JPEG:
                    $imageOriginal = imagecreatefromjpeg($backgroundImage);
                    break;
                case IMAGETYPE_PNG:
                    $imageOriginal = imagecreatefrompng($backgroundImage);
                    break;
            }
        }

        // interpret border settings
        $border = $cell->getBorder();
        if ($border === 1 || $border === '1') {
            $border = 'TRBL';
        }
        if (false !== strpos($border, 'T')) {
            $y = $y + ($cell->getBorderWidth() / 2);
            $height = $height - ($cell->getBorderWidth() / 2);
        }
        if (false !== strpos($border, 'R')) {
            $width = $width - ($cell->getBorderWidth() / 2);
        }
        if (false !== strpos($border, 'B')) {
            $height = $height - ($cell->getBorderWidth() / 2);
        }
        if (false !== strpos($border, 'L')) {
            $x = $x + ($cell->getBorderWidth() / 2);
            $width = $width - ($cell->getBorderWidth() / 2);
        }

        // terrible workaround to get the
        $pdf = $cell->getTableRow()->getTable()->getPdf();
        $prop = new \ReflectionProperty('\\TCPDF', 'pdfunit');
        $prop->setAccessible(true);
        $unit = $prop->getValue($pdf);

        $widthPixel = Helper::getSizeInPixel($width, $unit, $dpi);
        $heightPixel = Helper::getSizeInPixel($height, $unit, $dpi);
        $imageBackground = imagecreatetruecolor($widthPixel, $heightPixel);
        imagecopy($imageBackground, $imageOriginal, 0, 0, 0, 0, $widthPixel, $heightPixel);
        ob_start();
        imagejpeg($imageBackground);
        $image = ob_get_clean();
        imagedestroy($imageOriginal);
        imagedestroy($imageBackground);

        $this->getPdf()->Image(
            '@' . $image,
            $x,
            $y,
            $width,
            $height,
            null, // image type, null == auto
            null,
            'N',
            false,
            $dpi
        );
    }

    private function _getRawCellWidths()
    {
        $cellWidthsByUser = array();
        $cellWidthsByStringLength = array();
        $rowspanInfos = array();
        $cellMaxIndex = 0;
        $r = 0;
        foreach ($this->getTable()->getRows() as $row) {
            $c = 0;
            foreach ($row->getCells() as $cell) {
                // save rowspan info
                if ($cell->getRowspan() > 1) {
                    for ($rs = 0; $rs < $cell->getRowspan(); $rs++) {
                        $rowspanInfos[$c][$r + $rs] = array('position' => $rs);
                    }
                }

                // overjump rowspanned columns
                while (isset($rowspanInfos[$c][$r]) && $rowspanInfos[$c][$r]['position'] > 0) {
                    $c++;
                }

                // ignore cells taking more than one column
                if ($cell->getColspan() == 1) {
                    if ($cell->getWidth() && empty($cellWidthsByUser[$c])) {
                        // only the first width is taken into account
                        $cellWidthsByUser[$c] = $cell->getWidth();
                    } else if (empty($cellWidthsByUser[$c])) {
                        $cellWidthsByStringLength[$c][] = $this->getPdf()->GetStringWidth($cell->getText());
                    }
                    $cellMaxIndex = $c > $cellMaxIndex ? $c : $cellMaxIndex;
                }

                $c += $cell->getColspan();
            }
            $r++;
        }

        $cellWidths = array();
        for ($c = 0; $c <= $cellMaxIndex; $c++) {
            if (isset($cellWidthsByUser[$c])) {
                $cellWidths[$c] = $cellWidthsByUser[$c];
            } else if (isset($cellWidthsByStringLength[$c]) && count($cellWidthsByStringLength[$c]) > 0) {
                $cellWidths[$c] = array_sum($cellWidthsByStringLength[$c]) / count($cellWidthsByStringLength[$c]);
            } else {
                $cellWidths[$c] = 0;
            }
        }

        return $cellWidths;
    }
    
    /**
     * Calculates and returns an 2 dimensional array with the width for each
     * table cell of each row.
     * 
     * @return array
     */
    private function _getCellWidths()
    {
        if (isset($this->cellWidths)) {
            return $this->cellWidths;
        }
        
        $cellWidths = $this->_getRawCellWidths();

        // check if the sum of cell widths is valid
        $cellWidthSum = array_sum($cellWidths);

        $margins = $this->getPdf()->getMargins();
        $maxWidth = $this->getPdf()->getPageWidth() - $margins['left'] - $margins['right'];

        $definedWidth = $this->getTable()->getWidth() ?: null;
        if ($cellWidthSum > $maxWidth || $definedWidth) {
//            $cellWordWidths = $this->_getCellWordWidths();
//            $cellWordWidthsSum = array_sum($cellWordWidths);
//            $restWidth = $maxWidth;
            foreach ($cellWidths as $index => $width) {
//                $wordWidth = current($cellWordWidths);
//                next($cellWordWidths);
                if ($definedWidth) {
                    $newWidth = ($width / $cellWidthSum) * $definedWidth;
                } else {
                    $newWidth = ($width / $cellWidthSum) * $maxWidth;
                }
//                if ($cellWordWidthsSum < $maxWidth) {
//                    if ($wordWidth > $newWidth) {
//                        $newWidth = $wordWidth;
//                        $restWidth -= $wordWidth;
//                    }
//                }
                $cellWidths[$index] = $newWidth;
            }
        }

        // set new calculated widths to the cells
        $rowspanInfos = array();
        $r = 0;
        foreach ($this->getTable()->getRows() as $row) {
            $c = $cr = 0; // $cr = real cell index
            foreach ($row->getCells() as $cell) {
                // save rowspan info
                if ($cell->getRowspan() > 1) {
                    for ($rs = 0; $rs < $cell->getRowspan(); $rs++) {
                        $rowspanInfos[$c][$r + $rs] = array('position' => $rs);
                    }
                }

                // overjump rowspanned columns
                while (isset($rowspanInfos[$c][$r]) && $rowspanInfos[$c][$r]['position'] > 0) {
                    $c++;
                }

                // collect widths
                $width = 0;
                for ($i = 0; $i < $cell->getColspan(); $i++) {
                    $width += $cellWidths[$c];
                    $c++;
                }
                $this->cellWidths[$r][$cr] = $width;
                $cr++;
            }
            $r++;
        }
        
        return $this->cellWidths;
    }

    private function _getCellWordWidths()
    {
        $longestWordWidths = array();
        foreach ($this->getTable()->getRows() as $row) {
            $c = 0;
            foreach ($row->getCells() as $cell) {
                if ($cell->getColspan() == 1) {
                    // width of the longest word
                    $maxWidth = 0;
                    foreach (explode(' ', $cell->getText()) as $word) {
                        $width = $this->getPdf()->GetStringWidth($word);
                        if ($width > $maxWidth) {
                            $maxWidth = $width;
                        }
                    }
                    if (empty($longestWordWidths[$c]) || $maxWidth > $longestWordWidths[$c]) {
                        $longestWordWidths[$c] = $maxWidth;
                    }
                }
                $c += $cell->getColspan();
            }
        }
        return $longestWordWidths;
    }

    private function _getRowHeights()
    {
        if (!empty($this->rowHeights)) {
            return $this->rowHeights;
        }
        
        $pdf = $this->getPdf();
        $this->_saveFontSettings();
        
        $cellWidths = $this->_getCellWidths();
        $rowHeights = array();
        $rowspanInfos = array();
        $r = 0;
        foreach ($this->getTable()->getRows() as $row) {
            $c = 0;
            if (!isset($rowHeights[$r])) {
                // this is needed because of rowspan info
                $rowHeights[$r] = 0;
            }
            foreach ($row->getCells() as $cell) {
                // set the font size, so that the height can be determined correctly
                $pdf->SetFont(
                    $cell->getFontFamily(),
                    $cell->getFontWeight() == Table::FONT_WEIGHT_BOLD ? 'B' : '',
                    $cell->getFontSize()
                );

                // get the line height here by myself, otherwise it's not possible
                // to use our own line height
                $padding = $cell->getPadding();
                $lines = $pdf->getNumLines(
                    $cell->getText(),
                    $cellWidths[$r][$c],
                    false,
                    false,
                    array('T' => 0, 'R' => $padding['R'], 'B' => 0, 'L' => $padding['L']),
                    $cell->getBorder()
                );
                $height = $lines * $cell->getLineHeight() * ($cell->getFontSize() / $pdf->getScaleFactor()) * $pdf->getCellHeightRatio();

                // After we have summed up the height of all lines, we have to add
                // top and border padding of the cell to the height
                $height += $padding['T'] + $padding['B'];

                if ($cell->getMinHeight() > $height) {
                    $height = $cell->getMinHeight();
                }

                if ($cell->getRowspan() > 1) {
                    // save rowspan info for this column
                    $rowspanTotalHeight = $height;
                    $height = $height / $cell->getRowspan();
                    for ($rs = 0; $rs < $cell->getRowspan(); $rs++) {
                        $rowspanInfos[$c][$r + $rs] = array(
                            'height_rowspan_total' => $rowspanTotalHeight,
                            'own_height'           => $rowspanTotalHeight,
                            'rowspan'              => $cell->getRowspan(),
                            'width'                => $cellWidths[$r][$c],
                            'position'             => $rs,
                        );
                        if (!isset($rowHeights[$r + $rs]) || $rowHeights[$r + $rs] < $height) {
                            $rowHeights[$r + $rs] = $height;
                        }
                    }
                } else if ($height > $rowHeights[$r]) {
                    $rowHeights[$r] = $height;
                }
                $c++;
            }
            $r++;
        }
        $this->_restoreFontSettings();

        // correct rowspan heights
        foreach ($rowspanInfos as $c => $rowIndexes) {
            foreach ($rowIndexes as $r => $rowspanInfo) {
                if (0 === $rowspanInfo['position']) {
                    $height = array_sum(array_slice($rowHeights, $r, $rowspanInfo['rowspan']));
                }
                $rowspanInfos[$c][$r]['height_rowspan_total'] = $height;
            }
        }

        $this->rowspanInfos = $rowspanInfos;
        return $this->rowHeights = $rowHeights;
    }

    private function _getRowspanInfos()
    {
        $this->_getRowHeights();
        return $this->rowspanInfos;
    }

    private function _saveFontSettings()
    {
        $this->fontSettings = array(
            'family'  => $this->getPdf()->getFontFamily(),
            'style'   => $this->getPdf()->getFontStyle(),
            'size'    => $this->getPdf()->getFontSize(),
            'size_pt' => $this->getPdf()->getFontSizePt(),
            'cell_height_ratio' => $this->getPdf()->getCellHeightRatio(),
            'cell_padding' => $this->getPdf()->getCellPaddings(),
        );
        return $this;
    }

    private function _restoreFontSettings()
    {
        if (!$this->fontSettings) {
            throw new RuntimeException('No settings has been saved, yet.');
        }
        $this->getPdf()->SetFont(
            $this->fontSettings['family'],
            $this->fontSettings['style'],
            $this->fontSettings['size_pt']
        );
        $this->getPdf()->setCellHeightRatio($this->fontSettings['cell_height_ratio']);
        $this->getPdf()->setCellPaddings(
            $this->fontSettings['cell_padding']['L'],
            $this->fontSettings['cell_padding']['T'],
            $this->fontSettings['cell_padding']['R'],
            $this->fontSettings['cell_padding']['B']
        );


        return $this;
    }
    
    /**
     * @return Table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @return \TCPDF
     */
    public function getPdf()
    {
        return $this->getTable()->getPdf();
    }

    private function convert()
    {
        // save current styles
        $this->_saveFontSettings();

        $pdf = $this->getPdf();
        $cellWidths = $this->_getCellWidths();
        $rowHeights = $this->_getRowHeights();
        $rowspanInfos = $this->_getRowspanInfos();

        // after all sizes are collected, we can start printing the cells
        $x = $pdf->GetX();
        $r = 0;
        foreach ($this->getTable()->getRows() as $row) {
            $c = 0;
            $y2 = $pdf->GetY();
            $x2 = $x;
            foreach ($row->getCells() as $cell) {
                // calculate the width (regard colspan)
                $width = $cellWidths[$r][$c];

                // get the height with regarded rowspan
                $height = $rowHeights[$r];
                if (isset($rowspanInfos[$c][$r])) {
                    if (0 === $rowspanInfos[$c][$r]['position'] && $rowspanInfos[$c][$r]['height_rowspan_total'] > $height) {
                        $height = $rowspanInfos[$c][$r]['height_rowspan_total'];
                    } else if (0 < $rowspanInfos[$c][$r]['position']) {
                        // increase the X position, so that we do not overwrite the
                        // cell with rowspan
                        $x2 += $rowspanInfos[$c][$r]['width'];
                    }
                }

                // background image
                $this->_addCellBackgroundImage($cell, $x2, $y2, $width, $height);

                $pdf->SetFont(
                    $cell->getFontFamily(),
                    $cell->getFontWeight() == Table::FONT_WEIGHT_BOLD ? 'B' : '',
                    $cell->getFontSize()
                );
                $padding = $cell->getPadding();
                $pdf->setCellPaddings($padding['L'], $padding['T'], $padding['R'], $padding['B']);

                // set the line height here by myself
                // because TCPDF resets line height (cell padding of lines) 
                // before checking for current line height, so that it calculates the wrong
                // line height in MultiCell
                $pdf->setLastH($pdf->getCellHeight(
                    $cell->getLineHeight() * ($cell->getFontSize() / $pdf->getScaleFactor()),
                    false
                ));

                // write cell to pdf
                $pdf->MultiCell(
                    $width,
                    $height,
                    $cell->getText(),
                    $cell->getBorder(),
                    $cell->getAlign(),
                    $cell->getFill(),
                    1,                  // current position should go to the beginning of the next line
                    $x2,
                    $y2,
                    false,              // line height should NOT be resetted
                    false,              // stretch
                    false,              // is html
                    true,               // autopadding
                    $height,            // max height
                    strtoupper(substr($cell->getVerticalAlign(), 0, 1)), // vertical alignment T, M or B
                    $cell->getFitCell()
                );

                // increase X position for next cell
                $x2 = $x2 + $width;

                $c++;
            }
            $pdf->SetX($x);
            $r++;
        }

        $this->_restoreFontSettings();
    }
}
