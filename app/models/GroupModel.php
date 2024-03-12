<?php

namespace DMS\Models;

use DMS\Constants\Metadata\GroupMetadata;
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
            ->orderBy(GroupMetadata::ID, 'DESC')
            ->limit(1)
            ->execute();

        return $this->createGroupObjectFromDbRow($qb->fetch());
    }

    public function insertNewGroup(string $name, ?string $code) {
        $qb = $this->qb(__METHOD__);

        $keys = [GroupMetadata::NAME];
        $values = [$name];

        if($code !== NULL) {
            $keys[] = GroupMetadata::CODE;
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
            ->where(GroupMetadata::CODE . ' LIKE ?', [$code])
            ->limit(1)
            ->execute();

        return $this->createGroupObjectFromDbRow($qb->fetch());
    }

    public function getGroupById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('groups')
            ->where(GroupMetadata::ID . ' = ?', [$id])
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

        $id = $row[GroupMetadata::ID];
        $dateCreated = $row[GroupMetadata::DATE_CREATED];
        $name = $row[GroupMetadata::NAME];
        
        if(isset($row[GroupMetadata::CODE])) {
            $code = $row[GroupMetadata::CODE];
        } else {
            $code = null;
        }

        return new Group($id, $dateCreated, $name, $code);
    }
}

?>