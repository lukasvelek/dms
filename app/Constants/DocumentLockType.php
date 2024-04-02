<?php

namespace DMS\Constants;

class DocumentLockType {
    public const USER_LOCK = 'user_lock';
    public const PROCESS_LOCK = 'process_lock';

    /**
     * Textation must be "Document locked by: $result" where $result is the value received from this array
     */
    public static $texts = [
        self::USER_LOCK => 'User',
        self::PROCESS_LOCK => 'Process'
    ];

    public static $colors = [
        self::USER_LOCK => 'blue',
        self::PROCESS_LOCK => 'brown'
    ];
}

?>