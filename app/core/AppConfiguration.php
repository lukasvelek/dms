<?php

namespace DMS\Core;

/**
 * Application configuration
 * 
 * @author Lukas Velek
 */
class AppConfiguration {
    /**
     * ----- DB SERVER -----
     */
    public static function getDbServer() {
        return self::loadParam('db_server');
    }

    public static function getDbUser() {
        return self::loadParam('db_user');
    }

    public static function getDbPass() {
        return self::loadParam('db_pass');
    }

    public static function getDbName() {
        return self::loadParam('db_name');
    }

    /**
     * ----- LOGGGING -----
     */

    public static function getLogDir() {
        return self::loadParam('log_dir');
    }

    public static function getLogLevel() {
        return self::loadParam('log_level');
    }

    public static function getSqlLogLevel() {
        return self::loadParam('sql_log_level');
    }

    public static function getLogStopwatch() {
        return self::loadParam('log_stopwatch');
    }

    /**
     * ----- CACHING -----
     */

    public static function getCacheDir() {
        return self::loadParam('cache_dir');
    }

    /**
     * ----- SERVICE USER DEFINITION -----
     */

    public static function getIdServiceUser() {
        return self::loadParam('id_service_user');
    }

    /**
     * ----- MAILING -----
     */

    public static function getMailSenderEmail() {
        return self::loadParam('mail_sender_email');
    }

    public static function getMailSenderName() {
        return self::loadParam('mail_sender_name');
    }

    public static function getMailServer() {
        return self::loadParam('mail_server');
    }

    public static function getMailServerPort() {
        return self::loadParam('mail_server_port');
    }

    public static function getMailLoginUsername() {
        return self::loadParam('mail_login_username');
    }

    public static function getMailLoginPassword() {
        return self::loadParam('mail_login_password');
    }

    /**
     * ----- GRIDS -----
     */

    public static function getGridSize() {
        return self::loadParam('grid_size');
    }

    public static function getDefaultDatetimeFormat() {
        return self::loadParam('default_datetime_format');
    }

    public static function getEnableRelogin() {
        return self::loadParam('enable_relogin');
    }

    public static function getFolderMaxNestLevel() {
        return self::loadParam('folder_max_nest_level');
    }

    public static function getGridMainFolderHasAllDocuments() {
        return self::loadParam('grid_main_folder_has_all_documents');
    }

    public static function getAbsoluteAppDir() {
        return self::loadParam('absolute_app_dir');
    }

    public static function getServiceAutoRun() {
        return self::loadParam('enable_service_auto_run');
    }

    public static function getIsDebug() {
        return self::loadParam('is_debug');
    }

    public static function getDocumentReportKeepLength() {
        return self::loadParam('document_report_keep_length');
    }

    public static function getIsDocumentDuplicationEnabled() {
        return self::loadParam('enable_document_duplication');
    }

    public static function getPhpDirectoryPath() {
        return self::loadParam('php_path');
    }

    public static function getServerPath() {
        return self::loadParam('app_dir');
    }

    /**
     * ----- PRIVATE METHODS -----
     */

    private static function loadParam(string $key) {
        if(file_exists('config.local.php')) {
            include('config.local.php');
        } else {
            include('../../config.local.php');
        }

        return $cfg[$key];
    }
}

?>