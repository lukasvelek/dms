<?php

namespace DMS\Models;

use DMS\Constants\BulkActionRights;
use DMS\Constants\Metadata\UserActionRightMetadata;
use DMS\Constants\Metadata\UserBulkRightMetadata;
use DMS\Constants\Metadata\UserMetadataRightMetadata;
use DMS\Constants\UserActionRights;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class UserRightModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function removeAllActionRightsForIdUser(int $idUser) {
        return $this->deleteByCol(UserActionRightMetadata::ID_USER, $idUser, 'user_action_rights');
    }

    public function removeAllBulkActionRightsForIdUser(int $idUser) {
        return $this->deleteByCol(UserBulkRightMetadata::ID_USER, $idUser, 'user_bulk_rights');
    }

    public function removeAllMetadataRightsForIdUser(int $idUser) {
        return $this->deleteByCol(UserMetadataRightMetadata::ID_USER, $idUser, 'user_metadata_rights');
    }

    public function removeAllUserRightsForIdUser(int $idUser) {
        return ($this->removeAllActionRightsForIdUser($idUser) &&
                $this->removeAllBulkActionRightsForIdUser($idUser) &&
                $this->removeAllMetadataRightsForIdUser($idUser));
    }

    public function checkActionRightExists(int $idUser, string $actionName) {
        return $this->checkRightExists('action', $idUser, $actionName);
    }

    public function checkBulkActionRightExists(int $idUser, string $bulkActionName) {
        return $this->checkRightExists('bulk', $idUser, $bulkActionName);
    }

    public function insertActionRightForIdUser(int $idUser, string $actionName, bool $status) {
        return $this->insertNew(array(
            UserActionRightMetadata::ID_USER => $idUser,
            UserActionRightMetadata::ACTION_NAME => $actionName,
            UserActionRightMetadata::IS_EXECUTABLE => $status ? '1' : '0'
            ), 'user_action_rights');
    }

    public function insertBulkActionRightForIdUser(int $idUser, string $bulkActionName, bool $status) {
        return $this->insertNew(array(
            UserBulkRightMetadata::ID_USER => $idUser,
            UserBulkRightMetadata::ACTION_NAME => $bulkActionName,
            UserBulkRightMetadata::IS_EXECUTABLE => $status ? '1' : '0'
            ), 'user_bulk_rights');
    }

    public function insertMetadataRight(int $idUser, int $idMetadata) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('user_metadata_rights', [UserMetadataRightMetadata::ID_USER, UserMetadataRightMetadata::ID_METADATA])
            ->values([$idUser, $idMetadata])
            ->execute();

        return $qb->fetchAll();
    }

    public function enableRight(int $idUser, int $idMetadata, string $name) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('user_metadata_rights')
            ->set([$name => '1'])
            ->where(UserMetadataRightMetadata::ID_USER . ' = ?', [$idUser])
            ->andWhere(UserMetadataRightMetadata::ID_METADATA . ' = ?', [$idMetadata])
            ->execute();

        return $qb->fetchAll();
    }

    public function disableRight(int $idUser, int $idMetadata, string $name) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('user_metadata_rights')
            ->set([$name => '0'])
            ->where(UserMetadataRightMetadata::ID_USER . ' = ?', [$idUser])
            ->andWhere(UserMetadataRightMetadata::ID_METADATA . ' = ?', [$idMetadata])
            ->execute();

        return $qb->fetchAll();
    }

    public function getIdViewableMetadataForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['id'])
            ->from('user_metadata_rights')
            ->where(UserMetadataRightMetadata::ID_USER . ' = ?', [$idUser])
            ->andWhere(UserMetadataRightMetadata::VIEW . ' = 1')
            ->execute();

        return Database::convertMysqliResultToArray($qb->fetchAll(), ['id_metadata']);
    }

    public function getIdEditableMetadataForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['id'])
            ->from('user_metadata_rights')
            ->where(UserMetadataRightMetadata::ID_USER . ' = ?', [$idUser])
            ->andWhere(UserMetadataRightMetadata::EDIT . ' = 1')
            ->execute();

        return Database::convertMysqliResultToArray($qb->fetchAll(), ['id_metadata']);
    }

    public function getIdViewableValuesMetadataForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['id'])
            ->from('user_metadata_rights')
            ->where(UserMetadataRightMetadata::ID_USER . ' = ?', [$idUser])
            ->andWhere(UserMetadataRightMetadata::VIEW_VALUES . ' = 1')
            ->execute();

        return Database::convertMysqliResultToArray($qb->fetchAll(), ['id_metadata']);
    }

    public function getIdEditableValuesMetadataForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['id'])
            ->from('user_metadata_rights')
            ->where(UserMetadataRightMetadata::ID_USER . ' = ?', [$idUser])
            ->andWhere(UserMetadataRightMetadata::EDIT_VALUES . ' = 1')
            ->execute();

        return Database::convertMysqliResultToArray($qb->fetchAll(), ['id_metadata']);
    }

    public function getMetadataRights(int $idUser, int $idMetadata) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_metadata_rights')
            ->where(UserMetadataRightMetadata::ID_USER . ' = ?', [$idUser])
            ->andWhere(UserMetadataRightMetadata::ID_METADATA . ' = ?', [$idMetadata])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateBulkActionRight(int $idUser, string $rightName, bool $status) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('user_bulk_rights')
            ->set([UserBulkRightMetadata::IS_EXECUTABLE => ($status ? '1' : '0')])
            ->where(UserBulkRightMetadata::ID_USER . ' = ?', [$idUser])
            ->andWhere(UserBulkRightMetadata::ACTION_NAME . ' = ?', [$rightName])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateActionRight(int $idUser, string $rightName, bool $status) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('user_action_rights')
            ->set([UserActionRightMetadata::IS_EXECUTABLE => ($status ? '1' : '0')])
            ->where(UserActionRightMetadata::ID_USER . ' = ?', [$idUser])
            ->andWhere(UserActionRightMetadata::ACTION_NAME . ' = ?', [$rightName])
            ->execute();

        return $qb->fetchAll();
    }

    public function insertActionRightsForIdUser(int $idUser) {
        foreach(UserActionRights::$all as $r) {
            $qb = $this->qb(__METHOD__);

            $qb ->insert('user_action_rights', [UserActionRightMetadata::ID_USER, UserActionRightMetadata::ACTION_NAME, UserActionRightMetadata::IS_EXECUTABLE])
                ->values([$idUser, $r, '0'])
                ->execute();
        }

        return true;
    }

    public function insertBulkActionRightsForIdUser(int $idUser) {
        foreach(BulkActionRights::$all as $r) {
            $qb = $this->qb(__METHOD__);

            $qb ->insert('user_bulk_rights', [UserBulkRightMetadata::ID_USER, UserBulkRightMetadata::ACTION_NAME, UserBulkRightMetadata::IS_EXECUTABLE])
                ->values([$idUser, $r, '0'])
                ->execute();
        }

        return true;                
    }

    public function insertMetadataRightsForIdUser(int $idUser, array $metadata) {
        foreach($metadata as $m) {
            $qb = $this->qb(__METHOD__);
                         
            $qb ->insert('user_metadata_rights', [UserMetadataRightMetadata::ID_USER, UserMetadataRightMetadata::ID_METADATA, UserMetadataRightMetadata::VIEW])
                ->values([$idUser, $m->getId(), '1'])
                ->execute();
        }

        return true;
    }

    public function getActionRightsForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_action_rights')
            ->where(UserActionRightMetadata::ID_USER . ' = ?', [$idUser])
            ->execute();

        $rights = [];
        while($row = $qb->fetchAssoc()) {
            $rights[$row[UserActionRightMetadata::ACTION_NAME]] = $row[UserActionRightMetadata::IS_EXECUTABLE];
        }

        return $rights;
    }

    public function getBulkActionRightsForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_bulk_rights')
            ->where(UserBulkRightMetadata::ID_USER . ' = ?', [$idUser])
            ->execute();

        $rights = [];
        while($row = $qb->fetchAssoc()) {
            $rights[$row[UserBulkRightMetadata::ACTION_NAME]] = $row[UserBulkRightMetadata::IS_EXECUTABLE];
        }

        return $rights;
    }

    public function getAllBulkActionRightsForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_bulk_rights')
            ->where(UserBulkRightMetadata::ID_USER . ' = ?', [$idUser])
            ->execute();

        $rights = [];
        while($row = $qb->fetchAssoc()) {
            $rights[] = $row[UserBulkRightMetadata::ACTION_NAME];
        }

        return $rights;
    }

    private function checkRightExists(string $type, int $idUser, string $name) {
        $qb = $this->qb(__METHOD__);

        $tableName = '';
        $columnName = '';
        $whereColumnName = '';

        switch($type) {
            case 'action':
                $tableName = 'user_action_rights';
                $columnName = 'action_name';
                $whereColumnName = UserActionRightMetadata::ID_USER;
                break;

            case 'bulk':
                $tableName = 'user_bulk_rights';
                $columnName = 'action_name';
                $whereColumnName = UserBulkRightMetadata::ID_USER;
                break;
        }

        $qb ->select(['*'])
            ->from($tableName)
            ->where($whereColumnName . ' = ?', [$idUser])
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