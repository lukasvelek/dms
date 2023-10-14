<?php

namespace DMS\Constants;

class UserActionRights {
    public const CREATE_USER = 'create_user';
    public const CREATE_GROUP = 'create_group';

    public static $all = array(
        self::CREATE_USER,
        self::CREATE_GROUP
    );
}

?>