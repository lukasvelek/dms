<?php

namespace DMS\Entities;

class Comment extends AEntity {
    private int $idAuthor;
    private string $text;

    public function __construct(int $id, string $dateCreated, int $idAuthor, string $text) {
        parent::__construct($id, $dateCreated, null);

        $this->idAuthor = $idAuthor;
        $this->text = $text;
    }

    public function getIdAuthor() {
        return $this->idAuthor;
    }

    public function getText() {
        return $this->text;
    }
}

?>