<?php

namespace Tcpdf\Extension\Table;

/**
 * Tcpdf\Extension\Table\Cell
 *
 * @author naitsirch
 */
class Cell
{
    private $row;
    private $text;
    private $colspan = 1;
    private $width;
    private $minHeight;
    private $lineHeight;
    private $border = 0;
    private $align = 'L';
    private $fill = 0;
    private $fontFamily;
    private $fontSize;
    private $fontWeight;
    private $padding = array();

    public function __construct(Row $row, $text = '')
    {
        $this->row = $row;
        $this->setText($text);
        $this->setBorderWidth($row->getTable()->getBorderWidth());
        $this->setFontFamily($row->getTable()->getFontFamily());
        $this->setFontSize($row->getTable()->getFontSize());
        $this->setFontWeight($row->getTable()->getFontWeight());
        $this->setLineHeight($row->getTable()->getLineHeight());
        $this->setPadding($row->getTable()->getPdf()->getCellPaddings());
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
        $this->width = $width;
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

    /**
     * Get the height of one line. The value is given in user units.
     * @return float
     */
    public function getLineHeight()
    {
        return $this->lineHeight;
    }

    /**
     * Set the height of one line. The value must be in user units.
     *
     * @param float $lineHeight in user units
     * @return \Tcpdf\Extension\Table\Cell
     */
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

    public function getBorderWidth()
    {
        return $this->borderWidth;
    }

    public function setBorderWidth($borderWidth)
    {
        $this->borderWidth = $borderWidth;
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

    public function getFontFamily()
    {
        return $this->fontFamily;
    }

    public function setFontFamily($fontFamily)
    {
        $this->fontFamily = $fontFamily;
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
     *   <li><i>normal</i>: Table::FONT_WEIGHT_NORMAL</li>
     *   <li><i>bold</i>: Table::FONT_WEIGHT_BOLD</li>
     * </ul>
     * @return Cell
     * @throws InvalidArgumentException
     */
    public function setFontWeight($fontWeight)
    {
        if (!in_array($fontWeight, array(Table::FONT_WEIGHT_NORMAL, Table::FONT_WEIGHT_BOLD))) {
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
        return $this->padding;
    }
    
    /**
     * Set the padding of the cell. Defining the paddings is analogue to the
     * definition in CSS.
     * 
     * Set all paddings by array
     * @example $pdf->setPadding(array('T' => 2, 'R' => 3, 'B' => 2, 'L' => 3));
     * 
     * Set all paddings by parameters
     * @example $pdf->setPadding(2, 3, 2, 3);
     * 
     * Passing one parameter, assigns the value to all paddings
     * @example $pdf->setPadding(2);
     * 
     * Passing two parameters, assigns the first one to the top and bottom padding
     * and the second parameter to the right and left padding.
     * @example $pdf->setPadding(2, 3);
     * 
     * @param float|array $top
     * @param float $right
     * @param float $bottom
     * @param float $left
     * @return Cell
     */
    public function setPadding($top = null, $right = null, $bottom = null, $left = null)
    {
        $padding = array();
        
        if (is_array($top)) {
            $padding = $top;
        } else {
            if (null !== $top) {
                $padding['T'] = $top;
            }
            if (null !== $right) {
                $padding['R'] = $right;
            }
            if (null !== $bottom) {
                $padding['B'] = $bottom;
            }
            if (null !== $left) {
                $padding['L'] = $left;
            }
        }
        
        if (isset($padding['T']) && !isset($padding['R']) && !isset($padding['B']) && !isset($padding['L'])) {
            $padding['R'] = $padding['B'] = $padding['L'] = $padding['T'];
        } else if (isset($padding['T']) && isset($padding['R']) && !isset($padding['B']) && !isset($padding['L'])) {
            $padding['B'] = $padding['T'];
            $padding['L'] = $padding['R'];
        }
        
        $this->padding = array_replace(array(
                'T' => 1,
                'R' => 1,
                'B' => 1,
                'L' => 1,
            ),
            $this->padding,
            $padding
        );
        
        return $this;
    }

    /**
     * Returns the table's row instance.
     * @return Row
     */
    public function end()
    {
        return $this->getTableRow();
    }
}
