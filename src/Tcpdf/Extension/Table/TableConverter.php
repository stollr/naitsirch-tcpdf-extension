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
    private $pageBreakCallbackHeight;
    private $compiled;

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

        $pageContentHeight = Helper::getPageContentHeight($pdf, $pdf->getPage());
        
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

                if ($height > $pageContentHeight) {
                    $msg = "The height of the cell's content exceeds the page height. "
                         . "Wrapping of such a cell is currently not supported by "
                         . "tcpdf-extension. Please try to split your text into "
                         . "multiple rows manually. The content of the specific "
                         . "cell is: \"%s\"."
                    ;
                    $content = mb_strlen($cell->getText()) < 250
                        ? $cell->getText()
                        : mb_substr($cell->getText(), 0, 250) . ' [...]'
                    ;
                    throw new \Exception(sprintf($msg, $content));
                }

                if ($cell->getRowspan() > 1) {
                    // save rowspan info for this column
                    $minHeightPerRow = $height / $cell->getRowspan();
                    for ($rs = 0; $rs < $cell->getRowspan(); $rs++) {
                        $rowspanInfos[$r + $rs][$c] = array(
                            'cell'         => $cell,
                            'height_total' => $height, // will be corrected later, if the heights of later rows are known
                            'own_height'   => $height,
                            'rowspan'      => $cell->getRowspan(),
                            'width'        => $cellWidths[$r][$c],
                            'position'     => $rs,
                        );
                        if (!isset($rowHeights[$r + $rs]) || $rowHeights[$r + $rs] < $minHeightPerRow) {
                            $rowHeights[$r + $rs] = $minHeightPerRow;
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
        foreach ($rowspanInfos as $r => $cellIndexes) {
            foreach ($cellIndexes as $c => $rowspanInfo) {
                if (0 === $rowspanInfo['position']) {
                    $height = array_sum(array_slice($rowHeights, $r, $rowspanInfo['rowspan']));
                }
                $rowspanInfos[$r][$c]['height_total'] = $height;
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
     * Splits cells with rowspan, which are larger than the page.
     *
     * @param Row $row
     * @param int $r
     * @param float $remainingPagePlace
     */
    private function _splitRowspanCells($row, $r, $remainingPagePlace)
    {
        $rowspanInfos = $this->_getRowspanInfos();
        $rowHeights = $this->_getRowHeights();

        foreach ($row->getCells() as $c => $cell) {
            if (isset($rowspanInfos[$r][$c])
                && 0 === $rowspanInfos[$r][$c]['position']
                && !isset($rowspanInfos[$r][$c]['splitted'])
            ) {
                $lastR = $r;
                $heightSum = 0;
                for ($r2 = 0; $r2 < $cell->getRowspan(); $r2++) {
                    if ($heightSum + $rowHeights[$r + $r2] > $remainingPagePlace) {
                        $rowspanInfos[$lastR][$c]['height_total'] = $heightSum;
                        $rowspanInfos[$lastR][$c]['splitted'] = true;
                        $rowspanInfos[$r + $r2][$c]['position'] = 0;
                        $rowspanInfos[$r + $r2][$c]['cell'] = clone $cell;

                        $lastR = $r + $r2;
                        $heightSum = 0;
                        $pdf = $row->getTable()->getPdf();
                        $remainingPagePlace = Helper::getPageContentHeight($pdf, $pdf->getPage()) - $this->_getPageBreakCallbackHeight();
                    }
                    $heightSum += $rowHeights[$r + $r2];
                }

                $rowspanInfos[$lastR][$c]['height_total'] = $heightSum;
                $rowspanInfos[$lastR][$c]['splitted'] = true;
            }
        }

        $this->rowspanInfos = $rowspanInfos;
        return $rowspanInfos;
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

    public function compile()
    {
        if (!$this->compiled) {
            $this->_saveFontSettings();

            // this calculates all widths and heights
            $this->_getRowHeights();

            $this->_restoreFontSettings();

            $this->compiled = true;
        }
        return $this;
    }

    public function convert()
    {
        $this->compile();

        // save current styles
        $this->_saveFontSettings();

        $pdf = $this->getPdf();
        $cellWidths = $this->_getCellWidths();
        $rowHeights = $this->_getRowHeights();
        $rowspanInfos = $this->_getRowspanInfos();

        // after all sizes are collected, we can start printing the cells
        $x = $pdf->GetX();
        $rows = $this->getTable()->getRows();
        for ($r = 0; $r < count($rows); $r++) {
            $row = $rows[$r];
            $y2 = $pdf->GetY();
            $x2 = $x;

            // autopagebreak, if row is too high
            $page = $pdf->getPage();
            $remainingPlace = Helper::getRemainingYPageSpace($pdf, $page, $y2);
            if ($rowHeights[$r] >= $remainingPlace) {
                $pdf->AddPage();

                // execute page break callback
                $this->_execPageBreakCallback($r, $cellWidths, $rowHeights, $rowspanInfos, $rows);
                $r--;
                continue;
            }

            // check for and split rowspan cells, which are too large for the page
            // rowspan infos gets updated
            $rowspanInfos = $this->_splitRowspanCells($row, $r, $remainingPlace);

            $c = 0;
            foreach ($row->getCells() as $cell) {
                // calculate the width (regard colspan)
                $width = $cellWidths[$r][$c];

                // TODO: this solution does only work as long as there are not two rowspan cells next to each other
                // get the height with regarded rowspan
                $height = $rowHeights[$r];
                if (isset($rowspanInfos[$r][$c])) {
                    if (0 === $rowspanInfos[$r][$c]['position']) {
                        if ($cell === $rowspanInfos[$r][$c]['cell']) {
                            // this is a regular cell with rowspan > 1
                            // so we overwrite the height
                            $height = $rowspanInfos[$r][$c]['height_total'];
                            
                        } else {
                            // this is a splitted cell, which should be printed, here
                            if ($rowspanInfos[$r][$c]['own_height'] > $height) {
                                $rowspanInfos[$r][$c]['cell']->setText(''); // cell is cloned, so we can change its text
                            }
                            $this->_printCell(
                                $rowspanInfos[$r][$c]['cell'],
                                $page,
                                $x2,
                                $y2,
                                $rowspanInfos[$r][$c]['width'],
                                $rowspanInfos[$r][$c]['height_total']
                            );
                            $x2 += $rowspanInfos[$r][$c]['width'];
                        }
                    } else if (0 < $rowspanInfos[$r][$c]['position']) {
                        // increase the X position, so that we do not overwrite the
                        // cell with rowspan
                        $x2 += $rowspanInfos[$r][$c]['width'];
                    }
                }

                $this->_printCell($cell, $page, $x2, $y2, $width, $height);

                // increase X position for next cell
                $x2 = $x2 + $width;
                $c++;
            }
            $pdf->SetX($x);
        }

        $this->_restoreFontSettings();
    }

    private function _printCell(Cell $cell, $page, $x, $y, $width, $height)
    {
        $pdf = $this->getPdf();
        
        // background image
        $this->_addCellBackgroundImage($cell, $x, $y, $width, $height);

        // If a cell is higher than the remaining space of the page, a page break
        // could happen, but the Y value stays the same, so that the next cell is printed
        // to the bottom of the next page. This is why we have to check
        // and correct the page number, if needed
        if ($pdf->getPage() != $page) {
            $pdf->setPage($page);
        }

        $pdf->SetFont(
            $cell->getFontFamily(),
            $cell->getFontWeight() == Table::FONT_WEIGHT_BOLD ? 'B' : '',
            $cell->getFontSize()
        );
        $padding = $cell->getPadding();
        $pdf->setCellPaddings($padding['L'], $padding['T'], $padding['R'], $padding['B']);

        // Set the background color
        $backgroundColor = Helper::convertColor($cell->getBackgroundColor());
        if ($backgroundColor) {
            $pdf->SetFillColorArray($backgroundColor);
        }

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
            $backgroundColor !== null,
            1,                  // current position should go to the beginning of the next line
            $x,
            $y,
            false,              // line height should NOT be resetted
            false,              // stretch
            false,              // is html
            true,               // autopadding
            $height + 0.001,    // max height: must be slightly greater than $height, otherwise TCPDF fails printing the cell, because of strange bug in getting different heights. maybe rounding problem. But if maxh is not set, valign 'bottom' or 'middle' cannot be determined.
            strtoupper(substr($cell->getVerticalAlign(), 0, 1)), // vertical alignment T, M or B
            $cell->getFitCell()
        );
    }

    private function _execPageBreakCallback($rowIndex, &$cellWidths, &$rowHeights, &$rowspanInfos, &$rows)
    {
        $table = $this->getTable();
        if (!$callback = $table->getPageBreakCallback()) {
            return;
        }

        $table->setRows(array());
        $callback($table);
        $numberOfNewRows = count($table->getRows());

        if ($numberOfNewRows > 0) {
            $converter = new self($table, $this->cacheDir);
            $converter->compile();

            // merge cell width
            array_splice($cellWidths, $rowIndex, 0, $converter->_getCellWidths());
            $this->cellWidths = $cellWidths;

            // merge row heights
            array_splice($rowHeights, $rowIndex, 0, $converter->_getRowHeights());
            $this->rowHeights = $rowHeights;

            // merge rowspan info
            $newRowspanInfos = $converter->_getRowspanInfos();
            $reverseKeys = array_keys($rowspanInfos);
            rsort($reverseKeys, SORT_NUMERIC);
            //array_splice($rowspanInfos, $rowIndex, 0, $converter->_getRowspanInfos());
            foreach ($reverseKeys as $r) {
                if ($r >= $rowIndex && isset($rowspanInfos[$r])) {
                    $rowspanInfos[$r + $numberOfNewRows] = $rowspanInfos[$r];
                    unset($rowspanInfos[$r]);
                }
            }
            $this->rowspanInfos = $rowspanInfos;

            array_splice($rows, $rowIndex, 0, $table->getRows());
        }
        $table->setRows($rows);
    }

    /**
     * Calculates and returns the height of rows, added by the page break callback.
     * @return int
     */
    private function _getPageBreakCallbackHeight()
    {
        if ($this->pageBreakCallbackHeight !== null) {
            return $this->pageBreakCallbackHeight;
        }

        $table = $this->getTable();
        if (!$callback = $table->getPageBreakCallback()) {
            return $this->pageBreakCallbackHeight = 0;
        }

        $table->setRows(array());
        $callback($table);
        $numberOfNewRows = count($table->getRows());

        $this->pageBreakCallbackHeight = 0;
        if ($numberOfNewRows > 0) {
            $converter = new self($table, $this->cacheDir);
            $converter->compile();

            // merge row heights
            foreach ($converter->_getRowHeights() as $height) {
                $this->pageBreakCallbackHeight += $height;
            }
        }
        return $this->pageBreakCallbackHeight;
    }
}
