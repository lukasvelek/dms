<?php

namespace DMS\Constants;

class CacheCategories {
    public const BULK_ACTIONS = 'bulk_actions';
    public const PANELS = 'panels';
    public const ACTIONS = 'actions';
    public const FOLDERS = 'folders';
    public const METADATA = 'metadata';
    public const SERVICE_CONFIG = 'service_config';
    public const USERS = 'users';
    public const PAGES = 'pages';
    public const RIBBONS = 'ribbons';
    public const RIBBON_USER_RIGHTS = 'ribbon_user_rights';
    public const RIBBON_GROUP_RIGHTS = 'ribbon_group_rights';
    public const FLASH_MESSAGES = 'flash_messages';
    public const SERVICE_RUN_DATES = 'service_run_dates';

    public static $all = array(
        self::BULK_ACTIONS,
        self::PANELS,
        self::ACTIONS,
        self::FOLDERS,
        self::METADATA,
        self::SERVICE_CONFIG,
        self::USERS,
        self::PAGES,
        self::RIBBONS,
        self::RIBBON_USER_RIGHTS,
        self::RIBBON_GROUP_RIGHTS,
        self::FLASH_MESSAGES,
        self::SERVICE_RUN_DATES
    );
}

?>