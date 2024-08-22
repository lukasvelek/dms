<?php

namespace App\Components\Form;

class FormStateEntity {
    public array $__attributes;
    public string $__type;

    public function __construct(string $type) {
        $this->__attributes = [];
        $this->__type = $type;
    }

    public function __set(string $key, mixed $value) {
        $this->$key = $value;
        $this->__attributes[] = $key;
    }

    public function __get(string $key) {
        if(array_key_exists($key, $this->__attributes)) {
            return $this->$key;
        } else {
            return null;
        }
    }
}

?>