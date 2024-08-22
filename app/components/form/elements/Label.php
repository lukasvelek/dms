<?php

namespace App\Components\Form\Elements;

class Label extends AElement {
    public function __construct(string $for, string $text) {
        parent::__construct(__CLASS__, $for);

        $this->for = $for;
        $this->text = $text;
    }

    public function render() {
        return '<label for="' . $this->for . '">' . $this->text . '</label>';
    }
}

?>