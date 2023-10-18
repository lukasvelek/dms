<?php

namespace DMS\Entities;

class GroupUser {
    private int $id;
    private int $idGroup;
    private int $idUser;
    private bool $isManager;

    public function __construct(int $id, int $idGroup, int $idUser, bool $isManager) {
        $this->id = $id;
        $this->idGroup = $idGroup;
        $this->idUser = $idUser;
        $this->isManager = $isManager;
    }

    public function getId() {
        return $this->id;
    }

    public function getIdGroup() {
        return $this->idGroup;
    }

    public function getIdUser() {
        return $this->idUser;
    }

    public function getIsManager() {
        return $this->isManager;
    }
}

?>