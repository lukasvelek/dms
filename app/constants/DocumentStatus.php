<?php

namespace DMS\Constants;

class DocumentStatus {
    public const NEW = 1;
    public const DELETED = 2;

    public static $texts = array(
        self::NEW => 'New',
        self::DELETED => 'Deleted'
    );
}

?>