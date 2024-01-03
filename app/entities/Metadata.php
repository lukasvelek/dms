<?php

namespace DMS\Entities;

class Metadata extends AEntity {
    private string $name;
    private string $text;
    private string $tableName;
    private bool $isSystem;
    private string $inputType;
    private string $inputLength;
    private ?string $selectExternalEnumName;

    public function __construct(int $id, string $name, string $text, string $tableName, bool $isSystem, string $inputType, string $inputLength, ?string $selectExternalEnumName) {
        parent::__construct($id, null);

        $this->name = $name;
        $this->text = $text;
        $this->tableName = $tableName;
        $this->isSystem = $isSystem;
        $this->inputType = $inputType;
        $this->inputLength = $inputLength;
        $this->selectExternalEnumName = $selectExternalEnumName;
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

    public function getInputLength() {
        return $this->inputLength;
    }

    public function getSelectExternalEnumName() {
        return $this->selectExternalEnumName;
    }
}

?>