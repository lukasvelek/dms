<?php

namespace App\Components\Form;

class FormStateList {
    public array $__elements;

    public function __construct() {
        $this->__elements = [];
    }

    public function __set(string $key, mixed $value) {
        $this->$key = $value;
        $this->__elements[] = $key;
    }

    public function __get(string $key) {
        if(array_key_exists($key, $this->__elements)) {
            return $this->$key;
        } else {
            return null;
        }
    }
}

?>