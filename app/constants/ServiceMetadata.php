<?php

namespace DMS\Constants;

class ServiceMetadata {
    public const FILES_KEEP_LENGTH = 'files_keep_length';
    public const PASSWORD_CHANGE_PERIOD = 'password_change_period';
    public const PASSWORD_CHANGE_FORCE_ADMINISTRATORS = 'password_change_force_administrators';
    public const PASSWORD_CHANGE_FORCE = 'password_change_force';

    public static $texts = array(
        self::FILES_KEEP_LENGTH => 'Files keep length',
        self::PASSWORD_CHANGE_PERIOD => 'Password change period',
        self::PASSWORD_CHANGE_FORCE_ADMINISTRATORS => 'Force password change for administrators',
        self::PASSWORD_CHANGE_FORCE => 'Force password change for general users'
    );
}

?>