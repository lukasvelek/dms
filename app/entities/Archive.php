<?php

namespace DMS\Entities;

class Archive extends AEntity {
    private string $name;
    private int $type;
    private ?int $idParentArchiveEntity;

    public function __construct(int $id, string $dateCreated, string $name, int $type, ?int $idParentArchiveEntity) {
        parent::__construct($id, $dateCreated, null);

        $this->name = $name;
        $this->type = $type;
        $this->idParentArchiveEntity = $idParentArchiveEntity;
    }

    public function getName() {
        return $this->name;
    }

    public function getType() {
        return $this->type;
    }

    public function getIdParentArchiveEntity() {
        return $this->idParentArchiveEntity;
    }
}

?>