<?php

namespace DMS\Enums;

class DocumentMarkColorEnum implements IExternalEnum {
    private string $name;
    private array $values;

    public function __construct() {
        $this->name = 'DocumentMarkColorEnum';
        $this->values = [];

        $this->loadValues();
    }

    public function getValues() {
        return $this->values;
    }

    public function getValueByKey(string|int $key) {
        if(array_key_exists($key, $this->values)) {
            return $this->values[$key];
        } else {
            return null;
        }
    }

    public function getKeyByValue(string|int $value) {
        $key = array_search($value, $this->values);

        if($key === FALSE) {
            return null;
        } else {
            return $key;
        }
    }

    public function getName() {
        return $this->name;
    }

    private function loadValues() {
        $values = $this->values;

        $add = function(string $name, string $text) use (&$values) {
            $values[$name] = $text;
        };

        $add('green', 'Green');

        $this->values = $values;
    }

    public static function getEnum() {
        return new self();
    }
}

?>