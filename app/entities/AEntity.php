<?php

namespace DMS\Entities;

abstract class AEntity {
    protected int $id;
    protected ?string $dateCreated;

    protected function __construct(int $id, ?string $dateCreated) {
        $this->id = $id;
        $this->dateCreated = $dateCreated;
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
}

?>