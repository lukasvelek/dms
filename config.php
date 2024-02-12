<?php

/**
 * DATABASE CONFIGURATION
 */

$cfg['db_server'] = ''; // Database server address
$cfg['db_user'] = ''; // Database server user
$cfg['db_pass'] = ''; // Database server user password
$cfg['db_name'] = ''; // Database server name


/**
 * LOGGING CONFIGURATION
 */

$cfg['log_dir'] = ''; // Log directory location
$cfg['log_level'] = 0; // 0 - no log, 1 - log error only, 2 - log error & warning only, 3 - log all 
$cfg['sql_log_level'] = 0; // 0 - no log, 1 - log all
$cfg['log_stopwatch'] = 0; // 0 - no log, 1 - log all


/**
 * CACHING CONFIGURATION
 */

$cfg['cache_dir'] = ''; // Cache directory location
$cfg['serialize_cache'] = true; // false does not work yet


/**
 * FILE STORAGE CONFIGURATION
 */

$cfg['file_dir'] = ''; // File upload directory location


/**
 * GENERAL CONFIGURATION
 */

$cfg['id_service_user'] = '1'; // id of user used in services
$cfg['default_datetime_format'] = 'Y-m-d H:i:s'; // the default datetime format that is used when user has no own format set
$cfg['enable_relogin'] = true; // true if relogging in as other (connected) users is enabled
$cfg['folder_max_nest_level'] = 10;
$cfg['absolute_app_dir'] = '';


/**
 * MAILING CONFIGURATION
 */

$cfg['mail_sender_email'] = ''; // Mail server email
$cfg['mail_sender_name'] = ''; // Mail server display name
$cfg['mail_server'] = ''; // Mail server address
$cfg['mail_server_port'] = ''; // Mail server port
$cfg['mail_login_username'] = ''; // Mail server login username
$cfg['mail_login_password'] = ''; // Mail server login password


/**
 * GRID (UI) CONFIGURATION
 */

$cfg['grid_size'] = '25'; // grid size (number of rows)
$cfg['grid_use_fast_load'] = true; // grid fast load usage (useful with lots of documents)
$cfg['grid_use_ajax'] = true; // grid ajax usage (generally useful)
$cfg['grid_main_folder_has_all_documents'] = true; // true if grid main folder contains all documents within all folders, false if it only displays documents located in main folder


?>