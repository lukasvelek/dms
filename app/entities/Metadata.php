<?php

namespace DMS\Entities;

class Metadata extends AEntity {
    private string $name;
    private string $text;
    private string $tableName;
    private bool $isSystem;
    private string $inputType;

    public function __construct(int $id, string $name, string $text, string $tableName, bool $isSystem, string $inputType) {
        parent::__construct($id, null);

        $this->name = $name;
        $this->text = $text;
        $this->tableName = $tableName;
        $this->isSystem = $isSystem;
        $this->inputType = $inputType;
    }

    public function getName() {
        return $this->name;
    }

    public function getText() {
        return $this->text;
    }

    public function getTableName() {
        return $this->tableName;
    }

    public function getIsSystem() {
        return $this->isSystem;
    }

    public function getInputType() {
        return $this->inputType;
    }
}

?>