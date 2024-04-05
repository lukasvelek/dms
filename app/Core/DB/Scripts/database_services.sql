INSERT INTO `services` (`system_name`, `display_name`, `description`, `is_enabled`, `is_system`)
VALUES ('LogRotateService', 'Log rotate', 'Deletes old log files', '1', '1')

INSERT INTO `services` (`system_name`, `display_name`, `description`, `is_enabled`, `is_system`)
VALUES ('CacheRotateService', 'Cache rotate', 'Deletes old cache files', '1', '1')

INSERT INTO `services` (`system_name`, `display_name`, `description`, `is_enabled`, `is_system`)
VALUES ('FileManagerService', 'File manager', 'Deletes old unused files', '1', '1')

INSERT INTO `services` (`system_name`, `display_name`, `description`, `is_enabled`, `is_system`)
VALUES ('ShreddingSuggestionService', 'Shredding suggestion', 'Suggests documents for shredding', '1', '1')

INSERT INTO `services` (`system_name`, `display_name`, `description`, `is_enabled`, `is_system`)
VALUES ('PasswordPolicyService', 'Password policy', 'Checks if passwords have been changed in a period of time', '1', '1')

INSERT INTO `services` (`system_name`, `display_name`, `description`, `is_enabled`, `is_system`)
VALUES ('MailService', 'Mail service', 'Service responsible for sending emails', '1', '1')

INSERT INTO `services` (`system_name`, `display_name`, `description`, `is_enabled`, `is_system`)
VALUES ('NotificationManagerService', 'Notification manager', 'Service responsible for deleting old notifications', '1', '1')

INSERT INTO `services` (`system_name`, `display_name`, `description`, `is_enabled`, `is_system`)
VALUES ('DocumentArchivationService', 'Document archivator', 'Archives documents waiting for archivation', '1', '1')

INSERT INTO `services` (`system_name`, `display_name`, `description`, `is_enabled`, `is_system`)
VALUES ('DeclinedDocumentRemoverService', 'Declined document remover', 'Deletes declined documents', '1', '1')

INSERT INTO `services` (`system_name`, `display_name`, `description`, `is_enabled`, `is_system`)
VALUES ('DocumentReportGeneratorService', 'Document report generator', 'Generates document reports', '1', '1')