<?php

namespace DMS\Constants;

/**
 * Document rank constants
 * 
 * @author Lukas Velek
 */
class DocumentRank {
    public const PUBLIC = 'public';
    public const PRIVATE = 'private';

    public static $texts = array(
        self::PUBLIC => 'Public',
        self::PRIVATE => 'Private'
    );
}

?>