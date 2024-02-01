<?php

namespace DMS\Constants;

class ArchiveType {
    public const DOCUMENT = 1;
    public const BOX = 2;
    public const ARCHIVE = 3;

    public static $texts = array(
        self::DOCUMENT => 'Document',
        self::BOX => 'Box',
        self::ARCHIVE => 'Archive'
    );
}

?>