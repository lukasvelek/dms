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

    public function isIdUserInAdministratorsGroup(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $idGroup = $this->groupModel->getGroupByCode('ADMINISTRATORS')->getId();

        $row = $qb->select('*')
                  ->from('group_users')
                  ->where('id_group=:id_group')
                  ->andWhere('id_user=:id_user')
                  ->setParams(array(
                    ':id_group' => $idGroup,
                    ':id_user' => $idUser
                  ))
                  ->execute()
                  ->fetch();

        if(is_null($row)) {
            return false;
        } else {
            return true;
        }
    }

    public function updateUserInGroup(int $idGroup, int $idUser, array $data) {
        $qb = $this->qb(__METHOD__);

        $keys = [];
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

        return $result;
    }

    public function insertUserToGroup(int $idGroup, int $idUser) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->insert('group_users', 'id_user', 'id_group')
                     ->values(':id_user', ':id_group')
                     ->setParams(array(
                        ':id_user' => $idUser,
                        ':id_group' => $idGroup
                     ))
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function getGroupUserByIdGroup(int $idGroup) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('group_users')
                  ->where('id_group=:id_group')
                  ->setParam(':id_group', $idGroup)
                  ->limit('1')
                  ->execute()
                  ->fetchSingle();

        return $this->createGroupUserObjectFromDbRow($row);
    }

    public function getGroupUsersByGroupId(int $idGroup) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('group_users')
                   ->where('id_group=:id_group')
                   ->setParam(':id_group', $idGroup)
                   ->execute()
                   ->fetch();

        $groups = [];
        foreach($rows as $row) {
            $groups[] = $this->createGroupUserObjectFromDbRow($row);
        }

        return $groups;
    }

    public function getGroupsForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('group_users')
                   ->where('id_user=:id_user')
                   ->setParam(':id_user', $idUser)
                   ->execute()
                   ->fetch();

        $groups = [];
        foreach($rows as $row) {
            $groups[] = $this->createGroupUserObjectFromDbRow($row);
        }

        return $groups;
    }

    public function getGroupsWhereIdUserIsManager(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('group_users')
                   ->where('id_manager=:id_user')
                   ->setParam(':id_user', $idUser)
                   ->execute()
                   ->fetch();

        $groups = [];
        foreach($rows as $row) {
            $groups[] = $this->createGroupUserObjectFromDbRow($row);
        }

        return $groups;
    }

    private function createGroupUserObjectFromDbRow($row) {
        $id = $row['id'];
        $idGroup = $row['id_group'];
        $idUser = $row['id_user'];
        $isManager = $row['is_manager'];

        return new GroupUser($id, $idGroup, $idUser, ($isManager ? true : false));
    }
}

?>