<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Group;

class GroupModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getAllGroupsFromId(?int $idFrom, int $limit) {
        if(is_null($idFrom)) {
            return [];
        }

        $qb = $this->qb(__METHOD__);

        $qb ->select('*')
            ->from('groups');

        if($idFrom == 1) {
            $qb->explicit(' WHERE `id` >= ' . $idFrom . ' ');
        } else {
            $qb->explicit(' WHERE `id` > ' . $idFrom . ' ');
        }

        $qb->limit($limit);

        $rows = $qb->execute()->fetch();

        $groups = [];
        foreach($rows as $row) {
            $groups[] = $this->createGroupObjectFromDbRow($row);
        }

        return $groups;
    }

    public function getFirstIdGroupOnAGridPage(int $gridPage) {
        if($gridPage == 0) $gridPage = 1;
        return $this->getFirstRowWithCount($gridPage, 'groups', ['id']);
    }

    public function getGroupCount() {
        return $this->getRowCount('groups');
    }

    public function getLastInsertedGroup() {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('groups')
                  ->orderBy('id', 'DESC')
                  ->limit('1')
                  ->execute()
                  ->fetchSingle();

        return $this->createGroupObjectFromDbRow($row);
    }

    public function insertNewGroup(string $name, ?string $code) {
        $qb = $this->qb(__METHOD__);

        $keys = array('name');
        $values = array(':name');
        $params = array(':name' => $name);

        if(!is_null($code)) {
            $keys[] = 'code';
            $values[] = ':code';
            $params[':code'] = $code;
        }

        $result = $qb->insertArr('groups', $keys)
                     ->valuesArr($values)
                     ->setParams($params)
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function getGroupByCode(string $code) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('groups')
                  ->where('code=:code', true)
                  ->setParam(':code', $code)
                  ->limit('1')
                  ->execute()
                  ->fetchSingle();

        return $this->createGroupObjectFromDbRow($row);
    }

    public function getGroupById(int $id) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('groups')
                  ->where('id=:id')
                  ->setParam(':id', $id)
                  ->execute()
                  ->fetchSingle();
        
        return $this->createGroupObjectFromDbRow($row);
    }

    public function getAllGroups() {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('groups')
                   ->execute()
                   ->fetch();
        
        $groups = [];
        foreach($rows as $row) {
            $groups[] = $this->createGroupObjectFromDbRow($row);
        }

        return $groups;
    }

    private function createGroupObjectFromDbRow($row) {
        if($row === NULL) {
            return null;
        }

        $id = $row['id'];
        $dateCreated = $row['date_created'];
        $name = $row['name'];
        
        if(isset($row['code'])) {
            $code = $row['code'];
        } else {
            $code = null;
        }

        return new Group($id, $dateCreated, $name, $code);
    }
}

?>