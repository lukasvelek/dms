<?php

namespace DMS\Enums;

use DMS\Models\UserModel;

/**
 * Users external enum
 * 
 * @author Lukas Velek
 */
class UsersEnum extends AEnum {
    private UserModel $userModel;

    /**
     * Class constructor
     * 
     * @param UserModel $userModel UserModel instance
     */
    public function __construct(UserModel $userModel) {
        parent::__construct('UsersEnum');
        $this->userModel = $userModel;

        $this->loadValues();
    }

    /**
     * Loads enum values
     */
    private function loadValues() {
        $users = $this->userModel->getAllUsers();

        $this->addValue('null', '-');

        foreach($users as $user) {
            $this->addValue($user->getUsername(), $user->getFirstname() . ' ' . $user->getLastname());
        }
    }
}

?>