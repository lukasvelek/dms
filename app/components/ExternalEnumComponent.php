<?php

namespace DMS\Components;

use DMS\Enums\DocumentMarkColorEnum;
use DMS\Enums\IExternalEnum;

class ExternalEnumComponent {
    /**
     * @var array IExternalEnum array
     */
    private array $enums;

    public function __construct() {
        $this->initEnums();
    }

    public function getEnumByName(string $name) {
        if(array_key_exists($name, $this->enums)) {
            return $this->enums[$name];
        } else {
            return null;
        }
    }

    public function getEnumsList() {
        $list = [];

        foreach($this->enums as $enum) {
            if($enum instanceof IExternalEnum) {
                $list[$enum->getName()] = $enum->getName();
            }
        }

        return $list;
    }

    private function initEnums() {
        $this->enums = array(
            'DocumentMarkColorEnum' => DocumentMarkColorEnum::getEnum()
        );
    }
}

?>