<?php

namespace DMS\Entities;

/**
 * Entity right
 * 
 * @author Lukas Velek
 */
class EntityRight extends AEntity {
    private string $type;
    private string $name;
    private bool $value;

    /**
     * Class constructor
     * 
     * @param string $type Right type
     * @param string $name Right name
     * @param bool $value Right value
     */
    public function __construct(string $type, string $name, bool $value) {
        $this->type = $type;
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Returns right type
     * 
     * @return string Right type
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Returns right name
     * 
     * @return string Right name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns right value
     * 
     * @return bool Right value
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * Sets right value
     * 
     * @param bool $value Right value
     */
    public function setValue(bool $value) {
        $this->value = $value;
    }
}

?>