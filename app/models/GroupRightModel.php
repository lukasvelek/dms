<?php

namespace DMS\Models;

use DMS\Constants\BulkActionRights;
use DMS\Constants\UserActionRights;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class GroupRightModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function removeAllActionRightsForIdGroup(int $idGroup) {
        return $this->deleteByCol('id_group', $idGroup, 'group_action_rights');
    }

    public function removeAllBulkActionRightsForIdGroup(int $idGroup) {
        return $this->deleteByCol('id_group', $idGroup, 'group_bulk_rights');
    }
    
    public function removeAllMetadataRightsForIdGroup(int $idGroup) {
        return $this->deleteByCol('id_group', $idGroup, 'group_metadata_rights');
    }

    public function removeAllGroupRightsForIdGroup(int $idGroup) {
        return ($this->removeAllActionRightsForIdGroup($idGroup) &&
                $this->removeAllBulkActionRightsForIdGroup($idGroup) &&
                $this->removeAllMetadataRightsForIdGroup($idGroup));
    }

    public function checkActionRightExists(int $idGroup, string $actionName) {
        return $this->checkRightExists('action', $idGroup, $actionName);
    }

    public function checkBulkActionRightExists(int $idGroup, string $bulkActionName) {
        return $this->checkRightExists('bulk', $idGroup, $bulkActionName);
    }

    public function insertActionRightForIdGroup(int $idGroup, string $actionName, bool $status) {
        return $this->insertNew(array(
            'id_group' => $idGroup,
            'action_name' => $actionName,
            'is_executable' => $status ? '1' : '0'
            ), 'group_action_rights');
    }

    public function insertBulkActionRightForIdGroup(int $idGroup, string $bulkActionName, bool $status) {
        return $this->insertNew(array(
            'id_group' => $idGroup,
            'action_name' => $bulkActionName,
            'is_executable' => $status ? '1' : '0'
            ), 'group_bulk_rights');
    }

    public function insertActionRightsForIdGroup(int $idGroup) {
        $totalResult = true;

        foreach(UserActionRights::$all as $r) {
            $qb = $this->qb(__METHOD__);

            $qb ->insert('group_action_rights', ['id_group', 'action_name', 'is_executable'])
                ->values([$idGroup, $r, '0'])
                ->execute();
            
            if($totalResult === TRUE) {
                $totalResult = $qb->fetchAll();
            }

            $qb->clean();
            unset($qb);
        }

        return $totalResult;
    }

    public function insertBulkActionRightsForIdGroup(int $idGroup) {
        $totalResult = true;

        foreach(BulkActionRights::$all as $r) {
            $qb = $this->qb(__METHOD__);

            $qb ->insert('group_bulk_rights', ['id_group', 'action_name', 'is_executable'])
                ->values([$idGroup, $r, '0'])
                ->execute()
                ->fetch();

            if($totalResult === TRUE) {
                $totalResult = $qb->fetchAll();
            }

            $qb->clean();
            unset($qb);
        }

        return $totalResult;
    }

    public function updateBulkActionRight(int $idGroup, string $rightName, bool $status) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('group_bulk_rights')
            ->set(['is_executable', ($status ? '1' : '0')])
            ->where('id_group = ?', [$idGroup])
            ->andWhere('action_name = ?', [$rightName])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateActionRight(int $idGroup, string $rightName, bool $status) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('group_action_rights')
            ->set(['is_executable', ($status ? '1' : '0')])
            ->where('id_group = ?', [$idGroup])
            ->andWhere('action_name = ?', [$rightName])
            ->execute();

        return $qb->fetchAll();
    }

    public function getActionRightsForIdGroup(int $idGroup) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('group_action_rights')
            ->where('id_group = ?', [$idGroup])
            ->execute();

        $rights = [];
        while($row = $qb->fetchAssoc()) {
            $rights[$row['action_name']] = $row['is_executable'];
        }

        return $rights;
    }

    public function getBulkActionRightsForIdGroup(int $idGroup) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('group_bulk_rights')
            ->where('id_group = ?', [$idGroup])
            ->execute();

        $rights = [];
        while($row = $qb->fetchAssoc()) {
            $rights[$row['action_name']] = $row['is_executable'];
        }

        return $rights;
    }

    /**
     * Internal method that is used to check if a right exists.
     * It is useful when trying to update a value. If the right does not exist, it cannot be updated and has to be inserted instead.
     * 
     * @param string $type Right type - possible values: action, bulk, panel
     * @param int $idGroup Group ID
     * @param string $name Right name
     * @return bool True if the right exists or false if not
     */
    private function checkRightExists(string $type, int $idGroup, string $name) {
        $qb = $this->qb(__METHOD__);

        $tableName = '';
        $columnName = '';

        switch($type) {
            case 'action':
                $tableName = 'group_action_rights';
                $columnName = 'action_name';
                break;

            case 'bulk':
                $tableName = 'group_bulk_rights';
                $columnName = 'action_name';
                break;
        }

        $qb ->select(['*'])
            ->from($tableName)
            ->where('id_group = ?', [$idGroup])
            ->andWhere($columnName . ' = ?', [$name])
            ->execute();

        if($qb->fetchAll()->num_rows == 1) {
            return true;
        } else {
            return false;
        }
    }
}

?>