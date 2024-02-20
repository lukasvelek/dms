<?php

namespace DMS\Entities;

/**
 * Metadata entity
 * 
 * @author Lukas Velek
 */
class Metadata extends AEntity {
    private string $name;
    private string $text;
    private string $tableName;
    private bool $isSystem;
    private string $inputType;
    private string $inputLength;
    private ?string $selectExternalEnumName;
    private bool $isReadonly;

    /**
     * Class constructor
     * 
     * @param int $id Metadata ID
     * @param string $name Metadata system name
     * @param string $text Metadata display text
     * @param string $tableName Metadata table name
     * @param bool $isSystem If metadata is system
     * @param string $inputType Metadata input type
     * @param string $inputLength Metadata input length
     * @param null|string $selectExternalEnumName Metadata external enum name
     * @param bool $isReadonly Is metadata readonly
     */
    public function __construct(int $id, string $name, string $text, string $tableName, bool $isSystem, string $inputType, string $inputLength, ?string $selectExternalEnumName, bool $isReadonly = false) {
        parent::__construct($id, null, null);

        $this->name = $name;
        $this->text = $text;
        $this->tableName = $tableName;
        $this->isSystem = $isSystem;
        $this->inputType = $inputType;
        $this->inputLength = $inputLength;
        $this->selectExternalEnumName = $selectExternalEnumName;
        $this->isReadonly = $isReadonly;
    }

    /**
     * Returns metadata system name
     * 
     * @return string Metadata system name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns metadata display text
     * 
     * @return string Metadata display text
     */
    public function getText() {
        return $this->text;
    }

    /**
     * Returns metadata table name
     * 
     * @return string Metadata table name
     */
    public function getTableName() {
        return $this->tableName;
    }

    /**
     * Returns if metadata is system or user created
     * 
     * @return bool True if metadata is system or false if it is user created
     */
    public function getIsSystem() {
        return $this->isSystem;
    }

    /**
     * Returns metadata input type
     * 
     * @return string Metadata input type
     */
    public function getInputType() {
        return $this->inputType;
    }

    /**
     * Returns metadata input length
     * 
     * @return string Metadata input length
     */
    public function getInputLength() {
        return $this->inputLength;
    }

    /**
     * Returns metadata external enum name
     * 
     * @return string Metadata external enum name
     */
    public function getSelectExternalEnumName() {
        return $this->selectExternalEnumName;
    }

    /**
     * Returns if metadata is readonly or not
     * 
     * @return bool True if metadata is readonly or false if not
     */
    public function getIsReadonly() {
        return $this->isReadonly;
    }
}

?>