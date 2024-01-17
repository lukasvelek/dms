<?php

namespace DMS\Entities;

abstract class AFilter extends AEntity {
    private string $sql;
    private string $name;
    private ?string $description;
    private ?int $idAuthor;
    private bool $hasOrdering;

    protected function __construct(int $id, ?int $idAuthor, string $name, ?string $description, string $sql, bool $hasOrdering = false) {
        parent::__construct($id, null, null);

        $this->sql = $sql;
        $this->name = $name;
        $this->description = $description;
        $this->idAuthor = $idAuthor;
        $this->hasOrdering = $hasOrdering;
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

    public function hasOrdering() {
        return $this->hasOrdering;
    }
}

?>