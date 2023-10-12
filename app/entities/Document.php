<?php

namespace DMS\Entities;

class Document extends AEntity {
    /**
     * @var int
     */
    private $idAuthor;

    /**
     * @var int
     */
    private $idOfficer;

    /**
     * @var int
     */
    private $idManager;

    /**
     * @var string
     */
    private $name;
    
    /**
     * @var int
     */
    private $status;

    /**
     * @var int
     */
    private $idGroup;

    /**
     * @var int
     */
    private $isDeleted;
    
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