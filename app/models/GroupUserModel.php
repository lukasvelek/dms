<?php

namespace DMS\Models;

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
        return $this->deleteByCol('id_group', $idGroup, 'group_users');
    }

    public function removeUserFromAllGroups(int $idUser) {
        return $this->deleteByCol('id_user', $idUser, 'group_users');
    }

    public function isIdUserInAdministratorsGroup(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $idGroup = $this->groupModel->getGroupByCode('ADMINISTRATORS')->getId();

        $qb ->select(['*'])
            ->from('group_users')
            ->where('id_group = ?', [$idGroup])
            ->andWhere('id_user = ?', [$idUser])
            ->execute();

        if(is_null($qb->fetch())) {
            return false;
        } else {
            return true;
        }
    }

    public function updateUserInGroup(int $idGroup, int $idUser, array $data) {
        $qb = $this->qb(__METHOD__);

        /*$keys = [];
        $params = [];

        foreach($data as $k => $v) {
            $keys[$k] = ':' . $k;
            $params[':' . $k] = $v;
        }

        $params[':id_user'] = $idUser;
        $params[':id_group'] = $idGroup;

        $result = $qb->update('group_users')
                     ->set($keys)
                     ->where('id_user=:id_user')
                     ->andWhere('id_group=:id_group')
                     ->setParams($params)
                     ->execute()
                     ->fetch();

        return $result;*/

        $qb ->update('group_users')
            ->set($data)
            ->where('id_group = ? AND id_user = ?', [$idGroup, $idUser])
            ->execute();

        return $qb->fetchAll();
    }

    public function removeUserFromGroup(int $idGroup, int $idUser) {
        $qb = $this->qb(__METHOD__);

        /*$result = $qb->delete()
                     ->from('group_users')
                     ->where('id_group=:id_group')
                     ->andWhere('id_user=:id_user')
                     ->setParams(array(
                        ':id_user' => $idUser,
                        ':id_group' => $idGroup
                     ))
                     ->execute()
                     ->fetch();

        return $result;*/

        $qb ->delete()
            ->from('group_users')
            ->where('id_group = ? AND id_user = ?', [$idGroup, $idUser])
            ->execute();

        return $qb->fetchAll();
    }

    public function insertUserToGroup(int $idGroup, int $idUser) {
        $qb = $this->qb(__METHOD__);

        /*$result = $qb->insert('group_users', 'id_user', 'id_group')
                     ->values(':id_user', ':id_group')
                     ->setParams(array(
                        ':id_user' => $idUser,
                        ':id_group' => $idGroup
                     ))
                     ->execute()
                     ->fetch();

        return $result;*/

        $qb ->insert('group_users', ['id_user', 'id_group'])
            ->values([$idUser, $idGroup])
            ->execute();

        return $qb->fetchAll();
    }

    public function getGroupUserByIdGroup(int $idGroup) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('group_users')
            ->where('id_group = ?', [$idGroup])
            ->limit(1)
            ->execute();

        return $this->createGroupUserObjectFromDbRow($qb->fetch());
    }

    public function getGroupUsersByGroupId(int $idGroup) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('group_users')
            ->where('id_group = ?', [$idGroup])
            ->execute();

        $groups = [];
        foreach($qb->fetchAll() as $row) {
            $groups[] = $this->createGroupUserObjectFromDbRow($row);
        }

        return $groups;
    }

    public function getGroupsForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('group_users')
            ->where('id_user = ?', [$idUser])
            ->execute();

        $groups = [];
        foreach($qb->fetchAll() as $row) {
            $groups[] = $this->createGroupUserObjectFromDbRow($row);
        }

        return $groups;
    }

    public function getGroupsWhereIdUserIsManager(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('group_users')
            ->where('id_manager = ?', [$idUser])
            ->execute();

        $groups = [];
        foreach($qb->fetchAll() as $row) {
            $groups[] = $this->createGroupUserObjectFromDbRow($row);
        }

        return $groups;
    }

    private function createGroupUserObjectFromDbRow($row) {
        if($row === NULL) {
            return null;
        }
        
        $id = $row['id'];
        $idGroup = $row['id_group'];
        $idUser = $row['id_user'];
        $isManager = $row['is_manager'];

        return new GroupUser($id, $idGroup, $idUser, ($isManager ? true : false));
    }
}

?>