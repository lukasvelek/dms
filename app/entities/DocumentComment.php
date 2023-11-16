<?php

namespace DMS\Entities;

class DocumentComment extends Comment {
    private int $idDocument;

    public function __construct(int $id, string $dateCreated, int $idAuthor, string $text, int $idDocument) {
        parent::__construct($id, $dateCreated, $idAuthor, $text);

        $this->idDocument = $idDocument;
    }

    public function getIdDocument() {
        return $this->idDocument;
    }
}

?>