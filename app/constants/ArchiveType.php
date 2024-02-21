<?php

namespace DMS\Constants;

/**
 * Archive type constants
 * 
 * @author Lukas Velek
 */
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