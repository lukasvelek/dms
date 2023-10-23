<?php

namespace DMS\Core\DB;

use DMS\Constants\BulkActionRights;
use DMS\Constants\PanelRights;
use DMS\Constants\UserActionRights;
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

        $this->updateDefaultUserPanelRights();

        $this->insertDefaultUserPanelRights();
        $this->insertDefaultUserBulkActionRights();
        $this->insertDefaultUserActionRights();
    }

    public function updateDefaultUserPanelRights() {
        /*$this->insertDefaultUserPanelRights();
        $this->insertDefaultUserBulkActionRights();
        $this->insertDefaultUserActionRights();*/
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
                'is_deleted' => 'INT(2) NOT NULL DEFAULT 0'
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
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
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
                'table_name' => 'VARCHAR(256) NOT NULL'
            ),
            'metadata_values' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_metadata' => 'INT(32) NOT NULL',
                'name' => 'VARCHAR(256) NOT NULL',
                'value' => 'VARCHAR(256) NOT NULL'
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
        $defaultUsersUsernames = array('admin');
        $insertUsers = array();

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
            $password = password_hash($iu, PASSWORD_BCRYPT);

            $sql = 'INSERT INTO `users` (`firstname`, `lastname`, `username`, `password`)
                    VALUES (\'Admin\', \'\', \'admin\', \'' . $password . '\')';

            $this->logger->sql($sql, __METHOD__);

            $this->db->query($sql);
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
}

?>