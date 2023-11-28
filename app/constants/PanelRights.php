<?php

namespace DMS\Constants;

class PanelRights {
    public const SETTINGS = 'settings';
    public const DOCUMENTS = 'documents';
    public const PROCESSES = 'processes';
    public const SETTINGS_USERS = 'settings.users';
    public const SETTINGS_GROUPS = 'settings.groups';
    public const SETTINGS_METADATA = 'settings.metadata';
    public const SETTINGS_SYSTEM = 'settings.system';
    public const FOLDERS = 'folders';
    public const SETTINGS_SERVICES = 'settings.services';
    public const SETTINGS_NOTIFICATIONS = 'settings.notifications';

    public static $all = array(
        self::SETTINGS,
        self::DOCUMENTS,
        self::PROCESSES,
        self::SETTINGS_USERS,
        self::SETTINGS_GROUPS,
        self::SETTINGS_METADATA,
        self::SETTINGS_SYSTEM,
        self::FOLDERS,
        self::SETTINGS_SERVICES,
        self::SETTINGS_NOTIFICATIONS
    );
}

?>