<?php

namespace DMS\Models;

use DMS\Constants\Metadata\GroupUserMetadata;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\GroupUser;

class GroupUserModel extends AModel {
    private GroupModel $groupModel;

    public function __construct(Database $db, Logger $logger, GroupModel $groupModel) {
        parent::__construct($db, $logger);

        $this->groupModel = $groupModel;
    }

    public function removeAllGroupUsersForIdGroup(int $idGroup) {
        return $this->deleteByCol(GroupUserMetadata::ID_GROUP, $idGroup, 'group_users');
    }

    public function removeUserFromAllGroups(int $idUser) {
        return $this->deleteByCol(GroupUserMetadata::ID_USER, $idUser, 'group_users');
    }

    public function isIdUserInAdministratorsGroup(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $idGroup = $this->groupModel->getGroupByCode('ADMINISTRATORS')->getId();

        $qb ->select(['*'])
            ->from('group_users')
            ->where(GroupUserMetadata::ID_GROUP . ' = ?', [$idGroup])
            ->andWhere(GroupUserMetadata::ID_USER . ' = ?', [$idUser])
            ->execute();

        if(is_null($qb->fetch())) {
            return false;
        } else {
            return true;
        }
    }

    public function updateUserInGroup(int $idGroup, int $idUser, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('group_users')
            ->set($data)
            ->where(GroupUserMetadata::ID_GROUP . ' = ? AND ' . GroupUserMetadata::ID_USER . ' = ?', [$idGroup, $idUser])
            ->execute();

        return $qb->fetchAll();
    }

    public function removeUserFromGroup(int $idGroup, int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('group_users')
            ->where(GroupUserMetadata::ID_GROUP . ' = ? AND ' . GroupUserMetadata::ID_USER . ' = ?', [$idGroup, $idUser])
            ->execute();

        return $qb->fetchAll();
    }

    public function insertUserToGroup(int $idGroup, int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('group_users', [GroupUserMetadata::ID_USER, GroupUserMetadata::ID_GROUP])
            ->values([$idUser, $idGroup])
            ->execute();

        return $qb->fetchAll();
    }

    public function getGroupUserByIdGroup(int $idGroup) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('group_users')
            ->where(GroupUserMetadata::ID_GROUP . ' = ?', [$idGroup])
            ->limit(1)
            ->execute();

        return $this->createGroupUserObjectFromDbRow($qb->fetch());
    }

    public function getGroupUsersByGroupId(int $idGroup) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('group_users')
            ->where(GroupUserMetadata::ID_GROUP . ' = ?', [$idGroup])
            ->execute();

        $groups = [];
        while($row = $qb->fetchAssoc()) {
            $groups[] = $this->createGroupUserObjectFromDbRow($row);
        }

        return $groups;
    }

    public function getGroupsForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('group_users')
            ->where(GroupUserMetadata::ID_USER . ' = ?', [$idUser])
            ->execute();

        $groups = [];
        while($row = $qb->fetchAssoc()) {
            $groups[] = $this->createGroupUserObjectFromDbRow($row);
        }
        return $groups;
    }

    private function createGroupUserObjectFromDbRow($row) {
        if($row === NULL) {
            return null;
        }
        
        $id = $row[GroupUserMetadata::ID];
        $idGroup = $row[GroupUserMetadata::ID_GROUP];
        $idUser = $row[GroupUserMetadata::ID_USER];
        $isManager = $row[GroupUserMetadata::IS_MANAGER];

        return new GroupUser($id, $idGroup, $idUser, ($isManager ? true : false));
    }
}

?>