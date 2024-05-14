<?php

namespace DMS\Entities;

class UserSubstituteEntity extends AEntity {
    private int $idUser;
    private int $idSubstitute;

    public function __construct(int $id, int $idUser, int $idSubstitute) {
        parent::__construct($id, null, null);

        $this->idUser = $idUser;
        $this->idSubstitute = $idSubstitute;
    }
    
    public function getIdUser() {
        return $this->idUser;
    }

    public function getIdSubstitute() {
        return $this->idSubstitute;
    }
}

?>