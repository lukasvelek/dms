<?php

namespace DMS\Constants;

class UserActionRights {
    public const CREATE_USER = 'create_user';
    public const CREATE_GROUP = 'create_group';
    public const MANAGE_USER_RIGHTS = 'manage_user_rights';
    public const MANAGE_GROUP_RIGHTS = 'manage_group_rights';

    public static $all = array(
        self::CREATE_USER,
        self::CREATE_GROUP,
        self::MANAGE_USER_RIGHTS,
        self::MANAGE_GROUP_RIGHTS
    );
}

?>