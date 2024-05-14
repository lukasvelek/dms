<?php

namespace DMS\Constants;

/**
 * Service metadata constants
 * 
 * @author Lukas Velek
 */
class ServiceMetadata {
    public const FILES_KEEP_LENGTH = 'files_keep_length';
    public const PASSWORD_CHANGE_PERIOD = 'password_change_period';
    public const PASSWORD_CHANGE_FORCE_ADMINISTRATORS = 'password_change_force_administrators';
    public const PASSWORD_CHANGE_FORCE = 'password_change_force';
    public const NOTIFICATION_KEEP_LENGTH = 'notification_keep_length';
    public const NOTIFICATION_KEEP_UNSEEN_SERVICE_USER = 'notification_keep_unseen_service_user';
    public const SERVICE_RUN_PERIOD = 'service_run_period';
    public const ARCHIVE_OLD_LOGS = 'archive_old_logs';
    public const EXTRACTION_PATH = 'extraction_path';
    public const DELETE_EXTRACTED_FILES = 'delete_extracted_files';
    public const DOCUMENT_FOLDER_FOR_IMPORTS = 'document_folder_for_imports';

    public static $texts = array(
        self::FILES_KEEP_LENGTH => 'Files keep length',
        self::PASSWORD_CHANGE_PERIOD => 'Password change period',
        self::PASSWORD_CHANGE_FORCE_ADMINISTRATORS => 'Force password change for administrators',
        self::PASSWORD_CHANGE_FORCE => 'Force password change for general users',
        self::NOTIFICATION_KEEP_LENGTH => 'Notification keep length',
        self::NOTIFICATION_KEEP_UNSEEN_SERVICE_USER => 'Keep unseen service user\'s notifications',
        self::SERVICE_RUN_PERIOD => 'Run period',
        self::ARCHIVE_OLD_LOGS => 'Archive old logs',
        self::EXTRACTION_PATH => 'Extraction path',
        self::DELETE_EXTRACTED_FILES => 'Delete extracted path',
        self::DOCUMENT_FOLDER_FOR_IMPORTS => 'Document folder for imports'
    );
}

?>