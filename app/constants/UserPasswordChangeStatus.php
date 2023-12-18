<?php

namespace DMS\Constants;

class UserPasswordChangeStatus {
    public const OK = 1;
    public const WARNING = 2;
    public const FORCE = 3;

    public static $texts = array(
        self::OK => 'OK',
        self::WARNING => 'Warning',
        self::FORCE => 'Force'
    );
}

?>