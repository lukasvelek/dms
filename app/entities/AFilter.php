<?php

namespace DMS\Entities;

abstract class AFilter extends AEntity {
    private string $sql;
    private string $name;
    private ?string $description;
    private ?int $idAuthor;

    protected function __construct(int $id, ?int $idAuthor, string $name, ?string $description, string $sql) {
        parent::__construct($id, null, null);

        $this->sql = $sql;
        $this->name = $name;
        $this->description = $description;
        $this->idAuthor = $idAuthor;
    }

    public function getSql() {
        return $this->sql;
    }

    public function getName() {
        return $this->name;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getIdAuthor() {
        return $this->idAuthor;
    }
}

?>