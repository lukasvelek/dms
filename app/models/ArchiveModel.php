<?php

namespace DMS\Models;

use DMS\Constants\ArchiveType;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Archive;

class ArchiveModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getAllDocuments() {
        return $this->getAllArchiveEntitiesByType(ArchiveType::DOCUMENT);
    }
    
    public function getAllBoxes() {
        return $this->getAllArchiveEntitiesByType(ArchiveType::BOX);
    }

    public function getAllArchives() {
        return $this->getAllArchiveEntitiesByType(ArchiveType::ARCHIVE);
    }

    public function getAllArchiveEntitiesByType(int $type) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('archive_entities')
                   ->where('type=:type')
                   ->setParam('type', $type)
                   ->execute()
                   ->fetch();

        $entities = [];
        foreach($rows as $row) {
            $entities[] = $this->createArchiveObjectFromDbRow($row);
        }
           
        return $entities;
    }

    public function getAllArchiveEntities() {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('archive_entities')
                   ->execute()
                   ->fetch();

        $entities = [];
        foreach($rows as $row) {
            $entities[] = $this->createArchiveObjectFromDbRow($row);
        }

        return $entities;
    }

    private function createArchiveObjectFromDbRow($row) {
        $id = $row['id'];
        $dateCreated = $row['date_created'];
        $name = $row['name'];
        $type = $row['type'];
        $idParentArchiveEntity = null;
        
        if(isset($row['id_parent_archive_entity'])) {
            $idParentArchiveEntity = $row['id_parent_archive_entity'];
        }

        return new Archive($id, $dateCreated, $name, $type, $idParentArchiveEntity);
    }
}

?>