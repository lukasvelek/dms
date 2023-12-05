<?php

namespace DMS\UI\TableBuilder;

/**
 * The TableBuilder class allows users to create an HTML table.
 * 
 * @author Lukas Velek
 * @version 1.1
 */
class TableBuilder {
    private string $border;
    private array $rows;

    /**
     * The table builder constructor sets all the class variables to empty values
     */
    public function __construct() {
        $this->clean();
    }

    /**
     * Sets the table border
     * 
     * @param string $border Table border
     * @return self
     */
    public function setBorder(string $border) {
        $this->border = $border;

        return $this;
    }

    /**
     * Adds rows to the table
     * 
     * @param array $rows Table rows
     * @return self
     */
    public function setRows(array $rows) {
        foreach($rows as $row) {
            $this->addRow($row);
        }

        return $this;
    }

    /**
     * Adds a row to the table
     * 
     * @param TableRow $row TableRow instance
     * @return self
     */
    public function addRow(TableRow $row) {
        if($row instanceof TableRow) {
            $this->rows[] = $row;
        }

        return $this;
    }

    /**
     * Creates a table row
     * 
     * @return TableRow
     */
    public function createRow() {
        return new TableRow();
    }

    /**
     * Create a row column
     * 
     * @return TableCol
     */
    public function createCol() {
        return new TableCol();
    }

    /**
     * Converts the table to HTML code
     * 
     * @return string HTML code
     */
    public function build() {
        $code = array();

        $code[] = '<table border="' . $this->border . '">';

        if(!empty($this->rows)) {
            foreach($this->rows as $row) {
                $code[] = $row->build()->script;
            }
        }

        $code[] = '</table>';

        $singleLineCode = '';

        foreach($code as $c) {
            $singleLineCode .= $c;
        }

        $this->clean();

        return $singleLineCode;
    }

    /**
     * Sets all the class variables to empty values
     */
    private function clean() {
        $this->border = '';
    }

    /**
     * Returns a temporary object
     * 
     * @return self
     */
    public static function getTemporaryObject() {
        return new self();
    }
}

?>
