<?php

namespace DMS\Widgets;

use DMS\Helpers\ArrayStringHelper;
use DMS\UI\LinkBuilder;

abstract class AWidget implements IRenderable {
    protected array $code;

    protected function __construct() {
        $this->code = [];
    }

    protected function add(string $title, string $text) {
        $this->code[] = '<p><b>' . $title . ':</b> ' . $text . '</p>';
    }

    protected function updateLink(string $link, string $lastUpdate) {
        $this->code[] = '<p>Last update: ' . $lastUpdate . ' | ' . $link . '</p>';
    }

    public function render() {
        return ArrayStringHelper::createUnindexedStringFromUnindexedArray($this->code);
    }
}

?>