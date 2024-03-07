<?php

namespace DMS\Entities;

/**
 * Folder entity
 * 
 * @author Lukas Velek
 */
class Folder extends AEntity {
    private string $name;
    private ?string $description;
    private ?int $idParentFolder;
    private int $nestLevel;
    private int $order;

    /**
     * Class constructor
     * 
     * @param int $id Folder ID
     * @param string $dateCreated Date created
     * @param null|int $idParentFolder Parent folder ID or null
     * @param string $name Folder name
     * @param null|string $description Folder description
     * @param int $nestLevel Folder nest level
     * @param int $order Folder order
     */
    public function __construct(int $id, string $dateCreated, ?int $idParentFolder, string $name, ?string $description, int $nestLevel, int $order) {
        parent::__construct($id, $dateCreated, null);
        
        $this->name = $name;
        $this->idParentFolder = $idParentFolder;
        $this->description = $description;
        $this->nestLevel = $nestLevel;
        $this->order = $order;
    }

    /**
     * Returns folder name
     * 
     * @return string Folder name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns parent folder ID
     * 
     * @return null|int Parent folder ID or null
     */
    public function getIdParentFolder() {
        return $this->idParentFolder;
    }

    /**
     * Returns folder description
     * 
     * @return null|string Folder description
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Returns folder nest level
     * 
     * @return int Folder nest level
     */
    public function getNestLevel() {
        return $this->nestLevel;
    }

    /**
     * Returns folder order
     * 
     * @return int Folder order
     */
    public function getOrder() {
        return $this->order;
    }
}

?>