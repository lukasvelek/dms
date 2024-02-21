<?php

namespace DMS\Entities;

/**
 * Document comment entity
 */
class DocumentComment extends Comment {
    private int $idDocument;

     /**
     * Class constructor
     * 
     * @param int $id Comment ID
     * @param string $dateCreated Date created
     * @param int $idAuthor Author ID
     * @param string $text Comment text
     * @param int $idDocument Document ID
     */
    public function __construct(int $id, string $dateCreated, int $idAuthor, string $text, int $idDocument) {
        parent::__construct($id, $dateCreated, $idAuthor, $text);

        $this->idDocument = $idDocument;
    }

    /**
     * Returns document ID
     * 
     * @return int Document ID
     */
    public function getIdDocument() {
        return $this->idDocument;
    }
}

?>