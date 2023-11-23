<?php

namespace DMS\Core\DB;

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
use DMS\Core\Logger\Logger;

class DatabaseInstaller {
    private Database $db;
    private Logger $logger;

    public const DEFAULT_USERS = array(
        'admin'
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
        $this->insertDefaultServiceConfig();
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
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
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
                'shredding_status' => 'INT(32) NOT NULL'
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
                'id_author' => 'INT(32) NOT NULL'
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
                'length' => 'VARCHAR(256) NOT NULL'
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
                'text' => 'VARCHAR(256)',
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
                'location' => 'INT(32) NOT NULL',
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
        $defaultUsersUsernames = array('serviceuser', 'admin');
        $insertUsers = array();

        $defaultUserData = array(
            'serviceuser' => array(
                'firstname' => 'Service',
                'lastname' => 'User',
                'password' => 'serviceuser'
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
            $password = password_Hash($userData['password'], PASSWORD_BCRYPT);
            $firstname = $userData['firstname'];
            $lastname = $userData['lastname'];
            $username = $iu;

            $sql = "INSERT INTO `users` (`firstname`, `lastname`, `username`, `password`)
                    VALUES ('$firstname', '$lastname', '$username', '$password')";

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
            'ADMINISTRATORS' => 'admin'
        );

        $managers = array(
            'admin' => '1'
        );

        $idGroup = null;
        $idUser = null;

        foreach($groupCodes as $groupCode => $username) {
            $idGroup = null;
            $idUser = null;

            $sql = "SELECT * FROM `groups` WHERE `code` = '$groupCode'";
            $rows = $this->db->query($sql);

            if($rows->num_rows > 0) {
                foreach($rows as $row) {
                    $idGroup = $row['id'];
                }
            }

            $sql = "SELECT * FROM `users` WHERE `username` = '$username'";
            $rows = $this->db->query($sql);

            if($rows->num_rows > 0) {
                foreach($rows as $row) {
                    $idUser = $row['id'];
                }
            }

            if($idUser != NULL && $idGroup != NULL) {
                $manager = $managers[$username];

                $sql = "INSERT INTO `group_users` (`id_user`, `id_group`, `is_manager`) VALUES ('$idUser', '$idGroup', '$manager')";
                $this->db->query($sql);
            }
        }

        return true;
    }

    private function insertDefaultUserPanelRights() {
        $idUsers = array();
        $panels = PanelRights::$all;

        $userPanels = array();
        $dbUserPanels = array();

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

        foreach($idUsers as $idUser) {
            foreach($idMetadata as $idMeta) {
                $sql = "INSERT INTO `user_metadata_rights` (`id_metadata`, `id_user`, `view`, `edit`, `view_values`, `edit_values`)
                        VALUES ('$idMeta', '$idUser', '1', '1', '1', '1')";

                $this->logger->sql($sql, __METHOD__);

                $this->db->query($sql);
            }
        }
    }

    public function insertDefaultServiceConfig() {
        $serviceCfg = array(
            'LogRotateService' => array(
                'files_keep_length' => '7'
            )
        );

        foreach($serviceCfg as $serviceName => $serviceData) {
            foreach($serviceData as $key => $value) {
                $sql = "INSERT INTO `service_config` (`name`, `key`, `value`) VALUES ('$serviceName', '$key', '$value')";

                $this->logger->sql($sql, __METHOD__);

                $this->db->query($sql);
            }
        }
    }
}

?>