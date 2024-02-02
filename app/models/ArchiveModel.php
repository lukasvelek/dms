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

    public function getChildrenCount(int $id, int $type) {
        $qb = $this->qb(__METHOD__);

        $qb->select('id');

        switch($type) {
            case ArchiveType::DOCUMENT:
                $qb->from('documents')
                   ->where('id_archive_document=:id');
                break;

            case ArchiveType::BOX:
                $qb->from('archive_documents')
                   ->where('id_parent_archive_entity=:id');
                break;

            case ArchiveType::ARCHIVE:
                $qb->from('archive_boxes')
                   ->where('id_parent_archive_entity=:id');
                break;
        }

        $rows = $qb->setParam(':id', $id)
                   ->execute()
                   ->fetch();

        $entities = [];
        foreach($rows as $row) {
            $entities[] = $this->createArchiveObjectFromDbRow($row, $type);
        }
           
        return $entities;
    }

    public function getFirstIdDocumentOnAGridPage(int $gridPage) {
        if($gridPage == 0) $gridPage = 1;
        return $this->getFirstRowWithCount($gridPage, 'archive_documents', ['id']);
    }

    public function getFirstIdBoxOnAGridPage(int $gridPage) {
        if($gridPage == 0) $gridPage = 1;
        return $this->getFirstRowWithCount($gridPage, 'archive_boxes', ['id']);
    }

    public function getFirstIdArchiveOnAGridPage(int $gridPage) {
        if($gridPage == 0) $gridPage = 1;
        return $this->getFirstRowWithCount($gridPage, 'archive_archives', ['id']);
    }

    public function getDocumentById(int $id) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('archive_documents')
                  ->where('id=:id')
                  ->setParam(':id', $id)
                  ->execute()
                  ->fetchSingle();

        return $this->createArchiveObjectFromDbRow($row, ArchiveType::DOCUMENT);
    }

    public function getBoxById(int $id) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('archive_boxes')
                  ->where('id=:id')
                  ->setParam(':id', $id)
                  ->execute()
                  ->fetchSingle();

        return $this->createArchiveObjectFromDbRow($row, ArchiveType::BOX);
    }

    public function getArchiveById(int $id) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('archive_archives')
                  ->where('id=:id')
                  ->setParam(':id', $id)
                  ->execute()
                  ->fetchSingle();

        return $this->createArchiveObjectFromDbRow($row, ArchiveType::ARCHIVE);
    }

    public function insertNewDocument(array $data) {
        return $this->insertNew($data, 'archive_documents');
    }

    public function insertNewBox(array $data) {
        return $this->insertNew($data, 'archive_boxes');
    }

    public function insertNewArchive(array $data) {
        return $this->insertNew($data, 'archive_archives');
    }

    public function getBoxesForIdArchiveFromId(?int $idFrom, int $limit, int $idArchive) {
        if(is_null($idFrom)) {
            return [];
        }

        $qb = $this->qb(__METHOD__);

        $qb->select('*')
           ->from('archive_boxes')
           ->where('id_parent_archive_entity=:id_parent')
           ->setParam(':id_parent', $idArchive);

        if($idFrom == 1) {
            $qb->explicit('AND `id` >= ' . $idFrom . ' ');
        } else {
            $qb->explicit('AND `id` > ' . $idFrom . ' ');
        }

        $qb->limit($limit);

        $rows = $qb->execute()->fetch();

        $entities = [];
        foreach($rows as $row) {
            $entities[] = $this->createArchiveObjectFromDbRow($row, ArchiveType::BOX);
        }

        return $entities;
    }

    public function getDocumentsForIdBoxFromId(?int $idFrom, int $limit, int $idBox) {
        if(is_null($idFrom)) {
            return [];
        }

        $qb = $this->qb(__METHOD__);

        $qb->select('*')
           ->from('archive_documents')
           ->where('id_parent_archive_entity=:id_parent')
           ->setParam(':id_parent', $idBox);

        if($idFrom == 1) {
            $qb->explicit('AND `id` >= ' . $idFrom . ' ');
        } else {
            $qb->explicit('AND `id` > ' . $idFrom . ' ');
        }

        $qb->limit($limit);

        $rows = $qb->execute()->fetch();

        $entities = [];
        foreach($rows as $row) {
            $entities[] = $this->createArchiveObjectFromDbRow($row, ArchiveType::DOCUMENT);
        }

        return $entities;
    }

    public function getDocumentCount() {
        return $this->getRowCount('archive_documents', 'id');
    }

    public function getBoxCount() {
        return $this->getRowCount('archive_boxes', 'id');
    }

    public function getArchiveCount() {
        return $this->getRowCount('archive_archives', 'id');
    }

    public function getAllArchivesFromId(?int $idFrom, int $limit) {
        if(is_null($idFrom)) {
            return [];
        }

        $qb = $this->qb(__METHOD__);

        $qb->select('*')
           ->from('archive_archives');

        if($idFrom == 1) {
            $qb->explicit('WHERE `id` >= ' . $idFrom . ' ');
        } else {
            $qb->explicit('WHERE `id` > ' . $idFrom . ' ');
        }

        $qb->limit($limit);

        $rows = $qb->execute()->fetch();

        $entities = [];
        foreach($rows as $row) {
            $entities[] = $this->createArchiveObjectFromDbRow($row, ArchiveType::ARCHIVE);
        }

        return $entities;
    }

    public function getAllBoxesFromId(?int $idFrom, int $limit) {
        if(is_null($idFrom)) {
            return [];
        }

        $qb = $this->qb(__METHOD__);

        $qb->select('*')
           ->from('archive_boxes');

        if($idFrom == 1) {
            $qb->explicit('WHERE `id` >= ' . $idFrom . ' ');
        } else {
            $qb->explicit('WHERE `id` >= ' . $idFrom . ' ');
        }

        $qb->limit($limit);

        $rows = $qb->execute()->fetch();

        $entities = [];
        foreach($rows as $row) {
            $entities[] = $this->createArchiveObjectFromDbRow($row, ArchiveType::BOX);
        }

        return $entities;
    }

    public function getAllDocumentsFromId(?int $idFrom, int $limit) {
        if(is_null($idFrom)) {
            return [];
        }

        $qb = $this->qb(__METHOD__);

        $qb->select('*')
           ->from('archive_documents');

        if($idFrom == 1) {
            $qb->explicit('WHERE `id` >= ' . $idFrom . ' ');
        } else {
            $qb->explicit('WHERE `id` > ' . $idFrom . ' ');
        }

        $qb->limit($limit);

        $rows = $qb->execute()->fetch();

        $entities = [];
        foreach($rows as $row) {
            $entities[] = $this->createArchiveObjectFromDbRow($row, ArchiveType::DOCUMENT);
        }

        return $entities;
    }

    public function getAllDocuments() {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('archive_documents')
                   ->execute()
                   ->fetch();

        $entities = [];
        foreach($rows as $row) {
            $entities[] = $this->createArchiveObjectFromDbRow($row, ArchiveType::DOCUMENT);
        }
           
        return $entities;
    }
    
    public function getAllBoxes() {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('archive_boxes')
                   ->execute()
                   ->fetch();

        $entities = [];
        foreach($rows as $row) {
            $entities[] = $this->createArchiveObjectFromDbRow($row, ArchiveType::BOX);
        }
           
        return $entities;
    }

    public function getAllArchives() {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('archive_archives')
                   ->execute()
                   ->fetch();

        $entities = [];
        foreach($rows as $row) {
            $entities[] = $this->createArchiveObjectFromDbRow($row, ArchiveType::ARCHIVE);
        }
           
        return $entities;
    }

    private function createArchiveObjectFromDbRow($row, int $type) {
        $id = $row['id'];
        $dateCreated = $row['date_created'];
        $name = $row['name'];
        $idParentArchiveEntity = null;
        
        if(isset($row['id_parent_archive_entity'])) {
            $idParentArchiveEntity = $row['id_parent_archive_entity'];
        }

        return new Archive($id, $dateCreated, $name, $type, $idParentArchiveEntity);
    }
}

?>