<?php

namespace DMS\Entities;

class UserLoginBlockEntity extends AEntity {
    private int $idUser;
    private string $description;
    private string $dateFrom;
    private ?string $dateTo;
    private bool $isActive;

    public function __construct(int $id, string $dateCreated, int $idUser, string $description, string $dateFrom, ?string $dateTo, bool $isActive) {
        parent::__construct($id, $dateCreated, null);

        $this->idUser = $idUser;
        $this->description = $description;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->isActive = $isActive;
    }

    public function getIdUser() {
        return $this->idUser;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getDateFrom() {
        return $this->dateFrom;
    }

    public function getDateTo() {
        return $this->dateTo;
    }

    public function isActive() {
        return $this->isActive;
    }
}

?>