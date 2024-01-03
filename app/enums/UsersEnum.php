<?php

namespace DMS\Enums;

use DMS\Models\UserModel;

class UsersEnum extends AEnum {
    private UserModel $userModel;

    public function __construct(UserModel $userModel) {
        parent::__construct('UsersEnum');
        $this->userModel = $userModel;

        $this->loadValues($this->values);
    }

    private function loadValues(array &$values) {
        $users = $this->userModel->getAllUsers();

        foreach($users as $user) {
            $this->addValue($user->getUsername(), $user->getFirstname() . ' ' . $user->getLastname());
        }
    }
}

?>