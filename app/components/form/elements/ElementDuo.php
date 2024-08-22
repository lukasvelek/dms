<?php

namespace App\Components\Form\Elements;

class ElementDuo extends AElement {
    public function __construct(AElement $element, Label $label) {
        $this->element = $element;
        $this->label = $label;
    }

    public function render() {
        return '<span id="' . $this->element->name . '">' . $this->label->render() . '<br>' . $this->element->render() . '</span>';
    }
}

?>