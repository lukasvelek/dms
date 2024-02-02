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

    public function getArchiveEntityById(int $id) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('archive_entities')
                  ->where('id=:id')
                  ->setParam(':id', $id)
                  ->execute()
                  ->fetchSingle();

        return $this->createArchiveObjectFromDbRow($row);
    }

    public function getAllAvailableArchiveEntitiesByType(int $type) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('archive_entities')
                   ->where('type=:type')
                   ->explicit(' AND `id_parent_archive_entity` IS NULL ')
                   ->setParam(':type', $type)
                   ->execute()
                   ->fetch();

        $entities = [];
        foreach($rows as $row) {
            $entities[] = $this->createArchiveObjectFromDbRow($row);
        }

        return $entities;
    }

    public function getChildrenDocumentsCount(int $id) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('id')
                   ->from('documents')
                   ->where('id_archive_document=:id')
                   ->setParam(':id', $id)
                   ->execute()
                   ->fetch();

        return $rows->num_rows;
    }

    public function getChildrenCount(int $id) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('id')
                   ->from('archive_entities')
                   ->where('id_parent_archive_entity=:id')
                   ->setParam(':id', $id)
                   ->execute()
                   ->fetch();

        return $rows->num_rows;
    }

    public function insertNewArchiveEntity(array $data) {
        return $this->insertNew($data, 'archive_entities');
    }

    public function getAllArchivesFromId(?int $idFrom, int $limit) {
        if(is_null($idFrom)) {
            return [];
        }

        $qb = $this->qb(__METHOD__);

        $qb->select('*')
           ->from('archive_entities')
           ->where('type=:type')
           ->setParam(':type', ArchiveType::ARCHIVE);

        if($idFrom == 1) {
            $qb->explicit('AND `id` >= ' . $idFrom . ' ');
        } else {
            $qb->explicit('AND `id` > ' . $idFrom . ' ');
        }

        $qb->limit($limit);

        $rows = $qb->execute()->fetch();

        $entities = [];
        foreach($rows as $row) {
            $entities[] = $this->createArchiveObjectFromDbRow($row);
        }

        return $entities;
    }

    public function getAllBoxesFromId(?int $idFrom, int $limit) {
        if(is_null($idFrom)) {
            return [];
        }

        $qb = $this->qb(__METHOD__);

        $qb->select('*')
           ->from('archive_entities')
           ->where('type=:type')
           ->setParam(':type', ArchiveType::BOX);

        if($idFrom == 1) {
            $qb->explicit('AND `id` >= ' . $idFrom . ' ');
        } else {
            $qb->explicit('AND `id` > ' . $idFrom . ' ');
        }

        $qb->limit($limit);

        $rows = $qb->execute()->fetch();

        $entities = [];
        foreach($rows as $row) {
            $entities[] = $this->createArchiveObjectFromDbRow($row);
        }

        return $entities;
    }

    public function getAllDocumentsFromId(?int $idFrom, int $limit) {
        if(is_null($idFrom)) {
            return [];
        }

        $qb = $this->qb(__METHOD__);

        $qb->select('*')
           ->from('archive_entities')
           ->where('type=:type')
           ->setParam(':type', ArchiveType::DOCUMENT);

        if($idFrom == 1) {
            $qb->explicit('AND `id` >= ' . $idFrom . ' ');
        } else {
            $qb->explicit('AND `id` > ' . $idFrom . ' ');
        }

        $qb->limit($limit);

        $rows = $qb->execute()->fetch();

        $entities = [];
        foreach($rows as $row) {
            $entities[] = $this->createArchiveObjectFromDbRow($row);
        }

        return $entities;
    }

    public function getFirstIdEntityOnAGridPage(int $gridPage, int $type) {
        if($gridPage == 0) $gridPage = 1;
        return $this->getFirstRowWithCountWithCond($gridPage, 'archive_entities', ['id'], 'id', "WHERE `type` = $type");
    }

    public function getAllArchiveEntitiesForIdParent(int $idParentArchiveEntity) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('archive_entities')
                   ->where('id_parent_archive_entity=:id_parent')
                   ->setParam(':id_parent', $idParentArchiveEntity)
                   ->execute()
                   ->fetch();

        $entities = [];
        foreach($rows as $row) {
            $entities[] = $this->createArchiveObjectFromDbRow($row);
        }
           
        return $entities;
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
                   ->setParam(':type', $type)
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