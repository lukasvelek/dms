<?php

namespace DMS\Enums;

/**
 * Common class for external enums
 * 
 * @author Lukas Velek
 */
abstract class AEnum implements IExternalEnum {
    private string $name;
    protected array $values;

    /**
     * Class constructor
     * 
     * @param string $name Enum name
     */
    protected function __construct(string $name) {
        $this->values = [];
        $this->name = $name;
    }

    /**
     * Add value to the enum
     * 
     * @param string $name Enum name
     * @param string $text Enum display text
     */
    public function addValue(string $name, string $text) {
        $this->values[$name] = $text;
    }

    /**
     * Returns enum values
     * 
     * @return array Enum values
     */
    public function getValues() {
        return $this->values;
    }

    /**
     * Returns enum value by key
     * 
     * @param string|int $key Enum value key
     * @return null|int|string Enum value by key or null
     */
    public function getValueByKey(string|int $key) {
        if(array_key_exists($key, $this->values)) {
            return $this->values[$key];
        } else {
            return null;
        }
    }

    /**
     * Returns enum value key by its value
     * 
     * @param string|int $value Enum value value
     * @return null|int|string Enum key or null
     */
    public function getKeyByValue(string|int $value) {
        $key = array_search($value, $this->values);

        if($key === FALSE) {
            return null;
        } else {
            return $key;
        }
    }

    /**
     * Returns enum name
     * 
     * @return string Enum name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Creates empty enum
     * 
     * @return self
     */
    public static function getEnum() {
        return new self('');
    }
}

?>