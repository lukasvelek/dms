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

    public function removeAllActionRightsForIdUser(int $idUser) {
        return $this->deleteByCol('id_user', $idUser, 'user_action_rights');
    }

    public function removeAllBulkActionRightsForIdUser(int $idUser) {
        return $this->deleteByCol('id_user', $idUser, 'user_bulk_rights');
    }

    public function removeAllPanelRightsForIdUser(int $idUser) {
        return $this->deleteByCol('id_user', $idUser, 'user_panel_rights');
    }

    public function removeAllMetadataRightsForIdUser(int $idUser) {
        return $this->deleteByCol('id_user', $idUser, 'user_metadata_rights');
    }

    public function removeAllUserRightsForIdUser(int $idUser) {
        return ($this->removeAllActionRightsForIdUser($idUser) &&
                $this->removeAllBulkActionRightsForIdUser($idUser) &&
                $this->removeAllPanelRightsForIdUser($idUser) &&
                $this->removeAllMetadataRightsForIdUser($idUser));
    }

    public function checkActionRightExists(int $idUser, string $actionName) {
        return $this->checkRightExists('action', $idUser, $actionName);
    }

    public function checkBulkActionRightExists(int $idUser, string $bulkActionName) {
        return $this->checkRightExists('bulk', $idUser, $bulkActionName);
    }

    public function checkPanelRightExists(int $idUser, string $panelName) {
        return $this->checkRightExists('panel', $idUser, $panelName);
    }

    public function insertActionRightForIdUser(int $idUser, string $actionName, bool $status) {
        return $this->insertNew(array(
            'id_user' => $idUser,
            'action_name' => $actionName,
            'is_executable' => $status ? '1' : '0'
            ), 'user_action_rights');
    }

    public function insertBulkActionRightForIdUser(int $idUser, string $bulkActionName, bool $status) {
        return $this->insertNew(array(
            'id_user' => $idUser,
            'action_name' => $bulkActionName,
            'is_executable' => $status ? '1' : '0'
            ), 'user_bulk_rights');
    }

    public function insertPanelRightForIdUser(int $idUser, string $panelName, bool $status) {
        return $this->insertNew(array(
            'id_user' => $idUser,
            'panel_name' => $panelName,
            'is_visible' => $status ? '1' : '0'
            ), 'user_panel_rights');
    }

    public function insertMetadataRight(int $idUser, int $idMetadata) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('user_metadata_rights', ['id_user', 'id_metadata'])
            ->values([$idUser, $idMetadata])
            ->execute();

        return $qb->fetchAll();
    }

    public function enableRight(int $idUser, int $idMetadata, string $name) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('user_metadata_rights')
            ->set([$name => '1'])
            ->where('id_user = ?', [$idUser])
            ->andWhere('id_metadata = ?', [$idMetadata])
            ->execute();

        return $qb->fetchAll();
    }

    public function disableRight(int $idUser, int $idMetadata, string $name) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('user_metadata_rights')
            ->set([$name => '0'])
            ->where('id_user = ?', [$idUser])
            ->andWhere('id_metadata = ?', [$idMetadata])
            ->execute();

        return $qb->fetchAll();
    }

    public function getMetadataRights(int $idUser, int $idMetadata) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_metadata_rights')
            ->where('id_user = ?', [$idUser])
            ->andWhere('id_metadata = ?', [$idMetadata])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateBulkActionRight(int $idUser, string $rightName, bool $status) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('user_bulk_rights')
            ->set(['is_executable' => ($status ? '1' : '0')])
            ->where('id_user = ?', [$idUser])
            ->andWhere('action_name = ?', [$rightName])
            ->execute();

        return $qb->fetchAll();
    }

    public function updatePanelRight(int $idUser, string $rightName, bool $status) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('user_panel_rights')
            ->set(['is_visible' => ($status ? '1' : '0')])
            ->where('id_user = ?', [$idUser])
            ->andWhere('panel_name = ?', [$rightName])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateActionRight(int $idUser, string $rightName, bool $status) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('user_action_rights')
            ->set(['is_executable' => ($status ? '1' : '0')])
            ->where('id_user = ?', [$idUser])
            ->andWhere('action_name = ?', [$rightName])
            ->execute();

        return $qb->fetchAll();
    }

    public function insertActionRightsForIdUser(int $idUser) {
        foreach(UserActionRights::$all as $r) {
            $qb = $this->qb(__METHOD__);

            $qb ->insert('user_action_rights', ['id_user', 'action_name', 'is_executable'])
                ->values([$idUser, $r, '0'])
                ->execute()
                ->fetchAll();

            $qb->clean();
            unset($qb);
        }

        return true;
    }

    public function insertPanelRightsForIdUser(int $idUser) {
        foreach(PanelRights::$all as $r) {
            $qb = $this->qb(__METHOD__);

            $qb ->insert('user_panel_rights', ['id_user', 'panel_name', 'is_visible'])
                ->values([$idUser, $r, '0'])
                ->execute()
                ->fetchAll();
         
            $qb->clean();
            unset($qb);
        }

        return true;           
    }

    public function insertBulkActionRightsForIdUser(int $idUser) {
        foreach(BulkActionRights::$all as $r) {
            $qb = $this->qb(__METHOD__);

            $qb ->insert('user_bulk_rights', ['id_user', 'action_name', 'is_executable'])
                ->values([$idUser, $r, '0'])
                ->execute()
                ->fetchAll();
         
            $qb->clean();
            unset($qb);
        }

        return true;                
    }

    public function insertMetadataRightsForIdUser(int $idUser, array $metadata) {
        foreach($metadata as $m) {
            $qb = $this->qb(__METHOD__);
                         
            $qb ->insert('user_metadata_rights', ['id_user', 'id_metadata', 'view'])
                ->values([$idUser, $m->getId(), '1'])
                ->execute()
                ->fetchAll();

            $qb->clean();
            unset($qb);
        }

        return true;
    }

    public function getActionRightsForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_action_rights')
            ->where('id_user = ?', [$idUser])
            ->execute();

        $rights = [];
        while($row = $qb->fetchAssoc()) {
            $rights[$row['action_name']] = $row['is_executable'];
        }

        return $rights;
    }

    public function getPanelRightsForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_panel_rights')
            ->where('id_user = ?', [$idUser])
            ->execute();

        $rights = [];
        while($row = $qb->fetchAssoc()) {
            $rights[$row['panel_name']] = $row['is_visible'];
        }

        return $rights;
    }

    public function getBulkActionRightsForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_bulk_rights')
            ->where('id_user = ?', [$idUser])
            ->execute();

        $rights = [];
        while($row = $qb->fetchAssoc()) {
            $rights[$row['action_name']] = $row['is_executable'];
        }

        return $rights;
    }

    public function getAllBulkActionRightsForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_bulk_rights')
            ->where('id_user = ?', [$idUser])
            ->execute();

        $rights = [];
        while($row = $qb->fetchAssoc()) {
            $rights[] = $row['action_name'];
        }

        return $rights;
    }

    private function checkRightExists(string $type, int $idUser, string $name) {
        $qb = $this->qb(__METHOD__);

        $tableName = '';
        $columnName = '';

        switch($type) {
            case 'action':
                $tableName = 'user_action_rights';
                $columnName = 'action_name';
                break;

            case 'bulk':
                $tableName = 'user_bulk_rights';
                $columnName = 'action_name';
                break;

            case 'panel':
                $tableName = 'user_panel_rights';
                $columnName = 'panel_name';
                break;
        }

        $qb ->select(['*'])
            ->from($tableName)
            ->where('id_user = ?', [$idUser])
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