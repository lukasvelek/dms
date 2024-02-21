<?php

namespace DMS\Constants;

/**
 * Archive status constants
 * 
 * @author Lukas Velek
 */
class ArchiveStatus {
    public const NEW = 1;
    public const IN_BOX = 2;
    public const IN_ARCHIVE = 3;
    public const FINISHED = 4;
    public const CLOSED = 5;
    public const SUGGESTED_FOR_SHREDDING = 6;
    public const APPROVED_FOR_SHREDDING = 7;
    public const SHREDDED = 8;

    public static array $texts = array(
        self::NEW => 'New',
        self::IN_BOX => 'In a box',
        self::IN_ARCHIVE => 'In an archive',
        self::FINISHED => 'Finished',
        self::CLOSED => 'Closed',
        self::SUGGESTED_FOR_SHREDDING => 'Suggested for shredding',
        self::APPROVED_FOR_SHREDDING => 'Approved for shredding',
        self::SHREDDED => 'Shredded'
    );
}

?>