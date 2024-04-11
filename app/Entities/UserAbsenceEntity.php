<?php

namespace DMS\Entities;

class UserAbsenceEntity extends AEntity {
    private int $idUser;
    private string $dateFrom;
    private string $dateTo;

    public function __construct(int $id, int $idUser, string $dateFrom, string $dateTo) {
        parent::__construct($id, null, null);

        $this->idUser = $idUser;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function getIdUser() {
        return $this->idUser;
    }

    public function getDateFrom() {
        return $this->dateFrom;
    }

    public function getDateTo() {
        return $this->dateTo;
    }
}

?>