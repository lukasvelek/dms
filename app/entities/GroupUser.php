<?php

namespace DMS\Entities;

/**
 * Group user connection entity
 * 
 * @author Lukas Velek
 */
class GroupUser {
    private int $id;
    private int $idGroup;
    private int $idUser;
    private bool $isManager;

    /**
     * Class constructor
     * 
     * @param int $id Group user connection ID
     * @param int $idGroup Group ID
     * @param int $idUser User ID
     * @param bool $isManager Is user manager
     */
    public function __construct(int $id, int $idGroup, int $idUser, bool $isManager) {
        $this->id = $id;
        $this->idGroup = $idGroup;
        $this->idUser = $idUser;
        $this->isManager = $isManager;
    }

    /**
     * Returns group user connection ID
     * 
     * @return int Group user connection ID
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Returns group ID
     * 
     * @return int Group ID
     */
    public function getIdGroup() {
        return $this->idGroup;
    }

    /**
     * Returns user ID
     * 
     * @return int User ID
     */
    public function getIdUser() {
        return $this->idUser;
    }

    /**
     * Returns whether user is group manager
     * 
     * @return bool True if user is group manager or false if not
     */
    public function getIsManager() {
        return $this->isManager;
    }
}

?>