<?php

namespace DMS\Components;

use DMS\Enums\AEnum;
use DMS\Enums\FoldersEnum;
use DMS\Enums\GroupsEnum;
use DMS\Enums\UsersEnum;

/**
 * Component that allows using external enums
 * 
 * @author Lukas Velek
 */
class ExternalEnumComponent {
    /**
     * @var array IExternalEnum array
     */
    private array $enums;
    private array $models;

    /**
     * Class constructor
     * 
     * @param array $models Database models array
     */
    public function __construct(array $models) {
        $this->models = $models;
        
        $this->initEnums();
    }

    /**
     * Returns database model by its name
     * 
     * @param string $name Database model name
     * @return object|null Requested database model or null
     */
    public function getModelByName(string $name) {
        if(array_key_exists($name, $this->models)) {
            return $this->models[$name];
        } else {
            return null;
        }
    }

    /**
     * Returns enum by its name
     * 
     * @param string $name Enum name
     * @return object|null Request enum instance or null
     */
    public function getEnumByName(string $name) {
        if(array_key_exists($name, $this->enums)) {
            return $this->enums[$name];
        } else {
            return null;
        }
    }

    /**
     * Returns the list of contained enums
     * 
     * @return array $list List of contained enums
     */
    public function getEnumsList() {
        $list = [];

        foreach($this->enums as $enum) {
            if($enum instanceof AEnum) {
                $list[$enum->getName()] = $enum;
            }
        }

        return $list;
    }

    /**
     * Instantiates enums
     */
    private function initEnums() {
        $this->enums = array(
            'UsersEnum' => new UsersEnum($this->getModelByName('userModel')),
            'GroupsEnum' => new GroupsEnum($this->getModelByName('groupModel')),
            'FoldersEnum' => new FoldersEnum($this->getModelByName('folderModel'))
        );
    }
}

?>