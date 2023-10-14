<?php

namespace DMS\Constants;

class PanelRights {
    public const SETTINGS = 'settings';
    public const DOCUMENTS = 'documents';
    public const PROCESSES = 'processes';
    public const SETTINGS_USERS = 'settings.users';
    public const SETTINGS_GROUPS = 'settings.groups';

    public static $all = array(
        self::SETTINGS,
        self::DOCUMENTS,
        self::PROCESSES,
        self::SETTINGS_USERS,
        self::SETTINGS_GROUPS
    );
}

?>