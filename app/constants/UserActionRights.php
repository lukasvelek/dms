<?php

namespace DMS\Constants;

class UserActionRights {
    public const CREATE_USER = 'create_user';
    public const CREATE_GROUP = 'create_group';
    public const MANAGE_USER_RIGHTS = 'manage_user_rights';
    public const MANAGE_GROUP_RIGHTS = 'manage_group_rights';
    public const CREATE_METADATA = 'create_metadata';
    public const DELETE_METADATA = 'delete_metadata';
    public const EDIT_METADATA_VALUES = 'edit_metadata_values';
    public const EDIT_USER_METADATA_RIGHTS = 'edit_user_metadata_rights';

    public static $all = array(
        self::CREATE_USER,
        self::CREATE_GROUP,
        self::MANAGE_USER_RIGHTS,
        self::MANAGE_GROUP_RIGHTS,
        self::CREATE_METADATA,
        self::DELETE_METADATA,
        self::EDIT_METADATA_VALUES,
        self::EDIT_USER_METADATA_RIGHTS
    );
}

?>