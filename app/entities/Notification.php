<?php

namespace DMS\Entities;

class Notification extends AEntity {
    private int $idUser;
    private string $text;
    private string $action;

    public function __construct(int $id, string $dateCreated, int $idUser, string $text, string $action) {
        parent::__construct($id, $dateCreated, null);

        $this->idUser = $idUser;
        $this->text = $text;
        $this->action = $action;
    }

    public function getIdUser() {
        return $this->idUser;
    }

    public function getText() {
        return $this->text;
    }

    public function getAction() {
        return $this->action;
    }
}

?>