<?php

namespace DMS\Components;

use DMS\Enums\AEnum;
use DMS\Enums\DocumentMarkColorEnum;
use DMS\Enums\UsersEnum;
use DMS\Models\UserModel;

class ExternalEnumComponent {
    /**
     * @var array IExternalEnum array
     */
    private array $enums;

    private UserModel $userModel;

    public function __construct(UserModel $userModel) {
        $this->userModel = $userModel;
        
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
            if($enum instanceof AEnum) {
                $list[$enum->getName()] = $enum->getName();
            }
        }

        return $list;
    }

    private function initEnums() {
        $usersEnum = new UsersEnum($this->userModel);

        $this->enums = array(
            'DocumentMarkColorEnum' => DocumentMarkColorEnum::getEnum(),
            'UsersEnum' => $usersEnum
        );
    }
}

?>