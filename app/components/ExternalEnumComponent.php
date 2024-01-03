<?php

namespace DMS\Components;

use DMS\Enums\AEnum;
use DMS\Enums\DocumentMarkColorEnum;
use DMS\Enums\GroupsEnum;
use DMS\Enums\UsersEnum;
use DMS\Models\GroupModel;
use DMS\Models\UserModel;

class ExternalEnumComponent {
    /**
     * @var array IExternalEnum array
     */
    private array $enums;

    private array $models;

    public function __construct(array $models) {
        $this->models = $models;
        
        $this->initEnums();
    }

    public function getModelByName(string $name) {
        if(array_key_exists($name, $this->models)) {
            return $this->models[$name];
        } else {
            return null;
        }
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
            if($enum instanceof AEnum) {
                $list[$enum->getName()] = $enum->getName();
            }
        }

        return $list;
    }

    private function initEnums() {
        $this->enums = array(
            'DocumentMarkColorEnum' => DocumentMarkColorEnum::getEnum(),
            'UsersEnum' => new UsersEnum($this->getModelByName('userModel')),
            'GroupsEnum' => new GroupsEnum($this->getModelByName('groupModel'))
        );
    }
}

?>