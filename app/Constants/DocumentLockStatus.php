<?php

namespace DMS\Constants;

class DocumentLockStatus {
    public const ACTIVE = 1;
    public const INACTIVE = 2;

    public static $texts = [
        self::ACTIVE => 'Active',
        self::INACTIVE => 'Inactive'
    ];
}

?>