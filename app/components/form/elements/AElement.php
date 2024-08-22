<?php

namespace App\Components\Form\Elements;

use App\UI\IUIRenderable;

abstract class AElement implements IUIRenderable {
    public array $__elements;
    public string $__id;
    public string $__type;

    protected function __construct(string $type, string $id) {
        $this->__id = $id;
        $this->__type = $type;
        $this->__elements = [];
    }

    public function __set(string $key, mixed $value) {
        $this->$key = $value;
        $this->__elements[] = $key;
    }

    public function __get(string $key) {
        if(array_key_exists($key, $this->__elements)) {
            return $this->__elements[$key];
        } else {
            return null;
        }
    }
}

?>