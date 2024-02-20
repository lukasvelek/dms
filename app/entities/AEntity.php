<?php

namespace DMS\Entities;

/**
 * Common entity class
 * 
 * @author Lukas Velek
 */
abstract class AEntity {
    protected int $id;
    protected ?string $dateCreated;
    protected ?string $dateUpdated;

    /**
     * Class constructor
     * 
     * @param int $id Entity ID
     * @param null|string $dateCreated Date created
     * @param null|string $dateUpdated Date updated
     */
    protected function __construct(int $id, ?string $dateCreated, ?string $dateUpdated) {
        $this->id = $id;
        $this->dateCreated = $dateCreated;
        $this->dateUpdated = $dateUpdated;
    }

    /**
     * Returns entity ID
     * 
     * @return int Entity ID
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Sets entity ID
     * 
     * @param int $id Entity ID
     */
    public function setId(int $id) {
        $this->id = $id;
    }

    /**
     * Returns entity date created
     * 
     * @return null|string Entity date created or null
     */
    public function getDateCreated() {
        return $this->dateCreated;
    }

    /**
     * Sets entity date created
     * 
     * @param string $dateCreated Date created
     */
    public function setDateCreated(string $dateCreated) {
        $this->dateCreated = $dateCreated;
    }

    /**
     * Returns entity date updated
     * 
     * @return null|string Entity date updated or null
     */
    public function getDateUpdated() {
        return $this->dateUpdated;
    }

    /**
     * Sets entity date updated
     * 
     * @param string $dateUpdated Date updated
     */
    public function setDateUpdated(string $dateUpdated) {
        $this->dateUpdated = $dateUpdated;
    }
}

?>