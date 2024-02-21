<?php

namespace DMS\Entities;

/**
 * Comment entity
 * 
 * @author Lukas Velek
 */
class Comment extends AEntity {
    private int $idAuthor;
    private string $text;

    /**
     * Class constructor
     * 
     * @param int $id Comment ID
     * @param string $dateCreated Date created
     * @param int $idAuthor Author ID
     * @param string $text Comment text
     */
    public function __construct(int $id, string $dateCreated, int $idAuthor, string $text) {
        parent::__construct($id, $dateCreated, null);

        $this->idAuthor = $idAuthor;
        $this->text = $text;
    }

    /**
     * Returns comment author ID
     * 
     * @return int Comment author ID or null
     */
    public function getIdAuthor() {
        return $this->idAuthor;
    }

    /**
     * Returns comment text
     * 
     * @return string Comment text
     */
    public function getText() {
        return $this->text;
    }
}

?>