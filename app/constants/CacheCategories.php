<?php

namespace DMS\Constants;

class CacheCategories {
    public const BULK_ACTIONS = 'bulk_actions';
    public const PANELS = 'panels';
    public const ACTIONS = 'actions';
    public const FOLDERS = 'folders';
    public const METADATA = 'metadata';
    public const SERVICE_CONFIG = 'service_config';

    public static $all = array(
        self::BULK_ACTIONS,
        self::PANELS,
        self::ACTIONS,
        self::FOLDERS,
        self::METADATA,
        self::SERVICE_CONFIG
    );
}

?>