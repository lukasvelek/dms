<?php

namespace DMS\Enums;

use DMS\Models\UserModel;

class UsersEnum extends AEnum {
    private UserModel $userModel;

    public function __construct(UserModel $userModel) {
        parent::__construct('UsersEnum');
        $this->userModel = $userModel;

        $this->loadValues();
    }

    private function loadValues() {
        $users = $this->userModel->getAllUsers();

        $this->addValue('null', '-');

        foreach($users as $user) {
            $this->addValue($user->getUsername(), $user->getFirstname() . ' ' . $user->getLastname());
        }
    }
}

?>