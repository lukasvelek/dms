<?php

namespace DMS\Core;

class Application {
    private $objects;

    public function __construct() {
        $this->objects = array();
    }

    public function registerObject(string $objectName, object $object) {
        if(!array_key_exists($objectName, $this->objects)) {
            $this->objects[$objectName] = $object;
        }

        return true;
    }

    public function getObject(string $objectName) {
        if(array_key_exists($objectName, $this->objects)) {
            return $this->objects[$objectName];
        }

        return false;
    }
}

?>