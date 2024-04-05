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

-- USER GROUP RELATIONS
