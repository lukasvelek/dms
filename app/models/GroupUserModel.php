<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\GroupUser;

class GroupUserModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
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