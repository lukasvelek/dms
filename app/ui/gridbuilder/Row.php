<?php

namespace DMS\UI\GridBuilder;

class Row {
    public string $title;
    public string $code;
    public array $cols;
    public int $colspan;
    public $onRender;

    public function __construct(string $title, string $code = '') {
        $this->title = $title;
        $this->cols = [];
        $this->colspan = 1;

        if($code == '') {
            $this->code = strtolower($this->title);
        } else {
            $this->code = $code;
        }

        return $this;
    }

    public function addCol(string $text) {
        $this->cols[] = $text;

        return $this;
    }

    public function addColArray(array $texts) {
        $this->cols = array_merge($this->cols, $texts);
        
        return $this;
    }

    public function setColspan(int $colspan) {
        $this->colspan = $colspan;

        return $this;
    }

    public function addOnRender(callable $func) {
        $this->onRender = $func;

        return $this;
    }
}

?>