<?php

namespace DMS\Entities;

class DocumentReportEntity extends AEntity {
    private int $idUser;
    private int $status;
    private string $sqlString;
    private ?string $fileSrc;
    private ?string $filename;
    private ?int $idFileStorageLocation;

    public function __construct(int $id, int $idUser, string $dateCreated, string $dateUpdated, int $status, string $sqlString, ?string $fileSrc, ?string $filename, ?int $idFileStorageLocation) {
        parent::__construct($id, $dateCreated, $dateUpdated);
        $this->idUser = $idUser;
        $this->status = $status;
        $this->sqlString = $sqlString;
        $this->fileSrc = $fileSrc;
        $this->filename = $filename;
        $this->idFileStorageLocation = $idFileStorageLocation;
    }

    public function getIdUser() {
        return $this->idUser;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getSqlString() {
        return $this->sqlString;
    }

    public function getFileSrc() {
        return $this->fileSrc;
    }

    public function getFilename() {
        return $this->filename;
    }

    public function getIdFileStorageLocation() {
        return $this->idFileStorageLocation;
    }
}

?>