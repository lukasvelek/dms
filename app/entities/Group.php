<?php

namespace DMS\Entities;

/**
 * Grop entity
 * 
 * @author Lukas Velek
 */
class Group extends AEntity {
    private string $name;
    private ?string $code;

    /**
     * Class constructor
     * 
     * @param int $id Group ID
     * @param string $dateCreated Date created
     * @param string $name Group name
     * @param null|string $code Group code or null
     */
    public function __construct(int $id, string $dateCreated, string $name, ?string $code) {
        parent::__construct($id, $dateCreated, null);

        $this->name = $name;
        $this->code = $code;
    }

    /**
     * Returns group name
     * 
     * @return string Group name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns group code
     * 
     * @return null|string Group code or null
     */
    public function getCode() {
        return $this->code;
    }
}

?>