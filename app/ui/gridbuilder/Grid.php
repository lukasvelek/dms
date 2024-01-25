<?php

namespace DMS\UI\GridBuilder;

use DMS\UI\IBuildable;

class Grid implements IBuildable {
    private array $rows;
    private array $headers;

    public function __construct() {
        $this->rows = [];
        $this->headers = [];

        return $this;
    }

    public function addHeader(string $code) {
        $this->headers[] = $code;

        return $this;
    }

    public function addRow(Row $row) {
        $this->rows[] = $row;

        return $this;
    }

    public function createRow(string $title, string $code = '') {
        return new Row($title, $code);
    }

    public function build() {
        $code = '';

        if(empty($this->headers)) {
            $code = $this->buildWithoutHeaders();
        } else {
            $code = $this->buildWithHeaders();
        }

        return $code;
    }

    private function buildWithHeaders() {

    }

    private function buildWithoutHeaders() {
        $code = '<table>';

        $code .= '<tr>';
        foreach($this->rows as $row) {
            $code .= '<th colspan="' . $row->colspan . '">' . $row->title . '</th>';
        }
        $code .= '</tr>';

        for($i = 0; $i < count($this->rows[0]->cols); $i++) {
            $code .= '<tr>';

            for($j = 0; $j < count($this->rows); $j++) {
                $col = $this->rows[$j]->cols[$i];
                $row = $this->rows[$j];
                $row->onRender;

                $code .= '<td>' . $col . '</td>';
            }

            $code .= '</tr>';
        }

        $code .= '</table>';

        return $code;
    }
}

?>