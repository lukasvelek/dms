<?php

namespace DMS\Core\DB;

use DMS\Authenticators\UserAuthenticator;
use DMS\Constants\BulkActionRights;
use DMS\Constants\DocumentAfterShredActions;
use DMS\Constants\DocumentRank;
use DMS\Constants\DocumentShreddingStatus;
use DMS\Constants\DocumentStatus;
use DMS\Constants\PanelRights;
use DMS\Constants\ProcessStatus;
use DMS\Constants\ProcessTypes;
use DMS\Constants\UserActionRights;
use DMS\Constants\UserStatus;
use DMS\Core\CryptManager;
use DMS\Core\Logger\Logger;

class DatabaseInstaller {
    private Database $db;
    private Logger $logger;

    public const DEFAULT_USERS = array(
        'admin',
        'service_user'
    );

    public function __construct(Database $db, Logger $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }

    public function install() {
        $this->createTables();
        $this->insertDefaultUsers();
        $this->insertDefaultGroups();
        $this->insertDefaultUserGroups();
        $this->insertDefaultMetadata();

        //$this->updateDefaultUserPanelRights();

        $this->insertDefaultUserPanelRights();
        $this->insertDefaultUserBulkActionRights();
        $this->insertDefaultUserActionRights();
        $this->insertDefaultUserMetadataRights();

        $this->insertDefaultGroupPanelRights();
        $this->insertDefaultGroupBulkActionRights();
        $this->insertDefaultGroupActionRights();
        $this->insertDefaultGroupMetadataRights();

        $this->insertDefaultServiceConfig();

        $this->insertDefaultRibbons();
        $this->insertDefaultRibbonGroupRights();
        $this->insertDefaultRibbonUserRights();
    }

    public function updateDefaultUserRights() {
        $this->insertDefaultUserPanelRights();
        $this->insertDefaultUserBulkActionRights();
        $this->insertDefaultUserActionRights();
    }

    private function createTables() {
        $tables = array(
            'users' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'firstname' => 'VARCHAR(256) NOT NULL',
                'lastname' => 'VARCHAR(256) NOT NULL',
                'username' => 'VARCHAR(256) NOT NULL',
                'password' => 'VARCHAR(256) NULL',
                'status' => 'INT(2) NOT NULL DEFAULT 1',
                'email' => 'VARCHAR(256) NULL',
                'address_street' => 'VARCHAR(256) NULL',
                'address_house_number' => 'VARCHAR(256) NULL',
                'address_city' => 'VARCHAR(256) NULL',
                'address_zip_code' => 'VARCHAR(256) NULL',
                'address_country' => 'VARCHAR(256) NULL',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'date_password_changed' => 'DATETIME NOT NULL',
                'password_change_status' => 'INT(2) NOT NULL DEFAULT 1',
                'default_user_page_url' => 'VARCHAR(256) NULL',
                'date_updated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ),
            'user_panel_rights' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_user' => 'INT(32) NOT NULL',
                'panel_name' => 'VARCHAR(256) NOT NULL',
                'is_visible' => 'INT(2) DEFAULT 0'
            ),
            'documents' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_author' => 'INT(32) NOT NULL',
                'id_officer' => 'INT(32) NULL',
                'name' => 'VARCHAR(256) NOT NULL',
                'status' => 'INT(32) NOT NULL',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'id_manager' => 'INT(32) NOT NULL',
                'id_group' => 'INT(32) NOT NULL',
                'is_deleted' => 'INT(2) NOT NULL DEFAULT 0',
                'rank' => 'VARCHAR(256) NOT NULL',
                'id_folder' => 'INT(32) NULL',
                'file' => 'VARCHAR(256) NULL',
                'shred_year' => 'VARCHAR(4) NOT NULL',
                'after_shred_action' => 'VARCHAR(256) NOT NULL',
                'shredding_status' => 'INT(32) NOT NULL',
                'date_updated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ),
            'user_bulk_rights' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_user' => 'INT(32) NOT NULL',
                'action_name' => 'VARCHAR(256) NOT NULL',
                'is_executable' => 'INT(2) DEFAULT 0'
            ),
            'groups' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'name' => 'VARCHAR(256) NOT NULL',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'code' => 'VARCHAR(256) NULL'
            ),
            'group_users' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_user' => 'INT(32) NOT NULL',
                'id_group' => 'INT(32) NOT NULL',
                'is_manager' => 'INT(2) NOT NULL DEFAULT 0'
            ),
            'processes' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_document' => 'INT(32) NULL',
                'workflow1' => 'INT(32) NULL',
                'workflow2' => 'INT(32) NULL',
                'workflow3' => 'INT(32) NULL',
                'workflow4' => 'INT(32) NULL',
                'workflow_status' => 'INT(32) NULL',
                'type' => 'INT(2) NOT NULL',
                'status' => 'INT(2) NOT NULL DEFAULT 1',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'id_author' => 'INT(32) NOT NULL',
                'date_updated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ),
            'user_action_rights' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_user' => 'INT(32) NOT NULL',
                'action_name' => 'VARCHAR(256) NOT NULL',
                'is_executable' => 'INT(2) DEFAULT 0'
            ),
            'group_action_rights' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_group' => 'INT(32) NOT NULL',
                'action_name' => 'VARCHAR(256) NOT NULL',
                'is_executable' => 'INT(2) DEFAULT 0'
            ),
            'group_panel_rights' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_group' => 'INT(32) NOT NULL',
                'panel_name' => 'VARCHAR(256) NOT NULL',
                'is_visible' => 'INT(2) DEFAULT 0'
            ),
            'group_bulk_rights' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_group' => 'INT(32) NOT NULL',
                'action_name' => 'VARCHAR(256) NOT NULL',
                'is_executable' => 'INT(2) DEFAULT 0'
            ),
            'metadata' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'name' => 'VARCHAR(256) NOT NULL',
                'text' => 'VARCHAR(256) NOT NULL',
                'table_name' => 'VARCHAR(256) NOT NULL',
                'is_system' => 'INT(2) NOT NULL DEFAULT 0',
                'input_type' => 'VARCHAR(256) NOT NULL',
                'length' => 'VARCHAR(256) NOT NULL',
                'select_external_enum_name' => 'VARCHAR(256) NULL'
            ),
            'metadata_values' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_metadata' => 'INT(32) NOT NULL',
                'name' => 'VARCHAR(256) NOT NULL',
                'value' => 'VARCHAR(256) NOT NULL'
            ),
            'user_metadata_rights' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_metadata' => 'INT(32) NOT NULL',
                'id_user' => 'INT(32) NOT NULL',
                'view' => 'INT(2) NOT NULL DEFAULT 0',
                'edit' => 'INT(2) NOT NULL DEFAULT 0',
                'view_values' => 'INT(2) NOT NULL DEFAULT 0',
                'edit_values' => 'INT(2) NOT NULL DEFAULT 0'
            ),
            'group_metadata_rights' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_metadata' => 'INT(32) NOT NULL',
                'id_group' => 'INT(32) NOT NULL',
                'view' => 'INT(2) NOT NULL DEFAULT 0',
                'edit' => 'INT(2) NOT NULL DEFAULT 0',
                'view_values' => 'INT(2) NOT NULL DEFAULT 0',
                'edit_values' => 'INT(2) NOT NULL DEFAULT 0'
            ),
            'folders' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_parent_folder' => 'INT(32) NULL',
                'name' => 'VARCHAR(256) NOT NULL',
                'description' => 'VARCHAR(256) NULL',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'nest_level' => 'INT(32) NOT NULL'
            ),
            'service_config' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'name' => 'VARCHAR(256) NOT NULL',
                'key' => 'VARCHAR(256) NOT NULL',
                'value' => 'VARCHAR(256) NOT NULL'
            ),
            'document_comments' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_author' => 'INT(32) NOT NULL',
                'id_document' => 'INT(32) NOT NULL',
                'text' => 'VARCHAR(32768)',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ),
            'process_comments' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_author' => 'INT(32) NOT NULL',
                'id_process' => 'INT(32) NOT NULL',
                'text' => 'VARCHAR(256)',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ),
            'user_widgets' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_user' => 'INT(32) NOT NULL',
                'location' => 'VARCHAR(256) NOT NULL',
                'widget_name' => 'VARCHAR(256) NOT NULL',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ),
            'document_sharing' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_author' => 'INT(32) NOT NULL',
                'id_user' => 'INT(32) NOT NULL',
                'id_document' => 'INT(32) NOT NULL',
                'date_from' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'date_to' => 'DATETIME NOT NULL',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'hash' => 'VARCHAR(256) NOT NULL'
            ),
            'notifications' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_user' => 'INT(32) NOT NULL',
                'text' => 'VARCHAR(32768) NOT NULL',
                'status' => 'INT(2) NOT NULL DEFAULT 1',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'action' => 'VARCHAR(256) NOT NULL'
            ),
            'service_log' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'name' => 'VARCHAR(256) NOT NULL',
                'text' => 'VARCHAR(32768) NOT NULL',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ),
            'mail_queue' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'recipient' => 'VARCHAR(256) NOT NULL',
                'title' => 'VARCHAR(256) NOT NULL',
                'body' => 'VARCHAR(32768) NOT NULL',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ),
            'password_reset_hashes' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_user' => 'INT(32) NOT NULL',
                'hash' => 'VARCHAR(256)',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ),
            'document_stats' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'total_count' => 'INT(32) NOT NULL',
                'shredded_count' => 'INT(32) NOT NULL',
                'archived_count' => 'INT(32) NOT NULL',
                'new_count' => 'INT(32) NOT NULL',
                'waiting_for_archivation_count' => 'INT(32) NOT NULL',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ),
            'process_stats' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'total_count' => 'INT(32) NOT NULL',
                'in_progress_count' => 'INT(32) NOT NULL',
                'finished_count' => 'INT(32) NOT NULL',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ),
            'ribbons' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_parent_ribbon' => 'INT(32) NULL',
                'name' => 'VARCHAR(256) NOT NULL',
                'code' => 'VARCHAR(256) NOT NULL',
                'title' => 'VARCHAR(256) NULL',
                'image' => 'VARCHAR(256) NULL',
                'is_visible' => 'INT(2) NOT NULL DEFAULT 1',
                'is_system' => 'INT(2) NOT NULL DEFAULT 1',
                'page_url' => 'VARCHAR(256) NOT NULL'
            ),
            'ribbon_user_rights' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_ribbon' => 'INT(32) NOT NULL',
                'id_user' => 'INT(32) NOT NULL',
                'can_see' => 'INT(2) NOT NULL DEFAULT 0',
                'can_edit' => 'INT(2) NOT NULL DEFAULT 0',
                'can_delete' => 'INT(2) NOT NULL DEFAULT 0'
            ),
            'ribbon_group_rights' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_ribbon' => 'INT(32) NOT NULL',
                'id_group' => 'INT(32) NOT NULL',
                'can_see' => 'INT(2) NOT NULL DEFAULT 0',
                'can_edit' => 'INT(2) NOT NULL DEFAULT 0',
                'can_delete' => 'INT(2) NOT NULL DEFAULT 0'
            ),
            'document_filters' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_author' => 'INT(32) NULL',
                'name' => 'VARCHAR(256) NOT NULL',
                'description' => 'VARCHAR(256) NULL',
                'filter_sql' => 'VARCHAR(32768) NOT NULL',
                'has_ordering' => 'INT(2) NOT NULL DEFAULT 0'
            )
        );

        foreach($tables as $table => $columns) {
            $col = '';

            $i = 0;
            foreach($columns as $columnName => $columnValue) {
                $column = '`' . $columnName . '` ' . $columnValue;

                if(($i + 1) == count($columns)) {
                    $col .= $column;
                } else {
                    $col .= $column . ', ';
                }

                $i++;
            }

            $sql = 'CREATE TABLE IF NOT EXISTS `' . $table . '` (' . $col . ')';

            $this->logger->sql($sql, __METHOD__);

            $this->db->query($sql);
        }

        return true;
    }

    private function insertDefaultUsers() {
        $defaultUsersUsernames = array('service_user', 'admin');
        $insertUsers = array();

        $defaultUserData = array(
            'service_user' => array(
                'firstname' => 'Service',
                'lastname' => 'User',
                'password' => 'service_user'
            ),
            'admin' => array(
                'firstname' => 'Admin',
                'lastname' => 'istrator',
                'password' => 'admin'
            )
        );

        $sql = 'SELECT * FROM `users`';
        $rows = $this->db->query($sql);

        if($rows->num_rows > 0) {
            foreach($rows as $row) {
                if(!in_array($row['username'], $defaultUsersUsernames)) {
                    $insertUsers[] = $row['username'];
                }
            }
        } else {
            $insertUsers = $defaultUsersUsernames;
        }

        foreach($insertUsers as $iu) {
            $userData = $defaultUserData[$iu];
            $password = CryptManager::hashPassword($userData['password'], $iu);
            $firstname = $userData['firstname'];
            $lastname = $userData['lastname'];
            $username = $iu;
            $datePasswordChanged = date('Y-m-d H:i:s');

            $sql = "INSERT INTO `users` (`firstname`, `lastname`, `username`, `password`, `date_password_changed`)
                    VALUES ('$firstname', '$lastname', '$username', '$password', '$datePasswordChanged')";

            $this->logger->sql($sql, __METHOD__);

            $this->db->query($sql);
        }

        return true;
    }

    private function insertDefaultGroups() {
        $defaultGroups = array(
            'ARCHMAN' => 'Archive Manager',
            'ADMINISTRATORS' => 'Administrators'
        );

        $insertGroups = array();

        $sql = 'SELECT * FROM `groups`';
        $rows = $this->db->query($sql);

        if($rows->num_rows > 0) {
            foreach($rows as $row) {
                if(!array_key_exists($row['code'], $defaultGroups)) {
                    $insertGroups[$row['code']] = $row['name'];
                }
            }
        } else {
            $insertGroups = $defaultGroups;
        }

        foreach($insertGroups as $code => $name) {
            $sql = "INSERT INTO `groups` (`name`, `code`) VALUES ('$name', '$code')";
            
            $this->logger->sql($sql, __METHOD__);

            $this->db->query($sql);
        }

        return true;
    }

    private function insertDefaultUserGroups() {
        $groupCodes = array(
            'ADMINISTRATORS',
            'ARCHMAN'
        );

        $groupUsers = array(
            'ARCHMAN' => array(
                'admin' => '1'
            ),
            'ADMINISTRATORS' => array(
                'admin' => '1'
            )
        );

        $idGroup = null;
        $idUser = null;

        foreach($groupCodes as $groupCode) {
            $idGroup = null;
            $idUser = null;

            $sql = "SELECT * FROM `groups` WHERE `code` = '$groupCode'";
            $rows = $this->db->query($sql);

            if($rows->num_rows > 0) {
                foreach($rows as $row) {
                    $idGroup = $row['id'];
                }
            }

            if($idGroup != NULL) {
                foreach($groupUsers[$groupCode] as $user => $isManager) {
                    $sql = "SELECT `id` FROM `users` WHERE `username` = '$user'";
                    $rows = $this->db->query($sql);

                    $idUser = null;

                    foreach($rows as $row) {
                        $idUser = $row['id'];
                    }

                    if($idUser == null) {
                        continue;
                    }

                    $sql = "INSERT INTO `group_users` (`id_user`, `id_group`, `is_manager`) VALUES ('$idUser', '$idGroup', '$isManager')";
                    $result = $this->db->query($sql);
                } 
            }
        }

        return true;
    }

    private function insertDefaultGroupPanelRights() {
        $idGroups = [];
        $panels = PanelRights::$all;

        $sql = "SELECT `id`, `code` FROM `groups`";

        $this->logger->sql($sql, __METHOD__);

        $rows = $this->db->query($sql);

        $allowPanels = [];

        foreach($rows as $row) {
            $idGroups[] = $row['id'];

            switch($row['code']) {
                case 'ARCHMAN':
                    $allowPanels[$row['id']] = array(
                        PanelRights::DOCUMENTS,
                        PanelRights::PROCESSES
                    );
                    break;
                
                case 'ADMINISTRATORS':
                    $allowPanels[$row['id']] = $panels;
                    break;
            }
        }

        foreach($idGroups as $id) {
            foreach($panels as $panel) {
                if(in_array($panel, $allowPanels[$id])) {
                    // allow
                    $sql = "INSERT INTO `group_panel_rights` (`id_group`, `panel_name`, `is_visible`) VALUES ('$id', '$panel', '1')";
                } else {
                    // deny
                    $sql = "INSERT INTO `group_panel_rights` (`id_group`, `panel_name`, `is_visible`) VALUES ('$id', '$panel', '0')";
                }

                $this->logger->sql($sql, __METHOD__);

                $this->db->query($sql);
            }
        }
    }

    private function insertDefaultUserPanelRights() {
        $idUsers = [];
        $panels = PanelRights::$all;

        $userPanels = [];
        $dbUserPanels = [];

        $sql = 'SELECT * FROM `users`';

        $this->logger->sql($sql, __METHOD__);

        $rows = $this->db->query($sql);

        if($rows->num_rows > 0) {
            foreach($rows as $row) {
                if(in_array($row['username'], self::DEFAULT_USERS)) {
                    $idUsers[] = $row['id'];
                }
            }
        }

        $sql = 'SELECT * FROM `user_panel_rights`';

        $rows = $this->db->query($sql);

        if($rows->num_rows > 0) {
            foreach($rows as $row) {
                $dbUserPanels[$row['id_user']][] = $row['panel_name'];
            }
        }

        foreach($panels as $panel) {
            if(empty($dbUserPanels)) {
                foreach($idUsers as $id) {
                    $userPanels[$id][] = $panel;
                }
            } else {
                foreach($dbUserPanels as $id => $dupanels) {
                    if(!in_array($panel, $dupanels)) {
                        $userPanels[$id][] = $panel;
                    }
                }
            }
        }

        foreach($userPanels as $id => $upanels) {
            foreach($upanels as $upanel) {
                $sql = "INSERT INTO `user_panel_rights` (`id_user`, `panel_name`, `is_visible`)
                VALUES ('$id', '$upanel', '1')";

                $this->logger->sql($sql, __METHOD__);

                $this->db->query($sql);
            }
        }
    }

    private function insertDefaultGroupBulkActionRights() {
        $idGroups = [];
        $actions = BulkActionRights::$all;

        $sql = "SELECT `id`, `code` FROM `groups`";

        $this->logger->sql($sql, __METHOD__);

        $rows = $this->db->query($sql);

        $allowActions = [];

        if($rows->num_rows > 0) {
            foreach($rows as $row) {
                $idGroups[] = $row['id'];

                switch($row['code']) {
                    case 'ARCHMAN':
                        $allowActions[$row['id']] = $actions;
                        break;

                    case 'ADMINISTRATORS':
                        $allowActions[$row['id']] = $actions;
                        break;
                }
            }
        }

        foreach($idGroups as $id) {
            foreach($actions as $action) {
                if(in_array($action, $allowActions[$id])) {
                    // allow
                    $sql = "INSERT INTO `group_bulk_rights` (`id_group`, `action_name`, `is_executable`) VALUES ('$id', '$action', '1')";
                } else {
                    // deny
                    $sql = "INSERT INTO `group_bulk_rights` (`id_group`, `action_name`, `is_executable`) VALUES ('$id', '$action', '0')";
                }

                $this->logger->sql($sql, __METHOD__);

                $this->db->query($sql);
            }
        }
    }

    private function insertDefaultUserBulkActionRights() {
        $idUsers = array();
        $actions = BulkActionRights::$all;

        $userActions = array();
        $dbUserActions = array();

        $sql = 'SELECT * FROM `users`';

        $this->logger->sql($sql, __METHOD__);

        $rows = $this->db->query($sql);

        if($rows->num_rows > 0) {
            foreach($rows as $row) {
                if(in_array($row['username'], self::DEFAULT_USERS)) {
                    $idUsers[] = $row['id'];
                }
            }
        }

        $sql = 'SELECT * FROM `user_bulk_rights`';

        $rows = $this->db->query($sql);

        if($rows->num_rows > 0) {
            foreach($rows as $row) {
                $dbUserActions[$row['id_user']][] = $row['action_name'];
            }
        }

        foreach($actions as $action) {
            if(empty($dbUserActions)) {
                foreach($idUsers as $id) {
                    $userActions[$id][] = $action;
                }
            } else {
                foreach($dbUserActions as $id => $duactions) {
                    if(!in_array($action, $duactions)) {
                        $userActions[$id][] = $action;
                    }
                }
            }
        }

        foreach($userActions as $id => $uactions) {
            foreach($uactions as $uaction) {
                $sql = "INSERT INTO `user_bulk_rights` (`id_user`, `action_name`, `is_executable`)
                VALUES ('$id', '$uaction', '1')";

                $this->logger->sql($sql, __METHOD__);

                $this->db->query($sql);
            }
        }
    }

    private function insertDefaultGroupActionRights() {
        $idGroups = [];
        $actions = UserActionRights::$all;

        $sql = "SELECT `id`, `code` FROM `groups`";

        $this->logger->sql($sql, __METHOD__);

        $rows = $this->db->query($sql);

        $allowActions = [];

        foreach($rows as $row) {
            $idGroups[] = $row['id'];

            switch($row['code']) {
                case 'ARCHMAN':
                    $allowActions[$row['id']] = [];
                    break;

                case 'ADMINISTRATORS':
                    $allowActions[$row['id']] = $actions;
                    break;
            }
        }

        foreach($idGroups as $id) {
            foreach($actions as $action) {
                if(in_array($action, $allowActions[$id])) {
                    // allow
                    $sql = "INSERT INTO `group_action_rights` (`id_group`, `action_name`, `is_executable`) VALUES ('$id', '$action', '1')";
                } else {
                    // deny
                    $sql = "INSERT INTO `group_action_rights` (`id_group`, `action_name`, `is_executable`) VALUES ('$id', '$action', '0')";
                }

                $this->logger->sql($sql, __METHOD__);

                $this->db->query($sql);
            }
        }
    }

    private function insertDefaultUserActionRights() {
        $idUsers = array();
        $actions = UserActionRights::$all;

        $userActions = array();
        $dbUserActions = array();

        $sql = 'SELECT * FROM `users`';

        $this->logger->sql($sql, __METHOD__);

        $rows = $this->db->query($sql);

        if($rows->num_rows > 0) {
            foreach($rows as $row) {
                if(in_array($row['username'], self::DEFAULT_USERS)) {
                    $idUsers[] = $row['id'];
                }
            }
        }

        $sql = 'SELECT * FROM `user_action_rights`';

        $rows = $this->db->query($sql);

        if($rows->num_rows > 0) {
            foreach($rows as $row) {
                $dbUserActions[$row['id_user']][] = $row['action_name'];
            }
        }

        foreach($actions as $action) {
            if(empty($dbUserActions)) {
                foreach($idUsers as $id) {
                    $userActions[$id][] = $action;
                }
            } else {
                foreach($dbUserActions as $id => $duactions) {
                    if(!in_array($action, $duactions)) {
                        $userActions[$id][] = $action;
                    }
                }
            }
        }

        foreach($userActions as $id => $uactions) {
            foreach($uactions as $uaction) {
                $sql = "INSERT INTO `user_action_rights` (`id_user`, `action_name`, `is_executable`)
                VALUES ('$id', '$uaction', '1')";

                $this->logger->sql($sql, __METHOD__);

                $this->db->query($sql);
            }
        }
    }

    public function insertDefaultMetadata() {
        $metadata = array(
            array(
                'table_name' => 'documents',
                'name' => 'rank',
                'text' => 'Rank',
                'input_type' => 'select',
                'length' => '256'
            ),
            array(
                'table_name' => 'documents',
                'name' => 'status',
                'text' => 'Status',
                'input_type' => 'select',
                'length' => '256'
            ),
            array(
                'table_name' => 'users',
                'name' => 'status',
                'text' => 'Status',
                'input_type' => 'select',
                'length' => '256'
            ),
            array(
                'table_name' => 'processes',
                'name' => 'status',
                'text' => 'Status',
                'input_type' => 'select',
                'length' => '256'
            ),
            array(
                'table_name' => 'processes',
                'name' => 'type',
                'text' => 'Type',
                'input_type' => 'select',
                'length' => '256'
            ),
            array(
                'table_name' => 'documents',
                'name' => 'after_shred_action',
                'text' => 'Action after shredding',
                'input_type' => 'select',
                'length' => '256'
            ),
            array(
                'table_name' => 'documents',
                'name' => 'shredding_status',
                'text' => 'Shredding status',
                'input_type' => 'select',
                'length' => '256'
            )
        );

        $idsMetadata = [];
        
        foreach($metadata as $m) {
            $name = $m['name'];
            $tableName = $m['table_name'];
            $text = $m['text'];
            $inputType = $m['input_type'];
            $length = $m['length'];

            $sql = "INSERT INTO `metadata` (`name`, `text`, `table_name`, `is_system`, `input_type`, `length`)
                    VALUES ('$name', '$text', '$tableName', '1', '$inputType', '$length')";

            $this->logger->sql($sql, __METHOD__);
            $this->db->query($sql);

            $sql = "SELECT `id` FROM `metadata` WHERE `name` = '$name' AND `table_name` = '$tableName'";
            
            $this->logger->sql($sql, __METHOD__);

            $rows = $this->db->query($sql);

            foreach($rows as $row) {
                $idsMetadata[$tableName . '.' . $name] = $row['id'];
            }
        }

        foreach($idsMetadata as $name => $id) {
            $values = [];

            switch($name) {
                case 'documents.rank':
                    foreach(DocumentRank::$texts as $v => $n) {
                        $values[$id][] = array('name' => $n, 'value' => $v);
                    }

                    break;

                case 'documents.status':
                    foreach(DocumentStatus::$texts as $v => $n) {
                        $values[$id][] = array('name' => $n, 'value' => $v);
                    }

                    break;

                case 'users.status':
                    foreach(UserStatus::$texts as $v => $n) {
                        $values[$id][] = array('name' => $n, 'value' => $v);
                    }

                    break;

                case 'processes.status':
                    foreach(ProcessStatus::$texts as $v => $n) {
                        $values[$id][] = array('name' => $n, 'value' => $v);
                    }

                    break;

                case 'processes.type':
                    foreach(ProcessTypes::$texts as $v => $n) {
                        $values[$id][] = array('name' => $n, 'value' => $v);
                    }

                    break;

                case 'documents.after_shred_action':
                    foreach(DocumentAfterShredActions::$texts as $v => $n) {
                        $values[$id][] = array('name' => $n, 'value' => $v);
                    }

                    break;

                case 'documents.shredding_status':
                    foreach(DocumentShreddingStatus::$texts as $v => $n) {
                        $values[$id][] = array('name' => $n, 'value' => $v);
                    }

                    break;
            }

            foreach($values as $id => $values) {
                foreach($values as $value) {
                    $n = $value['name'];
                    $v = $value['value'];

                    $sql = "INSERT INTO `metadata_values` (`id_metadata`, `name`, `value`)
                        VALUES ('$id', '$n', '$v')";

                $this->logger->sql($sql, __METHOD__);

                $this->db->query($sql);
                }
            }
        }
    }

    public function insertDefaultUserMetadataRights() {
        $sql = "SELECT `id` FROM `users`";

        $this->logger->sql($sql, __METHOD__);

        $rows = $this->db->query($sql);

        $idUsers = [];
        foreach($rows as $row) {
            $idUsers[] = $row['id'];
        }

        $sql = "SELECT `id` FROM `metadata`";

        $this->logger->sql($sql, __METHOD__);

        $rows = $this->db->query($sql);

        $idMetadata = [];
        foreach($rows as $row) {
            $idMetadata[] = $row['id'];
        }

        $this->db->beginTransaction();

        foreach($idUsers as $idUser) {
            foreach($idMetadata as $idMeta) {
                $sql = "INSERT INTO `user_metadata_rights` (`id_metadata`, `id_user`, `view`, `edit`, `view_values`, `edit_values`)
                        VALUES ('$idMeta', '$idUser', '1', '1', '1', '1')";

                $this->logger->sql($sql, __METHOD__);

                $this->db->query($sql);
            }
        }

        $this->db->commit();
    }

    public function insertDefaultGroupMetadataRights() {
        $sql = "SELECT `id` FROM `groups`";
        
        $this->logger->sql($sql, __METHOD__);

        $rows = $this->db->query($sql);
        
        $idGroups = [];
        foreach($rows as $row) {
            $idGroups[] = $row['id'];
        }

        $sql = "SELECT `id` FROM `metadata`";

        $this->logger->sql($sql, __METHOD__);

        $rows = $this->db->query($sql);

        $idMetadata = [];
        foreach($rows as $row) {
            $idMetadata[] = $row['id'];
        }

        $this->db->beginTransaction();

        foreach($idGroups as $idGroup) {
            foreach($idMetadata as $idMeta) {
                $sql = "INSERT INTO `group_metadata_rights` (`id_metadata`, `id_group`, `view`, `edit`, `view_values`, `edit_values`)
                        VALUES ('$idMeta', '$idGroup', '1', '1', '1', '1')";

                $this->logger->sql($sql, __METHOD__);

                $this->db->query($sql);
            }
        }

        $this->db->commit();
    }

    public function insertDefaultServiceConfig() {
        $serviceCfg = array(
            'LogRotateService' => array(
                'files_keep_length' => '7'
            ),
            'PasswordPolicyService' => array(
                'password_change_period' => '30',
                'password_change_force_administrators' => '0',
                'password_change_force' => '0'
            ),
            'NotificationManagerService' => array(
                'notification_keep_length' => '1'
            )
        );

        $this->db->beginTransaction();

        foreach($serviceCfg as $serviceName => $serviceData) {
            foreach($serviceData as $key => $value) {
                $sql = "INSERT INTO `service_config` (`name`, `key`, `value`) VALUES ('$serviceName', '$key', '$value')";

                $this->logger->sql($sql, __METHOD__);

                $this->db->query($sql);
            }
        }

        $this->db->commit();
    }

    public function insertDefaultRibbons() {
        $toppanelCodes = array(
            'home',
            'documents',
            'processes',
            'settings'
        );

        $toppanelRibbons = array(
            array(
                'name' => 'Home',
                'code' => 'home',
                'image' => 'img/home.svg',
                'is_visible' => '1',
                'page_url' => '?page=UserModule:HomePage:showHomepage',
                'is_system' => '1'
            ),
            array(
                'name' => 'Documents',
                'code' => 'documents',
                'image' => 'img/documents.svg',
                'is_visible' => '1',
                'page_url' => '?page=UserModule:Documents:showAll',
                'is_system' => '1'
            ),
            array(
                'name' => 'Processes',
                'code' => 'processes',
                'image' => 'img/processes.svg',
                'is_visible' => '1',
                'page_url' => '?page=UserModule:Processes:showAll',
                'is_system' => '1'
            ),
            array(
                'name' => 'Settings',
                'code' => 'settings',
                'image' => 'img/settings.svg',
                'is_visible' => '1',
                'page_url' => '?page=UserModule:Settings:showDashboard',
                'is_system' => '1'
            )
        );

        $this->db->beginTransaction();

        foreach($toppanelRibbons as $ribbon) {
            $keys = [];
            $values = [];

            foreach($ribbon as $k => $v) {
                $keys[] = $k;
                $values[] = $v;
            }

            $sql = "INSERT INTO `ribbons` (";

            $i = 0;
            foreach($keys as $k) {
                if(($i + 1) == count($keys)) {
                    $sql .= '`' . $k . '`';
                } else {
                    $sql .= '`' . $k . '`, ';
                }

                $i++;
            }

            $sql .= ") VALUES (";

            $i = 0;
            foreach($values as $v) {
                if(($i + 1) == count($values)) {
                    $sql .= "'" . $v . "'";
                } else {
                    $sql .= "'" . $v . "', ";
                }

                $i++;
            }

            $sql .= ")";

            $this->logger->sql($sql, __METHOD__);
            $this->db->query($sql);
        }

        $this->db->commit();

        $subpanelRibbons = array(
            'documents' => array(
                array(
                    'name' => 'All documents',
                    'code' => 'documents.all_documents',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Documents:showAll',
                    'is_system' => '1'
                ),
                array(
                    'name' => 'Waiting for archivation',
                    'code' => 'documents.waiting_for_archivation',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Documents:showFiltered&filter=waitingForArchivation',
                    'is_system' => '1'
                ),
                array(
                    'name' => 'New documents',
                    'code' => 'documents.new_documents',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Documents:showFiltered&filter=new',
                    'is_system' => '1'
                ),
                array(
                    'name' => 'SPLITTER',
                    'code' => 'documents.splitter',
                    'is_visible' => '1',
                    'page_url' => '#',
                    'is_system' => '1'
                )
            ),
            'processes' => array(
                array(
                    'name' => 'Processes started by me',
                    'code' => 'processes.started_by_me',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Processes:showAll&filter=startedByMe',
                    'is_system' => '1'
                ),
                array(
                    'name' => 'Processes waiting for me',
                    'code' => 'processes.waiting_for_me',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Processes:showAll&filter=waitingForMe',
                    'is_system' => '1'
                ),
                array(
                    'name' => 'Finished processes',
                    'code' => 'processes.finished',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Processes:showAll&filter=finished',
                    'is_system' => '1'
                )
            ),
            'settings' => array(
                array(
                    'name' => 'Dashboard',
                    'code' => 'settings.dashboard',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Settings:showDashboard',
                    'image' => 'img/dashboard.svg',
                    'is_system' => '1'
                ),
                array(
                    'name' => 'Document folders',
                    'code' => 'settings.document_folders',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Settings:showFolders',
                    'image' => 'img/folder.svg',
                    'is_system' => '1'
                ),
                array(
                    'name' => 'Users',
                    'code' => 'settings.users',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Settings:showUsers',
                    'image' => 'img/users.svg',
                    'is_system' => '1'
                ),
                array(
                    'name' => 'Groups',
                    'code' => 'settings.groups',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Settings:showGroups',
                    'image' => 'img/groups.svg',
                    'is_system' => '1'
                ),
                array(
                    'name' => 'Metadata',
                    'code' => 'settings.metadata',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Settings:showMetadata',
                    'image' => 'img/metadata.svg',
                    'is_system' => '1'
                ),
                array(
                    'name' => 'System',
                    'code' => 'settings.system',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Settings:showSystem',
                    'image' => 'img/system.svg',
                    'is_system' => '1'
                ),
                array(
                    'name' => 'Services',
                    'code' => 'settings.services',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Settings:showServices',
                    'image' => 'img/services.svg',
                    'is_system' => '1'
                ),
                array(
                    'name' => 'Dashboard widgets',
                    'code' => 'settings.dashboard_widgets',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Settings:showDashboardWidgets',
                    'image' => 'img/dashboard-widgets.svg',
                    'is_system' => '1'
                ),
                array(
                    'name' => 'Ribbons',
                    'code' => 'settings.ribbons',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:RibbonSettings:showAll',
                    'is_system' => '1'
                )
            )
        );

        foreach($toppanelCodes as $code) {
            $sql = "SELECT `id` FROM `ribbons` WHERE `code` = '$code'";

            $this->logger->sql($sql, __METHOD__);
            $result = $this->db->query($sql);

            $id = null;
            foreach($result as $row) {
                $id = $row['id'];
            }

            if($id == null) {
                break;
            }

            if(array_key_exists($code, $subpanelRibbons)) {
                $this->db->beginTransaction();

                foreach($subpanelRibbons[$code] as $ribbon) {
                    $keys = [];
                    $values = [];

                    $sql = "INSERT INTO `ribbons` (";
    
                    foreach($ribbon as $k => $v) {
                        $keys[] = $k;
                        $values[] = $v;
                    }
    
                    $keys[] = 'id_parent_ribbon';
                    $values[] = $id;
        
                    $i = 0;
                    foreach($keys as $k) {
                        if(($i + 1) == count($keys)) {
                            $sql .= '`' . $k . '`';
                        } else {
                            $sql .= '`' . $k . '`, ';
                        }
        
                        $i++;
                    }
        
                    $sql .= ") VALUES (";
        
                    $i = 0;
                    foreach($values as $v) {
                        if(($i + 1) == count($values)) {
                            $sql .= "'" . $v . "'";
                        } else {
                            $sql .= "'" . $v . "', ";
                        }
        
                        $i++;
                    }
        
                    $sql .= ")";
    
                    $this->logger->sql($sql, __METHOD__);
                    $this->db->query($sql);
                }
    
                $this->db->commit();
            }
        }
    }

    public function insertDefaultRibbonGroupRights() {
        $sql = "SELECT `id` FROM `ribbons`";

        $this->logger->sql($sql, __METHOD__);
        $rows = $this->db->query($sql);

        $idRibbons = [];
        foreach($rows as $row) {
            $idRibbons[] = $row['id'];
        }

        $sql = "SELECT `id` FROM `groups` WHERE `code` IN ('ADMINISTRATORS')";

        $this->logger->sql($sql, __METHOD__);
        $rows = $this->db->query($sql);

        $idGroups = [];
        foreach($rows as $row) {
            $idGroups[] = $row['id'];
        }

        $this->db->beginTransaction();

        foreach($idRibbons as $r) {
            foreach($idGroups as $g) {
                $sql = "INSERT INTO `ribbon_group_rights` (`id_ribbon`, `id_group`, `can_see`, `can_edit`, `can_delete`) VALUES ('$r', '$g', '1', '1', '1')";

                $this->logger->sql($sql, __METHOD__);
                $this->db->query($sql);
            }
        }

        $this->db->commit();
    }

    public function insertDefaultRibbonUserRights() {
        $sql = "SELECT `id` FROM `ribbons`";

        $this->logger->sql($sql, __METHOD__);
        $rows = $this->db->query($sql);

        $idRibbons = [];
        foreach($rows as $row) {
            $idRibbons[] = $row['id'];
        }

        $sql = "SELECT `id` FROM `users`";

        $this->logger->sql($sql, __METHOD__);
        $rows = $this->db->query($sql);

        $idUsers = [];
        foreach($rows as $row) {
            $idUsers[] = $row['id'];
        }

        $this->db->beginTransaction();

        foreach($idRibbons as $r) {
            foreach($idUsers as $u) {
                $canSee = 1;
                $canEdit = 1;
                $canDelete = 1;

                if($u != 2) { // not administrator
                    $canEdit = 0;
                    $canDelete = 0;
                }

                $sql = "INSERT INTO `ribbon_user_rights` (`id_ribbon`, `id_user`, `can_see`, `can_edit`, `can_delete`) VALUES ('$r', '$u', '$canSee', '$canEdit', '$canDelete')";

                $this->logger->sql($sql, __METHOD__);
                $this->db->query($sql);
            }
        }

        $this->db->commit();
    }
}

?>