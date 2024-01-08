<?php

namespace DMS\Enums;

abstract class AEnum implements IExternalEnum {
    private string $name;
    protected array $values;

    protected function __construct(string $name) {
        $this->values = [];
        $this->name = $name;
    }

    public function addValue(string $name, string $text) {
        $this->values[$name] = $text;
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

    public static function getEnum() {
        return new self('');
    }
}

?>