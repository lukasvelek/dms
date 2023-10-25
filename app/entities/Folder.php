<?php

namespace DMS\Entities;

class Folder extends AEntity {
    private string $name;
    private ?string $description;
    private ?int $idParentFolder;

    public function __construct(int $id, string $dateCreated, ?int $idParentFolder, string $name, ?string $description) {
        parent::__construct($id, $dateCreated);
        
        $this->name = $name;
        $this->idParentFolder = $idParentFolder;
        $this->description = $description;
    }

    public function getName() {
        return $this->name;
    }

    public function getIdParentFolder() {
        return $this->idParentFolder;
    }

    public function getDescription() {
        return $this->description;
    }
}

?>