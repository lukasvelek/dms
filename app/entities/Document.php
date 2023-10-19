<?php

namespace DMS\Entities;

class Document extends AEntity {
    private string $name;
    private int $idAuthor;
    private ?int $idOfficer;
    private int $idManager;
    private int $status;
    private int $idGroup;
    private int $isDeleted;
    
    public function __construct(int $id, string $dateCreated, int $idAuthor, ?int $idOfficer, string $name, int $status, int $idManager, int $idGroup, int $isDeleted) {
        parent::__construct($id, $dateCreated);

        $this->idAuthor = $idAuthor;
        $this->idOfficer = $idOfficer;
        $this->name = $name;
        $this->status = $status;
        $this->idManager = $idManager;
        $this->idGroup = $idGroup;
        $this->isDeleted = $isDeleted;
    }

    public function getIdAuthor() {
        return $this->idAuthor;
    }

    public function getIdOfficer() {
        return $this->idOfficer;
    }

    public function getName() {
        return $this->name;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getIdManager() {
        return $this->idManager;
    }

    public function getIdGroup() {
        return $this->idGroup;
    }

    public function getIsDeleted() {
        return $this->isDeleted;
    }
}

?>