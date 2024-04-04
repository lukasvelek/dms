<?php

namespace DMS\Core\DB;

use DMS\Constants\ArchiveStatus;
use DMS\Constants\ArchiveType;
use DMS\Constants\BulkActionRights;
use DMS\Constants\DocumentAfterShredActions;
use DMS\Constants\DocumentRank;
use DMS\Constants\DocumentShreddingStatus;
use DMS\Constants\DocumentStatus;
use DMS\Constants\FileStorageSystemLocations;
use DMS\Constants\ProcessStatus;
use DMS\Constants\ProcessTypes;
use DMS\Constants\Ribbons;
use DMS\Constants\UserActionRights;
use DMS\Constants\UserStatus;
use DMS\Core\CryptManager;
use DMS\Core\Logger\Logger;

/**
 * Database installation definition
 * 
 * @author Lukas Velek
 */
class DatabaseInstaller {
    private Database $db;
    private Logger $logger;

    public const DEFAULT_USERS = array(
        'admin',
        'service_user'
    );

    /**
     * Class constructor
     * 
     * @param Database $db Database instance
     * @param Logger $logger Logger instance
     */
    public function __construct(Database $db, Logger $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Installs the database
     */
    public function install() {
        $this->createTables();
        $this->createIndexes();
        $this->insertDefaultUsers();
        $this->insertDefaultGroups();
        $this->insertDefaultUserGroups();
        $this->insertDefaultMetadata();

        $this->insertDefaultUserBulkActionRights();
        $this->insertDefaultUserActionRights();
        $this->insertDefaultUserMetadataRights();

        $this->insertDefaultGroupBulkActionRights();
        $this->insertDefaultGroupActionRights();
        $this->insertDefaultGroupMetadataRights();

        $this->insertDefaultServiceConfig();

        $this->insertDefaultRibbons();
        $this->insertDefaultRibbonGroupRights();
        $this->insertDefaultRibbonUserRights();

        $this->insertDefaultFileStorageLocations();

        $this->insertSystemServices();
    }

    /**
     * Updates default user rights
     */
    public function updateDefaultUserRights() {
        $this->insertDefaultUserBulkActionRights();
        $this->insertDefaultUserActionRights();
        $this->insertDefaultUserMetadataRights();
    }

    /**
     * Creates the database tables
     * 
     * @return true
     */
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
                'date_updated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'default_user_datetime_format' => 'VARCHAR(256) NULL',
                'last_login_hash' => 'VARCHAR(256) NULL'
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
                'date_updated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'id_archive_document' => 'INT(32) NULL',
                'id_archive_box' => 'INT(32) NULL',
                'id_archive_archive' => 'INT(32) NULL'
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
                'date_updated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'is_archive' => 'INT(2) NOT NULL DEFAULT 0'
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
                'select_external_enum_name' => 'VARCHAR(256) NULL',
                'is_readonly' => 'INT(2) NOT NULL DEFAULT 0'
            ),
            'metadata_values' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_metadata' => 'INT(32) NOT NULL',
                'name' => 'VARCHAR(256) NOT NULL',
                'value' => 'VARCHAR(256) NOT NULL',
                'is_default' => 'INT(2) NOT NULL DEFAULT 0'
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
                'nest_level' => 'INT(32) NOT NULL',
                'ordering' => 'INT(32) NOT NULL'
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
                'text' => 'TEXT',
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
                'text' => 'TEXT NOT NULL',
                'status' => 'INT(2) NOT NULL DEFAULT 1',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'action' => 'VARCHAR(256) NOT NULL'
            ),
            'service_log' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'name' => 'VARCHAR(256) NOT NULL',
                'text' => 'TEXT NOT NULL',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ),
            'mail_queue' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'recipient' => 'VARCHAR(256) NOT NULL',
                'title' => 'VARCHAR(256) NOT NULL',
                'body' => 'TEXT NOT NULL',
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
                'page_url' => 'VARCHAR(256) NOT NULL',
                'ribbon_right' => 'INT(32) NOT NULL'
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
                'filter_sql' => 'TEXT NOT NULL',
                'has_ordering' => 'INT(2) NOT NULL DEFAULT 0'
            ),
            'user_connections' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_user1' => 'INT(32) NOT NULL',
                'id_user2' => 'INT(32) NOT NULL'
            ),
            'archive_documents' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'name' => 'VARCHAR(256) NOT NULL',
                'id_parent_archive_entity' => 'INT(32) NULL',
                'status' => 'INT(2) NOT NULL DEFAULT 1'
            ),
            'archive_boxes' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'name' => 'VARCHAR(256) NOT NULL',
                'id_parent_archive_entity' => 'INT(32) NULL',
                'status' => 'INT(2) NOT NULL DEFAULT 1'
            ),
            'archive_archives' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'name' => 'VARCHAR(256) NOT NULL',
                'id_parent_archive_entity' => 'INT(32) NULL',
                'status' => 'INT(2) NOT NULL DEFAULT 1'
            ),
            'document_reports' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'sql_string' => 'TEXT NOT NULL',
                'id_user' => 'INT(32) NOT NULL',
                'status' => 'INT(2) NOT NULL DEFAULT 1',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'date_updated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'file_src' => 'VARCHAR(256) NULL',
                'file_format' => 'VARCHAR(256) NOT NULL',
                'file_name' => 'VARCHAR(256) NULL',
                'id_file_storage_location' => 'INT(32) NULL'
            ),
            'file_storage_locations' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'name' => 'VARCHAR(256) NOT NULL',
                'path' => 'VARCHAR(256) NOT NULL',
                'is_default' => 'INT(2) NOT NULL DEFAULT 0',
                'is_active' => 'INT(2) NOT NULL DEFAULT 1',
                'order' => 'INT(32) NOT NULL',
                'is_system' => 'INT(2) NOT NULL DEFAULT 0',
                'type' => 'VARCHAR(256) NOT NULL',
                'absolute_path' => 'VARCHAR(256) NOT NULL'
            ),
            'calendar_events' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'title' => 'VARCHAR(256) NOT NULL',
                'color' => 'VARCHAR(256) NOT NULL',
                'tag' => 'VARCHAR(256) NULL',
                'date_from' => 'VARCHAR(256) NOT NULL',
                'date_to' => 'VARCHAR(256) NULL',
                'time' => 'VARCHAR(256) NOT NULL',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ),
            'db_transaction_log' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_calling_user' => 'INT(32) NULL',
                'time_taken' => 'VARCHAR(256) NOT NULL',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ),
            'services' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'system_name' => 'VARCHAR(256) NOT NULL',
                'display_name' => 'VARCHAR(256) NOT NULL',
                'description' => 'VARCHAR(256) NOT NULL',
                'is_enabled' => 'INT(2) NOT NULL DEFAULT 1',
                'is_system' => 'INT(2) NOT NULL DEFAULT 0',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ),
            'document_metadata_history' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_user' => 'INT(32) NOT NULL',
                'id_document' => 'INT(32) NOT NULL',
                'metadata_name' => 'VARCHAR(256) NOT NULL',
                'metadata_value' => 'VARCHAR(256) NULL',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ),
            'document_locks' => array(
                'id' => 'INT(32) NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'id_document' => 'INT(32) NOT NULL',
                'id_user' => 'INT(32) NULL',
                'id_process' => 'INT(32) NULL',
                'description' => 'TEXT NOT NULL',
                'status' => 'INT(2) NOT NULL DEFAULT 1',
                'date_created' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'date_updated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
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

    /**
     * Inserts indexes for selected database tables
     * 
     * @return true
     */
    private function createIndexes() {
        $indexes = [
            [
                'table_name' => 'documents',
                'columns' => [
                    'id_folder'
                ]
            ],
            [
                'table_name' => 'documents',
                'columns' => [
                    'status'
                ]
            ],
            [
                'table_name' => 'document_comments',
                'columns' => [
                    'id_document'
                ]
            ],
            [
                'table_name' => 'document_sharing',
                'columns' => [
                    'id_user',
                    'id_document'
                ]
            ],
            [
                'table_name' => 'document_filters',
                'columns' => [
                    'id_author'
                ]
            ],
            [
                'table_name' => 'document_reports',
                'columns' => [
                    'id_user'
                ]
            ],
            [
                'table_name' => 'user_bulk_rights',
                'columns' => [
                    'id_user'
                ]
            ],
            [
                'table_name' => 'user_action_rights',
                'columns' => [
                    'id_user'
                ]
            ],
            [
                'table_name' => 'user_metadata_rights',
                'columns' => [
                    'id_user',
                    'id_metadata'
                ]
            ],
            [
                'table_name' => 'ribbon_user_rights',
                'columns' => [
                    'id_ribbon',
                    'id_user'
                ]
            ],
            [
                'table_name' => 'group_bulk_rights',
                'columns' => [
                    'id_group'
                ]
            ],
            [
                'table_name' => 'group_action_rights',
                'columns' => [
                    'id_group'
                ]
            ],
            [
                'table_name' => 'group_metadata_rights',
                'columns' => [
                    'id_group',
                    'id_metadata'
                ]
            ],
            [
                'table_name' => 'ribbon_group_rights',
                'columns' => [
                    'id_ribbon',
                    'id_group'
                ]
            ],
            [
                'table_name' => 'metadata_values',
                'columns' => [
                    'id_metadata'
                ]
            ],
            [
                'table_name' => 'folders',
                'columns' => [
                    'id_parent_folder'
                ]
            ],
            [
                'table_name' => 'processes',
                'columns' => [
                    'id_document'
                ]
            ],
            [
                'table_name' => 'processes',
                'columns' => [
                    'id_author'
                ]
            ],
            [
                'table_name' => 'processes',
                'columns' => [
                    'workflow1',
                    'workflow2',
                    'workflow3',
                    'workflow4'
                ]
            ],
            [
                'table_name' => 'process_comments',
                'columns' => [
                    'id_process'
                ]
            ],
            [
                'table_name' => 'notifications',
                'columns' => [
                    'id_user'
                ]
            ],
            [
                'table_name' => 'password_reset_hashes',
                'columns' => [
                    'id_user'
                ]
            ],
            [
                'table_name' => 'ribbons',
                'columns' => [
                    'id_parent_ribbon'
                ]
            ],
            [
                'table_name' => 'file_storage_locations',
                'columns' => [
                    'type'
                ]
            ],
            [
                'table_name' => 'file_storage_locations',
                'columns' => [
                    'name'
                ]
            ],
            [
                'table_name' => 'services',
                'columns' => [
                    'system_name'
                ]
            ],
            [
                'table_name' => 'users',
                'columns' => [
                    'last_login_hash'
                ]
            ],
            [
                'table_name' => 'document_metadata_history',
                'columns' => [
                    'id_document'
                ]
            ],
            [
                'table_name' => 'document_locks',
                'columns' => [
                    'id_document',
                    'status'
                ]
            ]
        ];

        $tables = [];
        foreach($indexes as $array) {
            $tableName = $array['table_name'];
            $columns = $array['columns'];

            $c = 1;
            foreach($tables as $table) {
                if($table == $tableName) {
                    $c++;
                }
            }
            $tables[] = $tableName;

            $sql = 'CREATE INDEX `$INDEX_NAME$` ON `$TABLE_NAME$` (';

            $params = [
                '$INDEX_NAME$' => $tableName . '_' . $c,
                '$TABLE_NAME$' => $tableName
            ];

            foreach($params as $paramName => $paramValue) {
                $sql = str_replace($paramName, $paramValue, $sql);
            }

            $i = 0;
            foreach($columns as $col) {
                if(($i + 1) == count($columns)) {
                    $sql .= $col . ')';
                } else {
                    $sql .= $col . ', ';
                }

                $i++;
            }

            $this->logger->sql($sql, __METHOD__);
            $this->db->query($sql);
        }

        return true;
    }

    /**
     * Inserts default users
     * 
     * @return true
     */
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

    /**
     * Inserts default groups
     * 
     * @return true
     */
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

    /**
     * Inserts default users for groups
     * 
     * @return true
     */
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

    /**
     * Inserts default group bulk action rights
     * 
     * @return true
     */
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

        return true;
    }

    /**
     * Inserts default user bulk action rights
     * 
     * @return true
     */
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

        return true;
    }

    /**
     * Inserts default group action rights
     * 
     * @return true
     */
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

        return true;
    }

    /**
     * Inserts default user action rights
     * 
     * @return true
     */
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

        return true;
    }

    /**
     * Inserts default metadata
     * 
     * @return true
     */
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
                'table_name' => 'archive',
                'name' => 'status',
                'text' => 'Status',
                'input_type' => 'select',
                'length' => '256'
            ),
            array(
                'table_name' => 'archive',
                'name' => 'type',
                'text' => 'Type',
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

                case 'archive.status':
                    foreach(ArchiveStatus::$texts as $v => $n) {
                        $values[$id][] = array('name' => $n, 'value' => $v);
                    }

                    break;

                case 'archive.type':
                    foreach(ArchiveType::$texts as $v => $n) {
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

        return true;
    }

    /**
     * Inserts default user metadata rights
     * 
     * @return true
     */
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

        return true;
    }

    /**
     * Inserts default group metadata rights
     * 
     * @return true
     */
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

        return true;
    }

    /**
     * Inserts default service config
     * 
     * @return true
     */
    public function insertDefaultServiceConfig() {
        $serviceCfg = array(
            'LogRotateService' => array(
                'files_keep_length' => '7',
                'service_run_period' => '7',
                'archive_old_logs' => '1'
            ),
            'PasswordPolicyService' => array(
                'password_change_period' => '30',
                'password_change_force_administrators' => '0',
                'password_change_force' => '0',
                'service_run_period' => '30'
            ),
            'NotificationManagerService' => array(
                'notification_keep_length' => '1',
                'service_run_period' => '7',
                'notification_keep_unseen_service_user' => '1'
            ),
            'CacheRotateService' => array(
                'service_run_period' => '1'
            ),
            'FileManagerService' => array(
                'service_run_period' => '30'
            ),
            'ShreddingSuggestionService' => array(
                'service_run_period' => '30'
            ),
            'MailService' => array(
                'service_run_period' => '1'
            ),
            'DocumentArchivationService' => array(
                'service_run_period' => '7'
            ),
            'DeclinedDocumentRemoverService' => array(
                'service_run_period' => '30'
            ),
            'DocumentReportGeneratorService' => array(
                'service_run_period' => '1'
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

        return true;
    }

    /**
     * Inserts default ribbons
     * 
     * @return true
     */
    public function insertDefaultRibbons() {
        $toppanelCodes = array(
            'home',
            'documents',
            'processes',
            'settings',
            'archive',
            'current_user'
        );

        $toppanelRibbons = array(
            array(
                'name' => 'Home',
                'code' => 'home',
                'is_visible' => '1',
                'page_url' => '?page=UserModule:HomePage:showHomepage',
                'is_system' => '1',
                'ribbon_right' => Ribbons::ROOT_HOME
            ),
            array(
                'name' => 'Documents',
                'code' => 'documents',
                'is_visible' => '1',
                'page_url' => '?page=UserModule:Documents:showAll',
                'is_system' => '1',
                'ribbon_right' => Ribbons::ROOT_DOCUMENTS
            ),
            array(
                'name' => 'Processes',
                'code' => 'processes',
                'is_visible' => '1',
                'page_url' => '?page=UserModule:Processes:showAll',
                'is_system' => '1',
                'ribbon_right' => Ribbons::ROOT_PROCESSES
            ),
            array(
                'name' => 'Archive',
                'code' => 'archive',
                'is_visible' => '1',
                'page_url' => '?page=UserModule:Archive:showDocuments',
                'is_system' => '1',
                'ribbon_right' => Ribbons::ROOT_ARCHIVE
            ),
            array(
                'name' => 'Settings',
                'code' => 'settings',
                'is_visible' => '1',
                'page_url' => '?page=UserModule:Settings:showDashboard',
                'is_system' => '1',
                'ribbon_right' => Ribbons::ROOT_SETTINGS
            ),
            array(
                'name' => 'Current user',
                'code' => 'current_user',
                'is_visible' => '0',
                'page_url' => '?page=UserModule:Users:showProfile',
                'is_system' => '1',
                'ribbon_right' => Ribbons::ROOT_CURRENT_USER
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
                    'is_system' => '1',
                    'ribbon_right' => Ribbons::DOCUMENTS_ALL_DOCUMENTS
                ),
                array(
                    'name' => 'Waiting for archivation',
                    'code' => 'documents.waiting_for_archivation',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Documents:showFiltered&filter=waitingForArchivation',
                    'is_system' => '1',
                    'ribbon_right' => Ribbons::DOCUMENTS_WAITING_FOR_ARCHIVATION
                ),
                array(
                    'name' => 'New documents',
                    'code' => 'documents.new_documents',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Documents:showFiltered&filter=new',
                    'is_system' => '1',
                    'ribbon_right' => Ribbons::DOCUMENTS_NEW_DOCUMENTS
                ),
                array(
                    'name' => 'SPLITTER',
                    'code' => 'documents.splitter',
                    'is_visible' => '1',
                    'page_url' => '#',
                    'is_system' => '1',
                    'ribbon_right' => Ribbons::DOCUMENTS_SPLITTER
                )
            ),
            'processes' => array(
                array(
                    'name' => 'Processes started by me',
                    'code' => 'processes.started_by_me',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Processes:showAll&filter=startedByMe',
                    'is_system' => '1',
                    'ribbon_right' => Ribbons::PROCESSES_STARTED_BY_ME
                ),
                array(
                    'name' => 'Processes waiting for me',
                    'code' => 'processes.waiting_for_me',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Processes:showAll&filter=waitingForMe',
                    'is_system' => '1',
                    'ribbon_right' => Ribbons::PROCESSES_WAITING_FOR_ME
                ),
                array(
                    'name' => 'Finished processes',
                    'code' => 'processes.finished',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Processes:showAll&filter=finished',
                    'is_system' => '1',
                    'ribbon_right' => Ribbons::PROCESSES_FINISHED
                )
            ),
            'settings' => array(
                array(
                    'name' => 'Dashboard',
                    'code' => 'settings.dashboard',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Settings:showDashboard',
                    'is_system' => '1',
                    'ribbon_right' => Ribbons::SETTINGS_DASHBOARD
                ),
                array(
                    'name' => 'Document folders',
                    'code' => 'settings.document_folders',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Settings:showFolders',
                    'is_system' => '1',
                    'ribbon_right' => Ribbons::SETTINGS_DOCUMENT_FOLDERS
                ),
                array(
                    'name' => 'Users',
                    'code' => 'settings.users',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Settings:showUsers',
                    'is_system' => '1',
                    'ribbon_right' => Ribbons::SETTINGS_USERS
                ),
                array(
                    'name' => 'Groups',
                    'code' => 'settings.groups',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Settings:showGroups',
                    'is_system' => '1',
                    'ribbon_right' => Ribbons::SETTINGS_GROUPS
                ),
                array(
                    'name' => 'Metadata',
                    'code' => 'settings.metadata',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Settings:showMetadata',
                    'is_system' => '1',
                    'ribbon_right' => Ribbons::SETTINGS_METADATA
                ),
                array(
                    'name' => 'System',
                    'code' => 'settings.system',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Settings:showSystem',
                    'is_system' => '1',
                    'ribbon_right' => Ribbons::SETTINGS_SYSTEM
                ),
                array(
                    'name' => 'Services',
                    'code' => 'settings.services',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:ServiceSettings:showServices',
                    'is_system' => '1',
                    'ribbon_right' => Ribbons::SETTINGS_SERVICES
                ),
                array(
                    'name' => 'Dashboard widgets',
                    'code' => 'settings.dashboard_widgets',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Settings:showDashboardWidgets',
                    'is_system' => '1',
                    'ribbon_right' => Ribbons::SETTINGS_DASHBOARD_WIDGETS
                ),
                array(
                    'name' => 'Ribbons',
                    'code' => 'settings.ribbons',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:RibbonSettings:showAll',
                    'is_system' => '1',
                    'ribbon_right' => Ribbons::SETTINGS_RIBBONS
                )
            ),
            'archive' => array(
                array(
                    'name' => 'Documents',
                    'code' => 'archive.documents',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Archive:showDocuments',
                    'is_system' => '1',
                    'ribbon_right' => Ribbons::ARCHIVE_DOCUMENTS
                ),
                array(
                    'name' => 'Boxes',
                    'code' => 'archive.boxes',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Archive:showBoxes',
                    'is_system' => '1',
                    'ribbon_right' => Ribbons::ARCHIVE_BOXES
                ),
                array(
                    'name' => 'Archives',
                    'code' => 'archive.archives',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Archive:showArchives',
                    'is_system' => '1',
                    'ribbon_right' => Ribbons::ARCHIVE_ARCHIVES
                )
            ),
            'current_user' => array(
                array(
                    'name' => 'Settings',
                    'code' => 'current_user.settings',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:Users:showSettingsForm&id=current_user',
                    'is_system' => '1',
                    'ribbon_right' => Ribbons::CURRENT_USER_SETTINGS
                ),
                array(
                    'name' => 'My document reports',
                    'code' => 'current_user.document_reports',
                    'is_visible' => '1',
                    'page_url' => '?page=UserModule:DocumentReports:showAll&id=current_user',
                    'is_system' => '1',
                    'ribbon_right' => Ribbons::CURRENT_USER_DOCUMENT_REPORTS
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

        return true;
    }

    /**
     * Inserts default group ribbon rights
     * 
     * @return true
     */
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

        return true;
    }

    /**
     * Inserts default user ribbon rights
     * 
     * @return true
     */
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

        return true;
    }

    /**
     * Inserts default file storage locations
     * 
     * @return true
     */
    public function insertDefaultFileStorageLocations() {
        $cwd = getcwd();
        $cwd = str_replace('\\', '\\\\', $cwd);

        $order = 1;
        foreach(FileStorageSystemLocations::$texts as $key => $valArr) {
            $val = $cwd . '\\' . $valArr['path'] . '\\';
            $type = $valArr['type'];
            $absPath = $valArr['absolute_path'];
            $sql = "INSERT INTO `file_storage_locations` (`name`, `path`, `order`, `is_default`, `is_active`, `is_system`, `type`, `absolute_path`) VALUES ('$key', '$val', '$order', '1', '1', '1', '$type', '$absPath')";
            $this->logger->sql($sql, __METHOD__);
            $this->db->query($sql);
            $order++;
        }

        return true;
    }

    /**
     * Inserts system serviecs
     * 
     * @return true
     */
    public function insertSystemServices() {
        $services = [
            'LogRotateService' => [
                'display_name' => 'Log rotate',
                'description' => 'Deletes old log files'
            ],
            'CacheRotateService' => [
                'display_name' => 'Cache rotate',
                'description' => 'Deletes old cache files'
            ],
            'FileManagerService' => [
                'display_name' => 'File manager',
                'description' => 'Deletes old unused files'
            ],
            'ShreddingSuggestionService' => [
                'display_name' => 'Shredding suggestion',
                'description' => 'Suggests documents for shredding'
            ],
            'PasswordPolicyService' => [
                'display_name' => 'Password policy',
                'description' => 'Checks if passwords have been changed in a period of time'
            ],
            'MailService' => [
                'display_name' => 'Mail service',
                'description' => 'Service responsible for sending emails'
            ],
            'NotificationManagerService' => [
                'display_name' => 'Notification manager',
                'description' => 'Service responsible for deleting old notifications'
            ],
            'DocumentArchivationService' => [
                'display_name' => 'Document archivator',
                'description' => 'Archives documents waiting for archivation'
            ],
            'DeclinedDocumentRemoverService' => [
                'display_name' => 'Declined document remover',
                'description' => 'Deletes declined documents'
            ],
            'DocumentReportGeneratorService' => [
                'display_name' => 'Document report generator',
                'description' => 'Generates document reports'
            ]
        ];

        $this->db->beginTransaction();

        foreach($services as $serviceName => $serviceData) {
            $sql = "INSERT INTO `services` (`system_name`, `display_name`, `description`, `is_enabled`, `is_system`) VALUES (";
            $sql .= "'$serviceName', '" . $serviceData['display_name'] . "', '" . $serviceData['description'] . "', '1', '1'";
            $sql .= ")";

            $this->logger->sql($sql, __METHOD__);
            $this->db->query($sql);
        }

        $this->db->commit();

        return true;
    }
}

?>