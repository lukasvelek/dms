<?php

namespace DMS\Constants;

/**
 * Process type constants
 * 
 * @author Lukas Velek
 */
class ProcessTypes {
    public const DELETE = 1;
    public const HOME_OFFICE = 2;
    public const SHREDDING = 3;

    public static $texts = array(
        self::DELETE => 'Delete',
        self::HOME_OFFICE => 'Home Office',
        self::SHREDDING => 'Shredding'
    );
}

?>