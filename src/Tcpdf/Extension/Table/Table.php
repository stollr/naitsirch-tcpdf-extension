<?php

namespace Tcpdf\Extension\Table;

/**
 * Tcpdf\Extension\Table\Table
 *
 * @author naitsirch
 */
class Table
{
    const FONT_WEIGHT_NORMAL = 'normal';
    const FONT_WEIGHT_BOLD = 'bold';

    private $pdf;
    private $pageBreakCallback;
    private $cacheDir;
    private $rows = array();
    private $borderWidth;
    private $lineHeight = 1;
    private $fontFamily;
    private $fontSize;
    private $fontWeight;
    private $width;
    private $widthPercentage;
    private $xPosition;

    /**
     * Create a table structure for TCPDF.
     *
     * @param \TCPDF $pdf
     * @param string $cacheDir If the cache directory is given, resized images could be cached.
     */
    public function __construct(\TCPDF $pdf, $cacheDir = null)
    {
        $this->pdf = $pdf;
        $this->cacheDir = $cacheDir;
        $this->xPosition = $pdf->GetX();
        $this->setBorderWidth($pdf->GetLineWidth());
        $this->setFontFamily($pdf->getFontFamily());
        $this->setFontSize($pdf->getFontSizePt()); // FontSizePT is in points (not in user unit)
        $this->setFontWeight(strpos($pdf->getFontStyle(), 'B') !== false
            ? self::FONT_WEIGHT_BOLD
            : self::FONT_WEIGHT_NORMAL
        );
    }

    public function getBorderWidth()
    {
        return $this->borderWidth;
    }

    public function setBorderWidth($borderWidth)
    {
        $this->borderWidth = $borderWidth;
        return $this;
    }

    /**
     * Get the factor for the height of one line.
     * If the factor is 1.5 you will get a line height which is one and a half times largeer than the font size.
     *
     * @return float
     */
    public function getLineHeight()
    {
        return $this->lineHeight;
    }

    /**
     * Set the factor for the height of one line.
     * If the factor is 1.5 you will get a line height which is one and a half times largeer than the font size.
     *
     * @param float $lineHeight in user units
     * @return \Tcpdf\Extension\Table\Table
     */
    public function setLineHeight($lineHeight)
    {
        $this->lineHeight = $lineHeight;
        return $this;
    }

    /**
     * Returns a new table row.
     * @return Row
     */
    public function newRow()
    {
        return $this->rows[] = new Row($this);
    }

    /**
     * Returns the PDF generator.
     * @return \TCPDF
     */
    public function getPdf()
    {
        return $this->pdf;
    }

    /**
     * Returns all rows of this table.
     * @return Row[] array of Row
     */
    public function getRows()
    {
        return $this->rows;
    }

    public function setRows(array $rows)
    {
        $this->rows = $rows;
        return $this;
    }

    public function getWidth()
    {
        if (null === $this->width) {
            return null;
        }
        if ($this->widthPercentage) {
            $margins = $this->getPdf()->getMargins();
            $maxWidth = $this->getPdf()->getPageWidth() - $margins['right'] - $this->xPosition;
            return $this->width / 100 * $maxWidth;
        }
        return $this->width;
    }

    public function getWidthPercentage()
    {
        return $this->widthPercentage;
    }

    public function setWidth($width, $percentage = false)
    {
        if (!is_numeric($width)) {
            throw new \InvalidArgumentException('The width must be numeric.');
        }
        $this->width = $width;
        $this->widthPercentage = (bool) $percentage;
        return $this;
    }

    public function getFontFamily()
    {
        return $this->fontFamily;
    }

    public function setFontFamily($fontFamily)
    {
        $this->fontFamily = $fontFamily;
        return $this;
    }

    public function getFontSize()
    {
        return $this->fontSize;
    }

    public function setFontSize($fontSize)
    {
        if (!is_numeric($fontSize)) {
            throw new \InvalidArgumentException('The font size must be numeric.');
        }
        $this->fontSize = $fontSize;
        return $this;
    }

    public function getFontWeight()
    {
        return $this->fontWeight;
    }

    public function setFontWeight($fontWeight)
    {
        if (!in_array($fontWeight, array(self::FONT_WEIGHT_NORMAL, self::FONT_WEIGHT_BOLD))) {
            throw new \InvalidArgumentException("The font weight '$fontWeight' is not supported.");
        }
        $this->fontWeight = $fontWeight;
        return $this;
    }

    /**
     * Set the callback function, which will be executed on page break.
     * 
     * @param callable $callback
     * @return \Tcpdf\Extension\Table\Table
     */
    public function setPageBreakCallback($callback)
    {
        $this->pageBreakCallback = $callback;
        return $this;
    }

    /**
     * Returns the callback which should be called if the table makes a page break.
     * 
     * @return callable
     */
    public function getPageBreakCallback()
    {
        return $this->pageBreakCallback;
    }

    /**
     * Draws the table and returns the PDF generator.
     * @return \TCPDF
     */
    public function end()
    {
        $converter = new TableConverter($this, $this->cacheDir);
        $converter->convert();

        return $this->getPdf();
    }
}
