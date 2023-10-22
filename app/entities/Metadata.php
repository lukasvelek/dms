<?php

namespace DMS\Entities;

class Metadata extends AEntity {
    private string $name;
    private string $text;
    private string $tableName;

    public function __construct(int $id, string $name, string $text, string $tableName) {
        parent::__construct($id, null);

        $this->name = $name;
        $this->text = $text;
        $this->tableName = $tableName;
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
}

?>