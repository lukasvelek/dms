<?php

namespace DMS\Models;

use DMS\Constants\BulkActionRights;
use DMS\Constants\PanelRights;
use DMS\Constants\UserActionRights;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class GroupRightModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function insertActionRightsForIdGroup(int $idGroup) {
        foreach(UserActionRights::$all as $r) {
            $qb = $this->qb(__METHOD__);

            $result = $qb->insert('group_action_rights', 'id_group', 'action_name', 'is_executable')
                         ->values(':id_group', ':name', ':execute')
                         ->setParams(array(
                            ':id_group' => $idGroup,
                            ':name' => $r,
                            ':execute' => '0'
                         ))
                         ->execute()
                         ->fetch();
        }

        return true;
    }

    public function insertPanelRightsForIdGroup(int $idGroup) {
        foreach(PanelRights::$all as $r) {
            $qb = $this->qb(__METHOD__);

            $result = $qb->insert('group_panel_rights', 'id_group', 'panel_name', 'is_visible')
                         ->values(':id_group', ':name', ':visible')
                         ->setParams(array(
                            ':id_group' => $idGroup,
                            ':name' => $r,
                            ':visible' => '0'
                         ))
                         ->execute()
                         ->fetch();
        }

        return true;
    }

    public function insertBulkActionRightsForIdGroup(int $idGroup) {
        foreach(BulkActionRights::$all as $r) {
            $qb = $this->qb(__METHOD__);

            $result = $qb->insert('group_bulk_rights', 'id_group', 'action_name', 'is_executable')
                         ->values(':id_group', ':name', ':execute')
                         ->setParams(array(
                            ':id_group' => $idGroup,
                            ':name' => $r,
                            ':execute' => '0'
                         ))
                         ->execute()
                         ->fetch();
        }

        return true;
    }

    public function updatePanelRight(int $idGroup, string $rightName, bool $status) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->update('group_panel_rights')
                     ->set(array(
                        'is_visible' => ':visible'
                     ))
                     ->where('id_group=:id_group')
                     ->andWhere('panel_name=:name')
                     ->setParams(array(
                        ':id_group' => $idGroup,
                        ':name' => $rightName,
                        ':visible' => $status ? '1' : '0'
                     ))
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function updateBulkActionRight(int $idGroup, string $rightName, bool $status) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->update('group_bulk_rights')
                     ->set(array(
                        'is_executable' => ':execute'
                     ))
                     ->where('id_group=:id_group')
                     ->andWhere('action_name=:name')
                     ->setParams(array(
                        ':id_group' => $idGroup,
                        ':name' => $rightName,
                        ':execute' => $status ? '1' : '0'
                     ))
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function updateActionRight(int $idGroup, string $rightName, bool $status) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->update('group_action_rights')
                     ->set(array(
                        'is_executable' => ':execute'
                     ))
                     ->where('id_group=:id_group')
                     ->andWhere('action_name=:name')
                     ->setParams(array(
                        ':id_group' => $idGroup,
                        ':name' => $rightName,
                        ':execute' => $status ? '1' : '0'
                     ))
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function getActionRightsForIdGroup(int $idGroup) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('group_action_rights')
                   ->where('id_group=:id_group')
                   ->setParam(':id_group', $idGroup)
                   ->execute()
                   ->fetch();

        $rights = [];
        foreach($rows as $row) {
            $rights[$row['action_name']] = $row['is_executable'];
        }

        return $rights;
    }

    public function getPanelRightsForIdGroup(int $idGroup) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('group_panel_rights')
                   ->where('id_group=:id_group')
                   ->setParam(':id_group', $idGroup)
                   ->execute()
                   ->fetch();

        $rights = [];
        foreach($rows as $row) {
            $rights[$row['panel_name']] = $row['is_visible'];
        }

        return $rights;
    }

    public function getBulkActionRightsForIdGroup(int $idGroup) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('group_bulk_rights')
                   ->where('id_group=:id_group')
                   ->setParam(':id_group', $idGroup)
                   ->execute()
                   ->fetch();

        $rights = [];
        foreach($rows as $row) {
            $rights[$row['action_name']] = $row['is_executable'];
        }

        return $rights;
    }   
}

?>