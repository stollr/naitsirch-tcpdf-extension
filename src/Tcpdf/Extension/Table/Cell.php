<?php

namespace Tcpdf\Extension\Table;

/**
 * Tcpdf\Extension\Table\Cell
 *
 * @author naitsirch
 */
class Cell
{
    const FONT_WEIGHT_NORMAL = 'normal';
    const FONT_WEIGHT_BOLD = 'bold';

    private $row;
    private $text;
    private $colspan = 1;
    private $width;
    private $minHeight;
    private $lineHeight;
    private $border = 0;
    private $align = 'L';
    private $fill = 0;
    private $lineNumber;
    private $fontSize;
    private $fontWeight;

    public function __construct(Row $row, $text = '')
    {
        $this->row = $row;
        $this->setText($text);
        $this->setFontSize($row->getTable()->getPdf()->getFontSizePt());
        $this->setFontWeight(strpos($row->getTable()->getPdf()->getFontStyle(), 'B') !== false
            ? self::FONT_WEIGHT_BOLD
            : self::FONT_WEIGHT_NORMAL
        );
    }

    /**
     * Returns the table's row instance.
     * @return Row
     */
    public function getTableRow()
    {
        return $this->row;
    }

    public function setColspan($colspan = 1)
    {
        if ($colspan < 1) {
            throw new \InvalidArgumentException('The colspan must not be lower than "1".');
        }
        $this->colspan = $colspan;
        return $this;
    }

    public function getColspan()
    {
        return $this->colspan;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function setWidth($width)
    {
        if ($this->width != $width) {
            $this->width = $width;
            $this->lineNumber = null; // line number has to be reseted and recalculated
        }
        return $this;
    }

    public function getMinHeight()
    {
        return $this->minHeight;
    }

    public function setMinHeight($minHeight)
    {
        $this->minHeight = $minHeight;
        return $this;
    }

    public function getLineHeight()
    {
        if (!$this->lineHeight) {
            return $this->getTableRow()->getTable()->getLineHeight();
        }
        return $this->lineHeight;
    }

    public function setLineHeight($lineHeight)
    {
        $this->lineHeight = $lineHeight;
        return $this;
    }

    public function getText()
    {
        return $this->text;
    }

    public function setText($text)
    {
        $this->text = $text;
        return $this;
    }

    public function getBorder()
    {
        return $this->border;
    }

    /**
     * <div>
     *   <p>Indicates if borders must be drawn around the cell block. The value can be either a number:</p>
     *   <ul>
     *     <li><i>0</i>: no border</li>
     *     <li><i>1</i>: frame</li>
     *   </ul>
     *   <p>or a string containing some or all of the following characters (in any order):</p>
     *   <ul>
     *     <li><i>L</i>: left</li>
     *     <li><i>T</i>: top</li>
     *     <li><i>R</i>: right</li>
     *     <li><i>B</i>: bottom</li>
     *   </ul>
     *   <p>Default value: <i>0</i>.</p>
     * </div>
     * @param int|string $border
     * @return Cell
     */
    public function setBorder($border)
    {
        $this->border = $border;
        return $this;
    }

    public function getAlign()
    {
        return $this->align;
    }

    /**
     * <div>
     *   <p>Sets the text alignment. Possible values are:</p>
     *   <ul>
     *     <li><i>L</i>: left alignment</li>
     *     <li><i>C</i>: center</li>
     *     <li><i>R</i>: right alignment</li>
     *     <li><i>J</i>: justification (default value)</li>
     *   </ul>
     * </div>
     * @param string $align
     * @return Cell
     */
    public function setAlign($align)
    {
        $this->align = $align;
        return $this;
    }

    public function getFill()
    {
        return $this->fill;
    }

    /**
     * <div>
     *   <p>Indicates if the cell background must be painted (<i>true</i>) or transparent (<i>false</i>). Default value: <i>false</i>.</p>
     * </div>
     * @param boolean $fill
     * @return Cell
     */
    public function setFill($fill)
    {
        $this->fill = $fill;
        return $this;
    }

    public function getFontWeight()
    {
        return $this->fontWeight;
    }

    /**
     * Set font weight like in CSS.
     *
     * @param string $fontWeight <p>Possible values:</p><ul>
     *   <li><i>normal</i>: Cell::FONT_WEIGHT_NORMAL</li>
     *   <li><i>bold</i>: Cell::FONT_WEIGHT_BOLD</li>
     * </ul>
     * @return Cell
     * @throws InvalidArgumentException
     */
    public function setFontWeight($fontWeight)
    {
        if (!in_array($fontWeight, array(self::FONT_WEIGHT_NORMAL, self::FONT_WEIGHT_BOLD))) {
            throw new InvalidArgumentException("The font weight '$fontWeight' is not supported.");
        }
        $this->fontWeight = $fontWeight;
        return $this;
    }
    
    /**
     * Get the font size in PT.
     * @return int
     */
    public function getFontSize()
    {
        return $this->fontSize;
    }

    /**
     * Set the font size in PT.
     * @param int $fontSize
     * @return \Tcpdf\Extension\Table\Cell
     */
    public function setFontSize($fontSize)
    {
        $this->fontSize = $fontSize;
        return $this;
    }

    
    /**
     * Return cell padding.
     *     
     * @return array like this: array(
     *     'T' => 0,        // top
     *     'R' => 1.000125  // right
     *     'B' => 0         // bottom
     *     'L' => 1.000125  // left
     * )
     */
    public function getPadding()
    {
        $paddings = $this->getTableRow()->getTable()->getPdf()->getCellPaddings();
        return $paddings;
    }

    /**
     * Returns the table's row instance.
     * @return Row
     */
    public function end()
    {
        if (!$this->getLineHeight()) {
            throw new \RuntimeException('Every table cell needs a specified line height.');
        }
        return $this->getTableRow();
    }
}
