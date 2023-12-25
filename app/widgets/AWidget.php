<?php

namespace DMS\Widgets;

use DMS\Helpers\ArrayStringHelper;

abstract class AWidget implements IRenderable {
    protected array $code;

    protected function __construct() {
        $this->code = [];
    }

    protected function add(string $title, string $text) {
        $this->code[] = '<p><b>' . $title . ':</b> ' . $text . '</p>';
    }

    public function render() {
        return ArrayStringHelper::createUnindexedStringFromUnindexedArray($this->code);
    }
}

?>