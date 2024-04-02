<?php

namespace DMS\Entities;

class DocumentMetadataHistoryEntity extends AEntity {
    private int $idDocument;
    private int $idUser;
    private string $metadataName;
    private string $metadataValue;

    public function __construct(int $id, string $dateCreated, int $idDocument, int $idUser, string $metadataName, string $metadataValue) {
        parent::__construct($id, $dateCreated, null);

        $this->idDocument = $idDocument;
        $this->idUser = $idUser;
        $this->metadataName = $metadataName;
        $this->metadataValue = $metadataValue;
    }

    public function getIdDocument() {
        return $this->idDocument;
    }

    public function getIdUser() {
        return $this->idUser;
    }

    public function getMetadataName() {
        return $this->metadataName;
    }

    public function getMetadataValue() {
        return $this->metadataValue;
    }
}

?>