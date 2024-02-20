<?php

namespace DMS\Entities;

/**
 * Archive entity
 * 
 * @author Lukas Velek
 */
class Archive extends AEntity {
    private string $name;
    private int $type;
    private ?int $idParentArchiveEntity;
    private int $status;

    /**
     * Class constructor
     * 
     * @param int $id Archive ID
     * @param string $dateCreated Date created
     * @param string $name Archive name
     * @param int $type Archive type
     * @param null|int $idParentArchiveEntity Parent archive entity ID or null
     * @param int $status Archive status
     */
    public function __construct(int $id, string $dateCreated, string $name, int $type, ?int $idParentArchiveEntity, int $status) {
        parent::__construct($id, $dateCreated, null);

        $this->name = $name;
        $this->type = $type;
        $this->idParentArchiveEntity = $idParentArchiveEntity;
        $this->status = $status;
    }

    /**
     * Returns archive name
     * 
     * @return string Archive name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns archive type
     * 
     * @return int Archive type
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Returns archive name
     * 
     * @return null|int Parent archive entity ID or null
     */
    public function getIdParentArchiveEntity() {
        return $this->idParentArchiveEntity;
    }

    /**
     * Returns archive status
     * 
     * @return int Archive status
     */
    public function getStatus() {
        return $this->status;
    }
}

?>