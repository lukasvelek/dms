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
     * @var string
     */
    private $name;
    
    /**
     * @var int
     */
    private $status;
    
    public function __construct(int $id, string $dateCreated, int $idAuthor, ?int $idOfficer, string $name, int $status) {
        parent::__construct($id, $dateCreated);

        $this->idAuthor = $idAuthor;
        $this->idOfficer = $idOfficer;
        $this->name = $name;
        $this->status = $status;
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
}

?>