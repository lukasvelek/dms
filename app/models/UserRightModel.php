<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class UserRightModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
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
        $qb = $this->qb(__METHOD__);

        //$result = $qb->insert('user_action_rights')
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