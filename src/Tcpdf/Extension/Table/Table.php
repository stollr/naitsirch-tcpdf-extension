<?php

namespace Tcpdf\Extension\Table;

/**
 * Tcpdf\Extension\Table\Table
 *
 * @author naitsirch
 */
class Table
{
    private $pdf;
    private $rows;
    private $lineHeight;
    private $width;
    private $widthPercentage;

    public function __construct(\TCPDF $pdf)
    {
        $this->pdf = $pdf;
    }


    public function getLineHeight()
    {
        if (!$this->lineHeight) {
            return $this->getPdf()->getFontSize();
        }
        return $this->lineHeight;
    }

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

    public function getWidth()
    {
        if (null === $this->width) {
            return null;
        }
        if ($this->widthPercentage) {
            $maxWidth = $this->getPdf()->w - $this->getPdf()->rMargin - $this->getPdf()->x;
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
        $this->width = $width;
        $this->widthPercentage = (bool) $percentage;
        return $this;
    }


    /**
     * Draws the table and returns the PDF generator.
     * @return \TCPDF
     */
    public function end()
    {
        new TableConverter($this);
        return $this->getPdf();
    }
}
