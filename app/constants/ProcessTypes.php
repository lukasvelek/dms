<?php

namespace DMS\Constants;

class ProcessTypes {
    public const DELETE = 1;
    public const HOME_OFFICE = 2;

    public static $texts = array(
        self::DELETE => 'Delete',
        self::HOME_OFFICE => 'Home Office'
    );
}

?>