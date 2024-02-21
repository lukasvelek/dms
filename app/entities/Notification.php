<?php

namespace DMS\Entities;

/**
 * Notification entity
 * 
 * @author Lukas Velek
 */
class Notification extends AEntity {
    private int $idUser;
    private string $text;
    private string $action;

    /**
     * Class constructor
     * 
     * @param int $id Notification ID
     * @param string $dateCreated Date created
     * @param int $idUser User ID
     * @param string $text Notification text
     * @param string $action Notification open action
     */
    public function __construct(int $id, string $dateCreated, int $idUser, string $text, string $action) {
        parent::__construct($id, $dateCreated, null);

        $this->idUser = $idUser;
        $this->text = $text;
        $this->action = $action;
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
     * Returns notification text
     * 
     * @return string Notification text
     */
    public function getText() {
        return $this->text;
    }

    /**
     * Returns notification action
     * 
     * @return string Notification action
     */
    public function getAction() {
        return $this->action;
    }
}

?>