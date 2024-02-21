<?php

namespace DMS\Entities;

/**
 * File storage location entity
 * 
 * @author Lukas Velek
 */
class FileStorageLocation extends AEntity {
    private string $name;
    private string $path;
    private bool $isDefault;
    private bool $isActive;
    private int $order;

    /**
     * Class constructor
     * 
     * @param int $id FileStorageLocation ID
     * @param string $name Location name
     * @param string $path Location path
     * @param bool $isDefault Is location default
     * @param bool $isActive Is location active
     */
    public function __construct(int $id, string $name, string $path, bool $isDefault, bool $isActive, int $order) {
        parent::__construct($id, null, null);

        $this->name = $name;
        $this->path = $path;
        $this->isDefault = $isDefault;
        $this->isActive = $isActive;
        $this->order = $order;
    }

    /**
     * Returns location name
     * 
     * @return string Location name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns location path
     * 
     * @return string Location path
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * Returns whether the location is default or not
     * 
     * @return bool True if location is default or false if not
     */
    public function isDefault() {
        return $this->isDefault;
    }

    /**
     * Returns whether the location is active or not
     * 
     * @return bool True if location is active or false if not
     */
    public function isActive() {
        return $this->isActive;
    }

    /**
     * Returns location order
     * 
     * @return int Location order
     */
    public function getOrder() {
        return $this->order;
    }
}

?>