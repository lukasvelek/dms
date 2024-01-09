<?php

namespace DMS\UI;

class FlashMessageBuilder {
    private string $text;
    private string $type;

    public function __construct() {
        $this->text = '';
        $this->type = '';
    }

    public function setText(string $text) {
        $this->text = $text;

        return $this;
    }

    public function setType(string $type) {
        $this->type = $type;

        return $this;
    }

    public function getText() {
        return $this->text;
    }

    public function getType() {
        return $this->type;
    }

    public function build() {
        $code = '<div id="flash-message" class="' . $this->type . '">';
        $code .= '<div class="row">';
        $code .= '<div class="col-md">';
        $code .= $this->text;
        $code .= '</div>';
        $code .= '<div class="col-md" id="right">';
        $code .= '<a style="cursor: pointer" onclick="hideFlashMessage()">x</a>';
        $code .= '</div>';
        $code .= '</div>';
        $code .= '</div>';

        return $code;
    }
}

?>