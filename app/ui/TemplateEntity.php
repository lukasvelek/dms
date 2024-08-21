<?php

namespace App\UI;

class TemplateEntity {
    private array $__elements;

    public function __construct() {
        $this->__elements = [];
    }

    public function render() {
        
    }

    public function __set(string $name, mixed $value) {
        $this->$name = $value;

        $this->__elements[] = $name;
    }

    public function __get(string $name) {
        if(array_key_exists($name, $this->__elements)) {
            return $this->$name;
        } else {
            return null;
        }
    }
}

?>