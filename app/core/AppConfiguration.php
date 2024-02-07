<?php

namespace DMS\Core;

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

    public static function getSerializeCache() {
        return self::loadParam('serialize_cache');
    }

    /**
     * ----- FILE STORAGE -----
     */

    public static function getFileDir() {
        return self::loadParam('file_dir');
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

    public static function getGridUseFastLoad() {
        return self::loadParam('grid_use_fast_load');
    }

    public static function getGridUseAjax() {
        return self::loadParam('grid_use_ajax');
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

    public static function getGridMainFolderHasAllComments() {
        return self::loadParam('grid_main_folder_has_all_documents');
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