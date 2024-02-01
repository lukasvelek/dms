<?php

namespace DMS\Entities;

class EntityRight extends AEntity {
    private string $type;
    private string $name;
    private bool $value;

    public function __construct(string $type, string $name, bool $value) {
        $this->type = $type;
        $this->name = $name;
        $this->value = $value;
    }

    public function getType() {
        return $this->type;
    }

    public function getName() {
        return $this->name;
    }

    public function getValue() {
        return $this->value;
    }

    public function setValue(bool $value) {
        $this->value = $value;
    }
}

?>