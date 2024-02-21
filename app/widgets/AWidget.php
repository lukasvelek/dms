<?php

namespace DMS\Widgets;

use DMS\Helpers\ArrayStringHelper;
use DMS\UI\LinkBuilder;

abstract class AWidget implements IRenderable {
    protected array $code;

    protected function __construct() {
        $this->code = [];
    }

    protected function add(string $title, string $text, bool $useColon = true) {
        $code = '<p><b>' . $title;

        if($useColon === TRUE) {
            $code .= ':';
        }

        $code .= '</b> ' . $text . '</p>';

        $this->code[] = $code;
    }

    protected function updateLink(string $link, string $lastUpdate) {
        $this->code[] = '<p><b>Last update:</b> ' . $lastUpdate . ' | ' . $link . '</p>';
    }

    protected function addLink(string $link) {
        $this->code[] = $link;
    }

    public function render() {
        return ArrayStringHelper::createUnindexedStringFromUnindexedArray($this->code);
    }
}

?>