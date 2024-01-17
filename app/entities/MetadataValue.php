<?php

namespace DMS\Entities;

class MetadataValue extends AEntity {
    private int $idMetadata;
    private string $name;
    private string $value;
    private bool $isDefault;

    public function __construct(int $id, int $idMetadata, string $name, string $value, bool $isDefault = false) {
        parent::__construct($id, null, null);

        $this->idMetadata = $idMetadata;
        $this->name = $name;
        $this->value = $value;
        $this->isDefault = $isDefault;
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

    public function getIsDefault() {
        return $this->isDefault;
    }
}

?>