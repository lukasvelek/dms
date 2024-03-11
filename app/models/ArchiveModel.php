<?php

namespace DMS\Models;

use DMS\Constants\ArchiveStatus;
use DMS\Constants\ArchiveType;
use DMS\Constants\Metadata\ArchiveMetadata;
use DMS\Constants\Metadata\DocumentMetadata;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Archive;

class ArchiveModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getDocumentsInIdBoxWithOffset(int $idBox, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('archive_documents')
            ->where(ArchiveMetadata::ID_PARENT_ARCHIVE_ENTITY . ' = ?', [$idBox])
            ->limit($limit)
            ->offset($offset)
            ->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = $this->createArchiveObjectFromDbRow($row, ArchiveType::DOCUMENT);
        }
    
        return $entities;
    }

    public function getBoxesInIdArchiveWithOffset(int $idArchive, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('archive_boxes')
            ->where(ArchiveMetadata::ID_PARENT_ARCHIVE_ENTITY . ' = ?', [$idArchive])
            ->limit($limit)
            ->offset($offset)
            ->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = $this->createArchiveObjectFromDbRow($row, ArchiveType::DOCUMENT);
        }
    
        return $entities;
    }

    public function getDocumentsWithOffset(int $limit, int $offset) {
        $qb = $this->getArchiveEntitiesWithOffset('archive_documents', $limit, $offset);

        $qb->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = $this->createArchiveObjectFromDbRow($row, ArchiveType::DOCUMENT);
        }

        return $entities;
    }

    public function getBoxesWithOffset(int $limit, int $offset) {
        $qb = $this->getArchiveEntitiesWithOffset('archive_boxes', $limit, $offset);

        $qb->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = $this->createArchiveObjectFromDbRow($row, ArchiveType::BOX);
        }

        return $entities;
    }

    public function getArchivesWithOffset(int $limit, int $offset) {
        $qb = $this->getArchiveEntitiesWithOffset('archive_archives', $limit, $offset);

        $qb->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = $this->createArchiveObjectFromDbRow($row, ArchiveType::ARCHIVE);
        }

        return $entities;
    }

    public function getArchiveEntitiesWithOffset(string $dbTable, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from($dbTable)
            ->limit($limit)
            ->offset($offset);

        return $qb;
    }

    public function getDocumentsForIdParent(int $idParent) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('archive_boxes')
            ->where(ArchiveMetadata::ID_PARENT_ARCHIVE_ENTITY . ' = ?', [$idParent])
            ->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = $this->createArchiveObjectFromDbRow($row, ArchiveType::DOCUMENT);
        }

        return $entities;
    }

    public function getBoxesForIdParent(int $idParent) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('archive_boxes')
            ->where(ArchiveMetadata::ID_PARENT_ARCHIVE_ENTITY . ' = ?', [$idParent])
            ->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = $this->createArchiveObjectFromDbRow($row, ArchiveType::BOX);
        }
                  
        return $entities;
    }

    public function closeArchive(int $idArchive) {
        $data = [
            'status' => ArchiveStatus::CLOSED
        ];

        return $this->updateArchive($idArchive, $data);
    }

    public function moveBoxToArchive(int $idBox, int $idArchive) {
        $data = [
            ArchiveMetadata::ID_PARENT_ARCHIVE_ENTITY => $idArchive,
            ArchiveMetadata::STATUS => ArchiveStatus::IN_ARCHIVE
        ];

        return $this->updateBox($idBox, $data);
    }

    public function moveBoxFromArchive(int $idBox){
        $data = [
            'status' => ArchiveStatus::NEW
        ];

        $this->updateBox($idBox, $data);
        $this->updateToNull('archive_boxes', $idBox, [ArchiveMetadata::ID_PARENT_ARCHIVE_ENTITY]);

        return true;
    }

    public function moveDocumentToBox(int $idDocument, int $idBox) {
        $data = [
            ArchiveMetadata::ID_PARENT_ARCHIVE_ENTITY => $idBox,
            ArchiveMetadata::STATUS => ArchiveStatus::IN_BOX
        ];

        return $this->updateDocument($idDocument, $data);
    }

    public function moveDocumentFromBox(int $idDocument) {
        $data = [
            ArchiveMetadata::STATUS => ArchiveStatus::NEW
        ];

        $this->updateDocument($idDocument, $data);
        $this->updateToNull('archive_documents', $idDocument, [ArchiveMetadata::ID_PARENT_ARCHIVE_ENTITY]);

        return true;
    }

    public function updateDocument(int $id, array $data) {
        return $this->updateExisting('archive_documents', $id, $data);
    }

    public function updateBox(int $id, array $data) {
        return $this->updateExisting('archive_boxes', $id, $data);
    }

    public function updateArchive(int $id, array $data) {
        return $this->updateExisting('archive_archives', $id, $data);
    }

    public function getAllAvailableArchiveEntitiesByType(int $type) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*']);

        switch($type) {
            case ArchiveType::DOCUMENT:
                $qb->from('archive_documents');
                break;

            case ArchiveType::BOX:
                $qb->from('archive_boxes');
                break;

            case ArchiveType::ARCHIVE:
                $qb->from('archive_archives');
                break;
        }

        $qb ->where(ArchiveMetadata::ID_PARENT_ARCHIVE_ENTITY . ' IS NULL')
            ->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = $this->createArchiveObjectFromDbRow($row, $type);
        }

        return $entities;
    }

    public function getChildrenCount(int $id, int $parentType) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['id']);

        switch($parentType) {
            case ArchiveType::DOCUMENT:
                $qb->from('documents')
                   ->where(DocumentMetadata::ID_ARCHIVE_DOCUMENT . ' = ?', [$id]);
                break;

            case ArchiveType::BOX:
                $qb->from('archive_documents')
                   ->where(ArchiveMetadata::ID_PARENT_ARCHIVE_ENTITY . ' = ?', [$id]);
                break;

            case ArchiveType::ARCHIVE:
                $qb->from('archive_boxes')
                   ->where(ArchiveMetadata::ID_PARENT_ARCHIVE_ENTITY . ' = ?', [$id]);
                break;
        }

        $result = $qb ->execute()->fetchAll();

        return $result->num_rows;
    }

    public function getDocumentById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('archive_documents')
            ->where(ArchiveMetadata::ID . ' = ?', [$id])
            ->execute();

        return $this->createArchiveObjectFromDbRow($qb->fetch(), ArchiveType::DOCUMENT);
    }

    public function getBoxById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('archive_boxes')
            ->where(ArchiveMetadata::ID . ' = ?', [$id])
            ->execute();

        return $this->createArchiveObjectFromDbRow($qb->fetch(), ArchiveType::BOX);
    }

    public function getArchiveById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('archive_archives')
            ->where(ArchiveMetadata::ID . ' = ?', [$id])
            ->execute();

        return $this->createArchiveObjectFromDbRow($qb->fetch(), ArchiveType::ARCHIVE);
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

    public function getDocumentCount() {
        return $this->getRowCount('archive_documents', ArchiveMetadata::ID);
    }

    public function getBoxCount() {
        return $this->getRowCount('archive_boxes', ArchiveMetadata::ID);
    }

    public function getArchiveCount() {
        return $this->getRowCount('archive_archives', ArchiveMetadata::ID);
    }

    public function getAllDocuments() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('archive_documents')
            ->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = $this->createArchiveObjectFromDbRow($row, ArchiveType::DOCUMENT);
        }
           
        return $entities;
    }
    
    public function getAllBoxes() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('archive_boxes')
            ->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = $this->createArchiveObjectFromDbRow($row, ArchiveType::BOX);
        }
           
        return $entities;
    }

    public function getAllArchives() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('archive_archives')
            ->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = $this->createArchiveObjectFromDbRow($row, ArchiveType::ARCHIVE);
        }
           
        return $entities;
    }

    private function createArchiveObjectFromDbRow($row, int $type) {
        $id = $row[ArchiveMetadata::ID];
        $dateCreated = $row[ArchiveMetadata::DATE_CREATED];
        $name = $row[ArchiveMetadata::NAME];
        $idParentArchiveEntity = null;
        $status = $row[ArchiveMetadata::STATUS];
        
        if(isset($row[ArchiveMetadata::ID_PARENT_ARCHIVE_ENTITY])) {
            $idParentArchiveEntity = $row[ArchiveMetadata::ID_PARENT_ARCHIVE_ENTITY];
        }

        return new Archive($id, $dateCreated, $name, $type, $idParentArchiveEntity, $status);
    }
}

?>