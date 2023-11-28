<?php

namespace DMS\Entities;

class Notification extends AEntity {
    private int $idUser;
    private string $text;

    public function __construct(int $id, int $idUser, string $text) {
        parent::__construct($id, date('Y-m-d'));

        $this->idUser = $idUser;
        $this->text = $text;
    }

    public function getIdUser() {
        return $this->idUser;
    }

    public function getText() {
        return $this->text;
    }
}

?>