<?php

namespace DMS\Entities;

class Metadata extends AEntity {
    private string $name;
    private string $text;

    public function __construct(int $id, string $name, string $text) {
        parent::__construct($id, null);

        $this->name = $name;
        $this->text = $text;
    }

    public function getName() {
        return $this->name;
    }

    public function getText() {
        return $this->text;
    }
}

?>