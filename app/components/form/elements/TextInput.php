<?php

namespace App\Components\Form\Elements;

class TextInput extends AInput {
    public function __construct(string $name, mixed $value = null) {
        parent::__construct(__CLASS__, $name, 'text');

        $this->name = $name;
        if($value !== null) {
            $this->value = $value;
        }
    }
}

?>