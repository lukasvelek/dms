CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `firstname` VARCHAR(256) NOT NULL,
    `lastname` VARCHAR(256) NOT NULL,
    `username` VARCHAR(256) NOT NULL,
    `password` VARCHAR(256) NULL,
    `status` INT(2) NOT NULL DEFAULT 1,
    `email` VARCHAR(256) NULL,
    `address_street` VARCHAR(256) NULL,
    `address_house_number` VARCHAR(256) NULL,
    `address_city` VARCHAR(256) NULL,
    `address_zip_code` VARCHAR(256) NULL,
    `address_country` VARCHAR(256) NULL,
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp(),
    `date_password_changed` DATETIME NOT NULL,
    `password_change_status` INT(2) NOT NULL DEFAULT 1,
    `default_user_page_url` VARCHAR(256) NULL,
    `date_updated` DATETIME NOT NULL DEFAULT current_timestamp(),
    `default_user_datetime_format` VARCHAR(256) NULL,
    `last_login_hash` VARCHAR(256) NULL
)

CREATE TABLE IF NOT EXISTS `documents` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_author` INT(32) NOT NULL,
    `id_officer` INT(32) NULL,
    `name` VARCHAR(256) NOT NULL,
    `status` INT(32) NOT NULL,
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp(),
    `id_manager` INT(32) NOT NULL,
    `id_group` INT(32) NOT NULL,
    `is_deleted` INT(2) NOT NULL DEFAULT 0,
    `rank` VARCHAR(256) NOT NULL,
    `id_folder` INT(32) NULL,
    `file` VARCHAR(256) NULL,
    `shred_year` VARCHAR(4) NOT NULL,
    `after_shred_action` VARCHAR(256) NOT NULL,
    `shredding_status` INT(32) NOT NULL,
    `date_updated` DATETIME NOT NULL DEFAULT current_timestamp(),
    `id_archive_document` INT(32) NULL,
    `id_archive_box` INT(32) NULL,
    `id_archive_archive` INT(32) NULL
)

CREATE TABLE IF NOT EXISTS `user_bulk_rights` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_user` INT(32) NOT NULL,
    `action_name` VARCHAR(256) NOT NULL,
    `is_executable` INT(2) DEFAULT 0
)

CREATE TABLE IF NOT EXISTS `groups` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(256) NOT NULL,
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp(),
    `code` VARCHAR(256) NULL
)

CREATE TABLE IF NOT EXISTS `group_users` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_user` INT(32) NOT NULL,
    `id_group` INT(32) NOT NULL,
    `is_manager` INT(2) NOT NULL DEFAULT 0
)

CREATE TABLE IF NOT EXISTS `processes` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_document` INT(32) NULL,
    `workflow1` INT(32) NULL,
    `workflow2` INT(32) NULL,
    `workflow3` INT(32) NULL,
    `workflow4` INT(32) NULL,
    `workflow_status` INT(32) NULL,
    `type` INT(2) NOT NULL,
    `status` INT(2) NOT NULL DEFAULT 1,
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp(),
    `id_author` INT(32) NOT NULL,
    `date_updated` DATETIME NOT NULL DEFAULT current_timestamp(),
    `is_archive` INT(2) NOT NULL DEFAULT 0
)

CREATE TABLE IF NOT EXISTS `user_action_rights` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_user` INT(32) NOT NULL,
    `action_name` VARCHAR(256) NOT NULL,
    `is_executable` INT(2) DEFAULT 0
)

CREATE TABLE IF NOT EXISTS `group_action_rights` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_group` INT(32) NOT NULL,
    `action_name` VARCHAR(256) NOT NULL,
    `is_executable` INT(2) DEFAULT 0
)

CREATE TABLE IF NOT EXISTS `group_bulk_rights` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_group` INT(32) NOT NULL,
    `action_name` VARCHAR(256) NOT NULL,
    `is_executable` INT(2) DEFAULT 0
)

CREATE TABLE IF NOT EXISTS `metadata` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(256) NOT NULL,
    `text` VARCHAR(256) NOT NULL,
    `table_name` VARCHAR(256) NOT NULL,
    `is_system` INT(2) NOT NULL DEFAULT 0,
    `input_type` VARCHAR(256) NOT NULL,
    `length` VARCHAR(256) NOT NULL,
    `select_external_enum_name` VARCHAR(256) NULL,
    `is_readonly` INT(2) NOT NULL DEFAULT 0
)

CREATE TABLE IF NOT EXISTS `metadata_values` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_metadata` INT(32) NOT NULL,
    `name` VARCHAR(256) NOT NULL,
    `value` VARCHAR(256) NOT NULL,
    `is_default` INT(2) NOT NULL DEFAULT 0
)

CREATE TABLE IF NOT EXISTS `user_metadata_rights` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_metadata` INT(32) NOT NULL,
    `id_user` INT(32) NOT NULL,
    `view` INT(2) NOT NULL DEFAULT 0,
    `edit` INT(2) NOT NULL DEFAULT 0,
    `view_values` INT(2) NOT NULL DEFAULT 0,
    `edit_values` INT(2) NOT NULL DEFAULT 0
)

CREATE TABLE IF NOT EXISTS `group_metadata_rights` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_metadata` INT(32) NOT NULL,
    `id_group` INT(32) NOT NULL,
    `view` INT(2) NOT NULL DEFAULT 0,
    `edit` INT(2) NOT NULL DEFAULT 0,
    `view_values` INT(2) NOT NULL DEFAULT 0,
    `edit_values` INT(2) NOT NULL DEFAULT 0
)

CREATE TABLE IF NOT EXISTS `folders` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_parent_folder` INT(32) NULL,
    `name` VARCHAR(256) NOT NULL,
    `description` VARCHAR(256) NULL,
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp(),
    `nest_level` INT(32) NOT NULL,
    `ordering` INT(32) NOT NULL
)

CREATE TABLE IF NOT EXISTS `service_config` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(256) NOT NULL,
    `key` VARCHAR(256) NOT NULL,
    `value` VARCHAR(256) NOT NULL
)

CREATE TABLE IF NOT EXISTS `document_comments` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_author` INT(32) NOT NULL,
    `id_document` INT(32) NOT NULL,
    `text` TEXT,
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp()
)

CREATE TABLE IF NOT EXISTS `process_comments` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_author` INT(32) NOT NULL,
    `id_process` INT(32) NOT NULL,
    `text` VARCHAR(256),
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp()
)

CREATE TABLE IF NOT EXISTS `user_widgets` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_user` INT(32) NOT NULL,
    `location` VARCHAR(256) NOT NULL,
    `widget_name` VARCHAR(256) NOT NULL,
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp()
)

CREATE TABLE IF NOT EXISTS `document_sharing` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_author` INT(32) NOT NULL,
    `id_user` INT(32) NOT NULL,
    `id_document` INT(32) NOT NULL,
    `date_from` DATETIME NOT NULL DEFAULT current_timestamp(),
    `date_to` DATETIME NOT NULL,
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp(),
    `hash` VARCHAR(256) NOT NULL
)

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_user` INT(32) NOT NULL,
    `text` TEXT NOT NULL,
    `status` INT(2) NOT NULL DEFAULT 1,
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp(),
    `action` VARCHAR(256) NOT NULL
)

CREATE TABLE IF NOT EXISTS `service_log` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(256) NOT NULL,
    `text` TEXT NOT NULL,
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp()
)

CREATE TABLE IF NOT EXISTS `mail_queue` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `recipient` VARCHAR(256) NOT NULL,
    `title` VARCHAR(256) NOT NULL,
    `body` TEXT NOT NULL,
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp()
)

CREATE TABLE IF NOT EXISTS `password_reset_hashes` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_user` INT(32) NOT NULL,
    `hash` VARCHAR(256),
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp()
)

CREATE TABLE IF NOT EXISTS `document_stats` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `total_count` INT(32) NOT NULL,
    `shredded_count` INT(32) NOT NULL,
    `archived_count` INT(32) NOT NULL,
    `new_count` INT(32) NOT NULL,
    `waiting_for_archivation_count` INT(32) NOT NULL,
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp()
)

CREATE TABLE IF NOT EXISTS `process_stats` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `total_count` INT(32) NOT NULL,
    `in_progress_count` INT(32) NOT NULL,
    `finished_count` INT(32) NOT NULL,
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp()
)

CREATE TABLE IF NOT EXISTS `ribbons` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_parent_ribbon` INT(32) NULL,
    `name` VARCHAR(256) NOT NULL,
    `code` VARCHAR(256) NOT NULL,
    `title` VARCHAR(256) NULL,
    `image` VARCHAR(256) NULL,
    `is_visible` INT(2) NOT NULL DEFAULT 1,
    `is_system` INT(2) NOT NULL DEFAULT 1,
    `page_url` VARCHAR(256) NOT NULL,
    `ribbon_right` INT(32) NOT NULL
)

CREATE TABLE IF NOT EXISTS `ribbon_user_rights` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_ribbon` INT(32) NOT NULL,
    `id_user` INT(32) NOT NULL,
    `can_see` INT(2) NOT NULL DEFAULT 0,
    `can_edit` INT(2) NOT NULL DEFAULT 0,
    `can_delete` INT(2) NOT NULL DEFAULT 0
)

CREATE TABLE IF NOT EXISTS `ribbon_group_rights` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_ribbon` INT(32) NOT NULL,
    `id_group` INT(32) NOT NULL,
    `can_see` INT(2) NOT NULL DEFAULT 0,
    `can_edit` INT(2) NOT NULL DEFAULT 0,
    `can_delete` INT(2) NOT NULL DEFAULT 0
)

CREATE TABLE IF NOT EXISTS `document_filters` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_author` INT(32) NULL,
    `name` VARCHAR(256) NOT NULL,
    `description` VARCHAR(256) NULL,
    `filter_sql` TEXT NOT NULL,
    `has_ordering` INT(2) NOT NULL DEFAULT 0
)

CREATE TABLE IF NOT EXISTS `user_connections` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_user1` INT(32) NOT NULL,
    `id_user2` INT(32) NOT NULL
)

CREATE TABLE IF NOT EXISTS `archive_documents` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp(),
    `name` VARCHAR(256) NOT NULL,
    `id_parent_archive_entity` INT(32) NULL,
    `status` INT(2) NOT NULL DEFAULT 1
)

CREATE TABLE IF NOT EXISTS `archive_boxes` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp(),
    `name` VARCHAR(256) NOT NULL,
    `id_parent_archive_entity` INT(32) NULL,
    `status` INT(2) NOT NULL DEFAULT 1
)

CREATE TABLE IF NOT EXISTS `archive_archives` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp(),
    `name` VARCHAR(256) NOT NULL,
    `id_parent_archive_entity` INT(32) NULL,
    `status` INT(2) NOT NULL DEFAULT 1
)

CREATE TABLE IF NOT EXISTS `document_reports` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `sql_string` TEXT NOT NULL,
    `id_user` INT(32) NOT NULL,
    `status` INT(2) NOT NULL DEFAULT 1,
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp(),
    `date_updated` DATETIME NOT NULL DEFAULT current_timestamp(),
    `file_src` VARCHAR(256) NULL,
    `file_format` VARCHAR(256) NOT NULL,
    `file_name` VARCHAR(256) NULL,
    `id_file_storage_location` INT(32) NULL
)

CREATE TABLE IF NOT EXISTS `file_storage_locations` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(256) NOT NULL,
    `path` VARCHAR(256) NOT NULL,
    `is_default` INT(2) NOT NULL DEFAULT 0,
    `is_active` INT(2) NOT NULL DEFAULT 1,
    `order` INT(32) NOT NULL,
    `is_system` INT(2) NOT NULL DEFAULT 0,
    `type` VARCHAR(256) NOT NULL,
    `absolute_path` VARCHAR(256) NOT NULL
)

CREATE TABLE IF NOT EXISTS `calendar_events` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `title` VARCHAR(256) NOT NULL,
    `color` VARCHAR(256) NOT NULL,
    `tag` VARCHAR(256) NULL,
    `date_from` VARCHAR(256) NOT NULL,
    `date_to` VARCHAR(256) NULL,
    `time` VARCHAR(256) NOT NULL,
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp()
)

CREATE TABLE IF NOT EXISTS `db_transaction_log` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_calling_user` INT(32) NULL,
    `time_taken` VARCHAR(256) NOT NULL,
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp()
)

CREATE TABLE IF NOT EXISTS `services` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `system_name` VARCHAR(256) NOT NULL,
    `display_name` VARCHAR(256) NOT NULL,
    `description` VARCHAR(256) NOT NULL,
    `is_enabled` INT(2) NOT NULL DEFAULT 1,
    `is_system` INT(2) NOT NULL DEFAULT 0,
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp()
)

CREATE TABLE IF NOT EXISTS `document_metadata_history` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_user` INT(32) NOT NULL,
    `id_document` INT(32) NOT NULL,
    `metadata_name` VARCHAR(256) NOT NULL,
    `metadata_value` VARCHAR(256) NULL,
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp()
)

CREATE TABLE IF NOT EXISTS `document_locks` (
    `id` INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `id_document` INT(32) NOT NULL,
    `id_user` INT(32) NULL,
    `id_process` INT(32) NULL,
    `description` TEXT NOT NULL,
    `status` INT(2) NOT NULL DEFAULT 1,
    `date_created` DATETIME NOT NULL DEFAULT current_timestamp(),
    `date_updated` DATETIME NOT NULL DEFAULT current_timestamp()
)
