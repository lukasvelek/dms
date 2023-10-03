<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Group;

class GroupModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getGroupById(int $id) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('groups')
                  ->where('id=:id')
                  ->setParam(':id', $id)
                  ->execute()
                  ->fetch();
        
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
        $id = $row['id'];
        $dateCreated = $row['date_created'];
        $name = $row['name'];

        return new Group($id, $dateCreated, $name);
    }
}

?>