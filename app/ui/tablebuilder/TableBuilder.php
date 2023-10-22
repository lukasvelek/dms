<?php

namespace DMS\UI\TableBuilder;

class TableBuilder {
    private string $border;
    private array $rows;

    public function __construct() {
        $this->clean();
    }

    public function setBorder(string $border) {
        $this->border = $border;

        return $this;
    }

    public function setRows(array $rows) {
        foreach($rows as $row) {
            $this->addRow($row);
        }

        return $this;
    }

    public function addRow(TableRow $row) {
        if($row instanceof TableRow) {
            $this->rows[] = $row;
        }

        return $this;
    }

    public function createRow() {
        return new TableRow();
    }

    public function createCol() {
        return new TableCol();
    }

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

    private function clean() {
        $this->border = '';
    }

    public static function getTemporaryObject() {
        return new self();
    }

    /**
     * Headers structure:
     *  array('key' => 'value');
     * 
     * Data structure:
     *  array('key' => 'value');
     * 
     * e.g.
     * header: array('actions' => 'Actions');
     * data: array('actions' => '<a href="do">Do</a>');
     */
    public static function createGridTable(array $headers, array $data, string $emptyText = 'No data found') {
        $tb = self::getTemporaryObject();

        $headerRow = null;

        if(empty($data)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText($emptyText)));
        } else {
            foreach($data as $dk => $dv) {

            }
        }
    }
}

?>
