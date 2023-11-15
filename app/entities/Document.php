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
    private string $rank;
    private ?int $idFolder;
    private ?string $file;

    private array $metadata;
    
    public function __construct(int $id, string $dateCreated, int $idAuthor, ?int $idOfficer, string $name, int $status, int $idManager, int $idGroup, int $isDeleted, string $rank, ?int $idFolder, ?string $file = null) {
        parent::__construct($id, $dateCreated);

        $this->idAuthor = $idAuthor;
        $this->idOfficer = $idOfficer;
        $this->name = $name;
        $this->status = $status;
        $this->idManager = $idManager;
        $this->idGroup = $idGroup;
        $this->isDeleted = $isDeleted;
        $this->rank = $rank;
        $this->idFolder = $idFolder;
        $this->file = $file;
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

    public function getRank() {
        return $this->rank;
    }

    public function getMetadata(string $key = '') {
        if($key != '') {
            if(array_key_exists($key, $this->metadata)) {
                return $this->metadata[$key];
            } else {
                return null;
            }
        } else {
            return $this->metadata;
        }
    }

    public function setMetadata(array $metadata) {
        $this->metadata = $metadata;
    }

    public function getIdFolder() {
        return $this->idFolder;
    }

    public function getFile() {
        return $this->file;
    }
}

?>