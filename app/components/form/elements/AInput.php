<?php

namespace App\Components\Form\Elements;

abstract class AInput extends AElement {
    protected function __construct(string $elementType, string $id, string $type) {
        parent::__construct($elementType, $id);

        $this->type = $type;
    }

    public function render() {
        return '<input ' . implode(' ', $this->prepareAttributes()) . '>';
    }
    
    private function prepareAttributes() {
        $attributes = [];

        foreach($this->__elements as $name => $value) {
            if($value !== null) {
                $attributes[] = $name;
            } else {
                $attributes[] = $name . '="' . $value . '"';
            }
        }

        return $attributes;
    }
}

?>