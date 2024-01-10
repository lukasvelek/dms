<?php

namespace DMS\Entities;

abstract class AEntity {
    protected int $id;
    protected ?string $dateCreated;
    protected ?string $dateUpdated;

    protected function __construct(int $id, ?string $dateCreated, ?string $dateUpdated) {
        $this->id = $id;
        $this->dateCreated = $dateCreated;
        $this->dateUpdated = $dateUpdated;
    }

    public function getId() {
        return $this->id;
    }

    public function setId(int $id) {
        $this->id = $id;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public function setDateCreated(string $dateCreated) {
        $this->dateCreated = $dateCreated;
    }

    public function getDateUpdated() {
        return $this->dateUpdated;
    }

    public function setDateUpdated(string $dateUpdated) {
        $this->dateUpdated = $dateUpdated;
    }
}

?>