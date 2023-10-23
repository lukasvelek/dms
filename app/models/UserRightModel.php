<?php

namespace DMS\Models;

use DMS\Constants\BulkActionRights;
use DMS\Constants\PanelRights;
use DMS\Constants\UserActionRights;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Panels\Panels;

class UserRightModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function enableRight(int $idUser, int $idMetadata, string $name) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->update('user_metadata_rights')
                     ->set(array(
                        $name => ':' . $name
                     ))
                     ->where('id_user=:id_user')
                     ->andWhere('id_metadata=:id_metadata')
                     ->setParams(array(
                        ':id_user' => $idUser,
                        ':id_metadata' => $idMetadata,
                        ':' . $name => '1'
                     ))
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function disableRight(int $idUser, int $idMetadata, string $name) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->update('user_metadata_rights')
                     ->set(array(
                        $name => ':' . $name
                     ))
                     ->where('id_user=:id_user')
                     ->andWhere('id_metadata=:id_metadata')
                     ->setParams(array(
                        ':id_user' => $idUser,
                        ':id_metadata' => $idMetadata,
                        ':' . $name => '0'
                     ))
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function getMetadataRights(int $idUser, int $idMetadata) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('user_metadata_rights')
                  ->where('id_user=:id_user')
                  ->andWhere('id_metadata=:id_metadata')
                  ->setParams(array(
                    ':id_user' => $idUser,
                    ':id_metadata' => $idMetadata
                  ))
                  ->execute()
                  ->fetchSingle();

        return $row;
    }

    public function updateBulkActionRight(int $idUser, string $rightName, bool $status) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->update('user_bulk_rights')
                     ->set(array(
                        'is_executable' => ':execute'
                     ))
                     ->where('id_user=:id_user')
                     ->andWhere('action_name=:name')
                     ->setParams(array(
                        ':id_user' => $idUser,
                        ':name' => $rightName,
                        ':execute' => $status ? '1' : '0'
                     ))
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function updatePanelRight(int $idUser, string $rightName, bool $status) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->update('user_panel_rights')
                     ->set(array(
                        'is_visible' => ':visible'
                     ))
                     ->where('id_user=:id_user')
                     ->andWhere('panel_name=:name')
                     ->setParams(array(
                        ':id_user' => $idUser,
                        ':name' => $rightName,
                        ':visible' => $status ? '1' : '0'
                     ))
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function updateActionRight(int $idUser, string $rightName, bool $status) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->update('user_action_rights')
                     ->set(array(
                        'is_executable' => ':execute'
                     ))
                     ->where('id_user=:id_user')
                     ->andWhere('action_name=:name')
                     ->setParams(array(
                        ':id_user' => $idUser,
                        ':name' => $rightName,
                        ':execute' => $status ? '1' : '0'
                     ))
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function insertActionRightsForIdUser(int $idUser) {
        foreach(UserActionRights::$all as $r) {
            $qb = $this->qb(__METHOD__);

            $result = $qb->insert('user_action_rights', 'id_user', 'action_name', 'is_executable')
                         ->values(':id_user', ':name', ':execute')
                         ->setParams(array(
                            ':id_user' => $idUser,
                            ':name' => $r,
                            ':execute' => '0'
                         ))
                         ->execute()
                         ->fetch();
        }

        return true;
    }

    public function insertPanelRightsForIdUser(int $idUser) {
        foreach(PanelRights::$all as $r) {
            $qb = $this->qb(__METHOD__);

            $result = $qb->insert('user_panel_rights', 'id_user', 'panel_name', 'is_visible')
                         ->values(':id_user', ':name', ':visible')
                         ->setParams(array(
                            ':id_user' => $idUser,
                            ':name' => $r,
                            ':visible' => '0'
                         ))
                         ->execute()
                         ->fetch();
        }

        return true;           
    }

    public function insertBulkActionRightsForIdUser(int $idUser) {
        foreach(BulkActionRights::$all as $r) {
            $qb = $this->qb(__METHOD__);

            $result = $qb->insert('user_bulk_rights', 'id_user', 'action_name', 'is_executable')
                         ->values(':id_user', ':name', ':execute')
                         ->setParams(array(
                            ':id_user' => $idUser,
                            ':name' => $r,
                            ':execute' => '0'
                         ))
                         ->execute()
                         ->fetch();
        }

        return true;                
    }

    public function getActionRightsForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('user_action_rights')
                   ->where('id_user=:id_user')
                   ->setParam(':id_user', $idUser)
                   ->execute()
                   ->fetch();

        $rights = array();

        foreach($rows as $row) {
            $rights[$row['action_name']] = $row['is_executable'];
        }

        return $rights;
    }

    public function getPanelRightsForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('user_panel_rights')
                   ->where('id_user=:id_user')
                   ->setParam(':id_user', $idUser)
                   ->execute()
                   ->fetch();

        $rights = array();

        foreach($rows as $row) {
            $rights[$row['panel_name']] = $row['is_visible'];
        }

        return $rights;
    }

    public function getBulkActionRightsForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('user_bulk_rights')
                   ->where('id_user=:id_user')
                   ->setParam(':id_user', $idUser)
                   ->execute()
                   ->fetch();

        $rights = array();

        foreach($rows as $row) {
            $rights[$row['action_name']] = $row['is_executable'];
        }

        return $rights;
    }

    public function getAllBulkActionRightsForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('user_bulk_rights')
                   ->where('id_user=:id_user')
                   ->setParam(':id_user', $idUser)
                   ->execute()
                   ->fetch();

        $rights = array();

        foreach($rows as $row) {
            $rights[] = $row['action_name'];
        }

        return $rights;
    }
}

?>