<?php

namespace DMS\Entities;

class MetadataValue extends AEntity {
    private int $idMetadata;
    private string $name;
    private string $value;

    public function __construct(int $id, int $idMetadata, string $name, string $value) {
        parent::__construct($id, null);

        $this->idMetadata = $idMetadata;
        $this->name = $name;
        $this->value = $value;
    }

    public function getIdMetadata() {
        return $this->idMetadata;
    }

    public function getName() {
        return $this->name;
    }

    public function getValue() {
        return $this->value;
    }
}

?>