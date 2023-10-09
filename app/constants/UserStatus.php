<?php

namespace DMS\Constants;

class UserStatus {
    public const INACTIVE = 0;
    public const ACTIVE = 1;
    public const PASSWORD_CREATION_REQUIRED = 2;

    public static $texts = array(
        self::INACTIVE => 'Inactive',
        self::ACTIVE => 'Active',
        self::PASSWORD_CREATION_REQUIRED => 'Password creation required'
    );
}

?>