-- USERS
INSERT INTO `users` (`firstname`, `lastname`, `password`, `username`)
SELECT 'Service', 'User', '$2y$10$Eb34bkiy.Gq/YxkSOyzZYe8egq70wdZTmH56ftMzmGSyBXZFwd9sG', 'service_user'
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username` = 'service_user')

INSERT INTO `users` (`firstname`, `lastname`, `password`, `username`)
SELECT 'Administrator', ' ', '$2y$10$1UNSYh.T5ft0d3HFuyTpJ.i6rIM9DQxoA7Viri1JoyNbWQ15FHVJK', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username` = 'admin')

-- GROUPS
INSERT INTO `groups` (`name`, `code`)
SELECT 'Administrators', 'ADMINISTRATORS'
WHERE NOT EXISTS (SELECT 1 FROM `groups` WHERE `code` = 'ADMINISTRATORS')

INSERT INTO `groups` (`name`, `code`)
SELECT 'Archive Manager', 'ARCHMAN'
WHERE NOT EXISTS (SELECT 1 FROM `groups` WHERE `code` = 'ARCHMAN')

-- METADATA
INSERT INTO `metadata` (`name`, `text`, `table_name`, `is_system`, `input_type`, `length`)
VALUES ('rank', 'Rank', 'documents', '1', 'select', '256')

INSERT INTO `metadata` (`name`, `text`, `table_name`, `is_system`, `input_type`, `length`)
VALUES ('status', 'Status', 'documents', '1', 'select', '256')

INSERT INTO `metadata` (`name`, `text`, `table_name`, `is_system`, `input_type`, `length`)
VALUES ('after_shred_action', 'Action after shredding', 'documents', '1', 'select', '256')

INSERT INTO `metadata` (`name`, `text`, `table_name`, `is_system`, `input_type`, `length`)
VALUES ('shredding_status', 'Shredding status', 'documents', '1', 'select', '256')

INSERT INTO `metadata` (`name`, `text`, `table_name`, `is_system`, `input_type`, `length`)
VALUES ('status', 'Status', 'users', '1', 'select', '256')

INSERT INTO `metadata` (`name`, `text`, `table_name`, `is_system`, `input_type`, `length`)
VALUES ('status', 'Status', 'processes', '1', 'select', '256')

INSERT INTO `metadata` (`name`, `text`, `table_name`, `is_system`, `input_type`, `length`)
VALUES ('type', 'Type', 'processes', '1', 'select', '256')

INSERT INTO `metadata` (`name`, `text`, `table_name`, `is_system`, `input_type`, `length`)
VALUES ('status', 'Status', 'archive', '1', 'select', '256')

INSERT INTO `metadata` (`name`, `text`, `table_name`, `is_system`, `input_type`, `length`)
VALUES ('type', 'Type', 'archive', '1', 'select', '256')

-- SERVICE CONFIG
INSERT INTO `service_config` (`name`, `key`, `value`)
VALUES ('LogRotateService', 'files_keep_length', '7')

INSERT INTO `service_config` (`name`, `key`, `value`)
VALUES ('LogRotateService', 'service_run_period', '7')

INSERT INTO `service_config` (`name`, `key`, `value`)
VALUES ('LogRotateService', 'archive_old_logs', '1')

INSERT INTO `service_config` (`name`, `key`, `value`)
VALUES ('PasswordPolicyService', 'password_change_period', '30')

INSERT INTO `service_config` (`name`, `key`, `value`)
VALUES ('PasswordPolicyService', 'password_change_force_administrators', '0')

INSERT INTO `service_config` (`name`, `key`, `value`)
VALUES ('PasswordPolicyService', 'password_change_force', '0')

INSERT INTO `service_config` (`name`, `key`, `value`)
VALUES ('PasswordPolicyService', 'service_run_period', '30')

INSERT INTO `service_config` (`name`, `key`, `value`)
VALUES ('NotificationManagerService', 'notification_keep_length', '1')

INSERT INTO `service_config` (`name`, `key`, `value`)
VALUES ('NotificationManagerService', 'service_run_period', '7')

INSERT INTO `service_config` (`name`, `key`, `value`)
VALUES ('NotificationManagerService', 'notification_keep_unseen_service_user', '1')

INSERT INTO `service_config` (`name`, `key`, `value`)
VALUES ('CacheRotateService', 'service_run_period', '1')

INSERT INTO `service_config` (`name`, `key`, `value`)
VALUES ('FileManagerService', 'service_run_period', '30')

INSERT INTO `service_config` (`name`, `key`, `value`)
VALUES ('ShreddingSuggestionService', 'service_run_period', '30')

INSERT INTO `service_config` (`name`, `key`, `value`)
VALUES ('MailService', 'service_run_period', '1')

INSERT INTO `service_config` (`name`, `key`, `value`)
VALUES ('DocumentArchivationService', 'service_run_period', '7')

INSERT INTO `service_config` (`name`, `key`, `value`)
VALUES ('DeclinedDocumentRemoverService', 'service_run_period', '30')

INSERT INTO `service_config` (`name`, `key`, `value`)
VALUES ('DocumentReportGeneratorService', 'service_run_period', '1')