<?php

namespace Tcpdf\Extension\Table;

/**
 * Tcpdf\Extension\Table\Row
 *
 * @author naitsirch
 */
class Row
{
    private $table;
    private $cells;

    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    /**
     * Returns a new table cell.
     * @param string $text
     * @return Cell
     */
    public function newCell($text = '')
    {
        return $this->cells[] = new Cell($this, $text);
    }

    /**
     * Returns the table instance.
     * @return Table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Returns all cells of this table row.
     * @return Cell[] array of Cell
     */
    public function getCells()
    {
        return $this->cells;
    }

    /**
     * Returns the table instance.
     * @return Table
     */
    public function end()
    {
        return $this->getTable();
    }
}
