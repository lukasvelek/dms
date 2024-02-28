<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Group;

class GroupModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getGroupsWithOffset(int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('groups')
            ->limit($limit)
            ->offset($offset)
            ->execute();

        $groups = [];
        while($row = $qb->fetchAssoc()) {
            $groups[] = $this->createGroupObjectFromDbRow($row);
        }
    
        return $groups;
    }

    public function deleteGroupById(int $id) {
        return $this->deleteById($id, 'groups');
    }
    
    public function getGroupCount() {
        return $this->getRowCount('groups');
    }

    public function getLastInsertedGroup() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('groups')
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->execute();

        return $this->createGroupObjectFromDbRow($qb->fetch());
    }

    public function insertNewGroup(string $name, ?string $code) {
        $qb = $this->qb(__METHOD__);

        $keys = ['name'];
        $values = [$name];

        if($code !== NULL) {
            $keys[] = 'code';
            $values[] = $code;
        }

        $qb ->insert('groups', $keys)
            ->values($values)
            ->execute();

        return $qb->fetchAll();
    }

    public function getGroupByCode(string $code) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('groups')
            ->where('code LIKE ?', [$code])
            ->limit(1)
            ->execute();

        return $this->createGroupObjectFromDbRow($qb->fetch());
    }

    public function getGroupById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('groups')
            ->where('id = ?', [$id])
            ->execute();
        
        return $this->createGroupObjectFromDbRow($qb->fetch());
    }

    public function getAllGroups() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('groups')
            ->execute();
        
        $groups = [];
        while($row = $qb->fetchAssoc()) {
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