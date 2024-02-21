<?php

namespace DMS\Entities;

/**
 * Metadata value entity
 * 
 * @author Lukas Velek
 */
class MetadataValue extends AEntity {
    private int $idMetadata;
    private string $name;
    private string $value;
    private bool $isDefault;

    /**
     * Class constructor
     * 
     * @param int $id Metadata value ID
     * @param int $idMetadata Metadata ID
     * @param string $name Metadata value name
     * @param string $value Metadata value value
     * @param bool $isDefault Is metadata value default
     */
    public function __construct(int $id, int $idMetadata, string $name, string $value, bool $isDefault = false) {
        parent::__construct($id, null, null);

        $this->idMetadata = $idMetadata;
        $this->name = $name;
        $this->value = $value;
        $this->isDefault = $isDefault;
    }

    /**
     * Returns metadata ID
     * 
     * @return int Metadata ID
     */
    public function getIdMetadata() {
        return $this->idMetadata;
    }

    /**
     * Returns metadata value name
     * 
     * @return string Metadata value name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns metadata value value
     * 
     * @return string Metadata value value
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * Returns whether the metadata value is default
     * 
     * @return bool True if metadata value is default or false if not
     */
    public function getIsDefault() {
        return $this->isDefault;
    }
}

?>