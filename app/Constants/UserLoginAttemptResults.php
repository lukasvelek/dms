<?php

namespace DMS\Constants;

class UserLoginAttemptResults {
    public const SUCCESS = 1;
    public const BAD_CREDENTIALS = 0;
    public const NON_EXISTING_USER = -1;
    public const BLOCKED_USER = 2;

    public static $texts = [
        self::SUCCESS => 'Success',
        self::BAD_CREDENTIALS => 'Bad password',
        self::NON_EXISTING_USER => 'Non existing user',
        self::BLOCKED_USER => 'Blocked user'
    ];
}

?>