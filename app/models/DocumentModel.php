<?php

namespace DMS\Models;

use DMS\Constants\DocumentStatus;
use DMS\Constants\Metadata\DocumentMetadata;
use DMS\Core\AppConfiguration;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Document;
use DMS\Helpers\ArrayHelper;
use QueryBuilder\QueryBuilder;

class DocumentModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function updateDocumentsBulk(array $data, array $ids) {
        return $this->bulkUpdateExisting($data, $ids, 'documents');
    }

    public function deleteDocumentReportQueueEntryByFilename(string $filename, bool $like = false) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('document_reports');

        if($like === TRUE) {
            $qb->where('file_src LIKE \'?\'', [$filename]);
        } else {
            $qb->where('file_src = ?', [$filename]);
        }

        return $qb->fetch();
    }

    public function getDocumentsForDirectory(string $path) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('documents')
            ->where(DocumentMetadata::FILE . ' LIKE "?%"', [$path])
            ->execute();

        $documents = array();
        while($row = $qb->fetchAssoc()) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }
        
        return $documents;
    }

    public function insertDocumentReportQueueEntry(array $data) {
        return $this->insertNew($data, 'document_reports');
    }

    public function updateDocumentReportQueueEntry(int $id, array $data) {
        return $this->updateExisting('document_reports', $id, $data);
    }

    public function deleteDocumentReportQueueEntry(int $id) {
        return $this->deleteById($id, 'document_reports');
    }

    public function getLastInsertedDocumentReportQueueEntryForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['id'])
            ->from('document_reports')
            ->where('id_user = ?', [$idUser])
            ->orderBy('date_updated', 'DESC')
            ->limit(1)
            ->execute();

        return $qb->fetch('id');
    }

    public function getDocumentReportQueueEntriesForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('document_reports')
            ->where('id_user = ?', [$idUser])
            ->orderBy('date_updated', 'DESC')
            ->execute();

        return $qb->fetchAll();
    }

    public function getDocumentReportQueueEntriesForStatus(int $status) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('document_reports')
            ->where('status = ?', [$status])
            ->orderBy('date_updated', 'DESC')
            ->execute();

        return $qb->fetchAll();
    }

    public function getDocumentReportQueueEntryById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('document_reports')
            ->where('id = ?', [$id])
            ->execute();

        return $qb->fetch();
    }

    public function getDocumentCountForCustomSQL(string $sql) {
        $qb = $this->qb(__METHOD__);
        $qb->setSQL($sql);
        $qb->execute();

        return $qb->fetchAll()->num_rows;
    }

    public function getDocumentCountForStatus(?int $idFolder, ?string $filter) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(' . DocumentMetadata::ID . ') AS cnt'])
            ->from('documents');

        if($idFolder !== NULL) {
            $qb ->andWhere(DocumentMetadata::ID_FOLDER . ' = ?', [$idFolder]);
        } else {
            if(AppConfiguration::getGridMainFolderHasAllComments() === FALSE) {
                $qb ->andWhere(DocumentMetadata::ID_FOLDER . ' IS NULL');
            }
        }

        if($filter !== NULL) {
            $this->addFilterCondition($filter, $qb);
        }

        return $qb ->execute()->fetch('cnt');
    }

    public function getStandardDocumentsWithOffset(?int $idFolder, int $limit, int $offset, ?string $filter = null) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('documents')
            ->orderBy(DocumentMetadata::DATE_UPDATED, 'DESC')
            ->limit($limit)
            ->offset($offset);

        if($idFolder !== NULL) {
            $qb ->andWhere(DocumentMetadata::ID_FOLDER . ' = ?', [$idFolder]);
        } else {
            if(AppConfiguration::getGridMainFolderHasAllComments() === FALSE) {
                $qb ->andWhere(DocumentMetadata::ID_FOLDER . ' IS NULL');
            }
        }

        if($filter !== NULL) {
            $this->addFilterCondition($filter, $qb);
        }

        $qb ->execute();

        $documents = array();
        while($row = $qb->fetchAssoc()) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }
    
        return $documents;
    }

    public function getDocumentCountInArchiveDocument(int $idArchiveDocument) {
        return $this->getRowCount('documents', DocumentMetadata::ID, 'WHERE `' . DocumentMetadata::ID_ARCHIVE_DOCUMENT . '` = ' . $idArchiveDocument);
    }

    public function moveToArchiveDocument(int $id, int $idArchiveDocument) {
        $data = [DocumentMetadata::ID_ARCHIVE_DOCUMENT => $idArchiveDocument];

        return $this->updateDocument($id, $data);
    }

    public function bulkMoveToArchiveDocument(array $ids, int $idArchiveDocument) {
        $data = [DocumentMetadata::ID_ARCHIVE_DOCUMENT => $idArchiveDocument];

        return $this->bulkUpdateExisting($data, $ids, 'documents');
    }

    public function moveFromArchiveDocument(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('documents')
            ->setNull([DocumentMetadata::ID_ARCHIVE_DOCUMENT])
            ->where(DocumentMetadata::ID . ' = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function bulkMoveFromArchiveDocument(array $ids) {
        $qb = $this->qb(__METHOD__);
        
        $qb ->update('documents')
            ->setNull([DocumentMetadata::ID_ARCHIVE_DOCUMENT])
            ->where($qb->getColumnInValues(DocumentMetadata::ID, $ids))
            ->execute();

        return $qb->fetchAll();
    }

    public function getDocumentForIdArchiveEntity(int $idArchiveEntity) {
        $qb = $this->composeQueryStandardDocuments();

        $qb ->andWhere(DocumentMetadata::ID_ARCHIVE_DOCUMENT . ' = ? OR ' . DocumentMetadata::ID_ARCHIVE_BOX . ' = ? OR ' . DocumentMetadata::ID_ARCHIVE_ARCHIVE . ' = ?', [$idArchiveEntity, $idArchiveEntity, $idArchiveEntity])
            ->execute();

        $documents = array();
        while($row = $qb->fetchAssoc()) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    public function getDocumentsBySQL(string $sql) {
        $result = $this->db->query($sql);

        if($result === FALSE) {
            return [];
        }

        $documents = [];
        while($row = $result->fetch_assoc()) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    public function getAllDocumentsByStatus(int $status) {
        $qb = $this->composeQueryStandardDocuments();

        $qb ->andWhere(DocumentMetadata::STATUS . ' = ?', [$status])
            ->execute();

        $documents = array();
        while($row = $qb->fetchAssoc()) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    public function getDocumentsInIdArchiveDocumentWithOffset(int $idArchiveDocument, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('documents')
            ->where(DocumentMetadata::ID_ARCHIVE_DOCUMENT . ' = ?', [$idArchiveDocument])
            ->limit($limit)
            ->offset($offset)
            ->execute();

        $documents = array();
        while($row = $qb->fetchAssoc()) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }
        
        return $documents;
    }

    public function getLastDocumentStatsEntry() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('document_stats')
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->execute();

        return $qb->fetch();
    }

    public function insertDocumentStatsEntry(array $data) {
        return $this->insertNew($data, 'document_stats');
    }

    public function getTotalDocumentCount(?int $idFolder, bool $useConfigValueToShowAll = true) {
        $cond = null;

        if($idFolder !== NULL) {
            $cond = 'WHERE ' . DocumentMetadata::ID_FOLDER . ' = ' . $idFolder;
        } else {
            if($useConfigValueToShowAll === TRUE && AppConfiguration::getGridMainFolderHasAllComments() === FALSE) {
                $cond = 'WHERE ' . DocumentMetadata::ID_FOLDER . ' IS NULL';
            }
        }
        return $this->getRowCount('documents', DocumentMetadata::ID, $cond);
    }

    public function getAllDocumentIds() {
        $qb = $this->qb(__METHOD__);

        $qb ->select([DocumentMetadata::ID])
            ->from('documents')
            ->execute();

        $ids = [];
        while($row = $qb->fetchAssoc()) {
            $ids[] = $row['id'];
        }

        return $ids;
    }

    public function removeDocumentSharingForIdDocument(int $idDocument) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('document_sharing')
            ->where('id_document = ?', [$idDocument])
            ->execute();

        return $qb->fetchAll();
    }

    public function deleteDocument(int $id, bool $keepInDb = true) {
        $qb = $this->qb(__METHOD__);

        if($keepInDb) {
            $qb ->update('documents')
                ->set([
                    DocumentMetadata::STATUS => DocumentStatus::DELETED,
                    DocumentMetadata::IS_DELETED => '1',
                    DocumentMetadata::DATE_UPDATED => date(Database::DB_DATE_FORMAT)
                ]);
        } else {
            $qb ->delete()
                ->from('documents');
        }

        $qb ->where(DocumentMetadata::ID . ' = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function getDocumentSharingByIdDocumentAndIdUser(int $idUser, int $idDocument) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('document_sharing')
            ->where('id_user = ?', [$idUser])
            ->andWhere('id_document = ?', [$idDocument])
            ->execute();

        return $qb->fetch();
    }

    public function getCountDocumentsSharedWithUser(int $idUser) {
        return $this->getRowCount('document_sharing', 'id', "WHERE `id_user`='" . $idUser . "' AND (`date_from` < current_timestamp AND `date_to` > current_timestamp)");
    }

    public function getDocumentSharingsSharedWithUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('document_sharing')
            ->where('id_user = ?', [$idUser])
            ->andWhere('(date_from < current_timestamp AND date_to > current_timestamp)')
            ->execute();

        return $qb->fetchAll();
    }

    public function getSharedDocumentsWithUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $documentSharings = $this->getDocumentSharingsSharedWithUser($idUser);

        if($documentSharings->num_rows == 0) {
            return [];
        }

        $qb ->select(['*'])
            ->from('documents')
            ->where($qb->getColumnInValues(DocumentMetadata::ID, $documentSharings))
            ->execute();

        $documents = [];
        while($row = $qb->fetchAssoc()) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    public function insertDocumentSharing(array $data) {
        return $this->insertNew($data, 'document_sharing');
    }

    public function isDocumentSharedToUser(int $idUser, int $idDocument) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('document_sharing')
            ->where('id_user = ?', [$idUser])
            ->andWhere('id_document = ?', [$idDocument])
            ->execute();

        $result = false;

        while($row = $qb->fetchAssoc()) {
            $dateFrom = $row['date_from'];
            $dateTo = $row['date_to'];

            if(strtotime($dateFrom) < time() && strtotime($dateTo) > time()) {
                $result = true;

                break;
            }
        }

        return $result;
    }

    public function removeDocumentSharing(int $idShare) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('document_sharing')
            ->where('id = ?', [$idShare])
            ->execute();

        return $qb->fetchAll();
    }

    public function getDocumentCountByStatus(int $status = 0) {
        $qb = $this->qb(__METHOD__);

        $qb = $qb->select(['COUNT(' . DocumentMetadata::ID . ') AS cnt'])
                 ->from('documents');

        switch($status) {
            case 0:
                break;
            
            default:
                $qb->where(DocumentMetadata::STATUS . ' = ?', [$status]);

                break;
        }

        $qb->execute();

        return $qb->fetch('cnt');
    }

    public function getFilteredDocumentsForName(string $name, ?int $idFolder, string $filter) {
        $qb = $this->composeQueryStandardDocuments();

        $qb ->andWhere(DocumentMetadata::NAME . ' LIKE \'%?%\'', [$name]);

        if(!is_null($idFolder)) {
            $qb ->andWhere(DocumentMetadata::ID_FOLDER . ' = ?', [$idFolder]);
        }

        $this->addFilterCondition($filter, $qb);

        $qb->execute();

        $documents = [];
        while($row = $qb->fetchAssoc()) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    public function getDocumentsForNameCount(string $name, ?int $idFolder, ?string $filter) {
        $qb = $this->composeQueryStandardDocuments();

        $qb ->andWhere(DocumentMetadata::NAME . ' LIKE \'%?%\'', [$name], false);

        if($idFolder != null) {
            $qb ->andWhere(DocumentMetadata::ID_FOLDER . ' = ?', [$idFolder]);
        } else {
            if(AppConfiguration::getGridMainFolderHasAllComments() === FALSE) {
                $qb ->andWhere(DocumentMetadata::ID_FOLDER . ' IS NULL');
            }
        }

        if($filter != null) {
            $this->addFilterCondition($filter, $qb);
        }
        
        $qb ->orderBy(DocumentMetadata::DATE_CREATED, 'DESC')
            ->execute();

        return $qb->fetchAll()->num_rows;
    }

    public function getDocumentsForName(string $name, ?int $idFolder, ?string $filter, int $limit, int $offset) {
        $qb = $this->composeQueryStandardDocuments();

        $qb ->andWhere(DocumentMetadata::NAME . ' LIKE \'%?%\'', [$name], false);

        if($idFolder != null) {
            $qb ->andWhere(DocumentMetadata::ID_FOLDER . ' = ?', [$idFolder]);
        } else {
            if(AppConfiguration::getGridMainFolderHasAllComments() === FALSE) {
                $qb ->andWhere(DocumentMetadata::ID_FOLDER . ' IS NULL');
            }
        }
        
        if($limit >= 0) {
            $qb ->limit($limit)
                ->offset($offset);
        }

        if($filter != null) {
            $this->addFilterCondition($filter, $qb);
        }
        
        $qb ->orderBy(DocumentMetadata::DATE_CREATED, 'DESC')
            ->execute();

        $documents = [];
        while($row = $qb->fetchAssoc()) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    public function getDocumentsForFilename(string $filename) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('documents')
            ->where(DocumentMetadata::FILE . ' = ?', [$filename])
            ->execute();

        $documents = [];
        while($row = $qb->fetchAssoc()) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    public function nullIdFolder(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('documents')
            ->setNull([DocumentMetadata::ID_FOLDER])
            ->set([DocumentMetadata::DATE_UPDATED => date(Database::DB_DATE_FORMAT)])
            ->where(DocumentMetadata::ID . '= ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function nullIdOfficer(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('documents')
            ->set([
                DocumentMetadata::DATE_UPDATED => date(Database::DB_DATE_FORMAT),
                DocumentMetadata::ID_OFFICER => 'NULL'
            ])
            ->where(DocumentMetadata::ID . ' = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateDocument(int $id, array $values) {
        $qb = $this->qb(__METHOD__);

        $values[DocumentMetadata::DATE_UPDATED] = date(Database::DB_DATE_FORMAT);

        $qb ->update('documents')
            ->set($values)
            ->where(DocumentMetadata::ID . ' = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function getAllDocuments() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('documents')
            ->execute();

        $documents = [];
        while($row = $qb->fetchAssoc()) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    public function getDocumentById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('documents')
            ->where(DocumentMetadata::ID . ' = ?', [$id])
            ->execute();

        return $this->createDocumentObjectFromDbRow($qb->fetch());
    }

    public function updateStatus(int $id, int $status) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('documents')
            ->set([
                    DocumentMetadata::STATUS => $status,
                    DocumentMetadata::DATE_UPDATED => date(Database::DB_DATE_FORMAT)
            ])
            ->where(DocumentMetadata::ID . ' = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function getLastInsertedDocumentForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('documents')
            ->where(DocumentMetadata::ID_AUTHOR . ' = ?', [$idUser])
            ->orderBy(DocumentMetadata::ID, 'DESC')
            ->limit(1)
            ->execute();

        return $this->createDocumentObjectFromDbRow($qb->fetch());
    }

    public function updateOfficer(int $id, int $idOfficer) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('documents')
            ->set([
                    DocumentMetadata::ID_OFFICER => $idOfficer,
                    DocumentMetadata::DATE_UPDATED => date(Database::DB_DATE_FORMAT)
            ])
            ->where(DocumentMetadata::ID . ' = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function insertNewDocument(array $data) {
        return $this->insertNew($data, 'documents');
    }

    public function getStandardFilteredDocuments(string $filter) {
        $qb = $this->composeQueryStandardDocuments();
        $this->addFilterCondition($filter, $qb);

        $qb->execute();

        $documents = array();
        while($row = $qb->fetchAssoc()) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    public function getStandardDocuments(?int $idFolder, ?string $filter, int $limit) {
        $qb = $this->composeQueryStandardDocuments();

        if($idFolder != null) {
            $qb ->andWhere(DocumentMetadata::ID_FOLDER . ' = ?', [$idFolder]);
        } else {
            if(AppConfiguration::getGridMainFolderHasAllComments() === FALSE) {
                $qb ->andWhere(DocumentMetadata::ID_FOLDER . ' IS NULL');
            }
        }

        if($filter != null) {
            $this->addFilterCondition($filter, $qb);
        }

        $qb ->limit($limit)
            ->execute();

        $documents = array();
        while($row = $qb->fetchAssoc()) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    public function getStandardDocumentsInIdFolder(int $idFolder) {
        $qb = $this->composeQueryStandardDocuments();

        $qb ->andWhere(DocumentMetadata::ID_FOLDER . ' = ?', [$idFolder])
            ->execute();

        $documents = array();
        while($row = $qb->fetchAssoc()) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    public function composeQueryStandardDocuments(bool $ignoreDeleted = true) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('documents');

        if($ignoreDeleted) {
            $qb ->where(DocumentMetadata::IS_DELETED . ' = ?', ['0']);
        }

        return $qb;
    }

    public function createDocumentObjectFromDbRow($row) {
        if($row === NULL) {
            return null;
        }
        
        $id = $row[DocumentMetadata::ID];
        $dateCreated = $row[DocumentMetadata::DATE_CREATED];
        $idAuthor = $row[DocumentMetadata::ID_AUTHOR];
        $idOfficer = $row[DocumentMetadata::ID_OFFICER];
        $name = $row[DocumentMetadata::NAME];
        $status = $row[DocumentMetadata::STATUS];
        $idManager = $row[DocumentMetadata::ID_MANAGER];
        $idGroup = $row[DocumentMetadata::ID_GROUP];
        $isDeleted = $row[DocumentMetadata::IS_DELETED];
        $rank = $row[DocumentMetadata::RANK];
        $idFolder = null;
        $file = null;
        $shredYear = $row[DocumentMetadata::SHRED_YEAR];
        $afterShredAction = $row[DocumentMetadata::AFTER_SHRED_ACTION];
        $shreddingStatus = $row[DocumentMetadata::SHREDDING_STATUS];
        $dateUpdated = $row[DocumentMetadata::DATE_UPDATED];
        $idArchiveDocument = null;
        $idArchiveBox = null;
        $idArchiveArchive = null;

        if(isset($row[DocumentMetadata::ID_FOLDER])) {
            $idFolder = $row[DocumentMetadata::ID_FOLDER];
        }

        if(isset($row[DocumentMetadata::FILE])) {
            $file = $row[DocumentMetadata::FILE];
        }

        if(isset($row[DocumentMetadata::ID_ARCHIVE_DOCUMENT])) {
            $idArchiveDocument = $row[DocumentMetadata::ID_ARCHIVE_DOCUMENT];
        }

        if(isset($row[DocumentMetadata::ID_ARCHIVE_BOX])) {
            $idArchiveBox = $row[DocumentMetadata::ID_ARCHIVE_BOX];
        }

        if(isset($row[DocumentMetadata::ID_ARCHIVE_ARCHIVE])) {
            $idArchiveArchive = $row[DocumentMetadata::ID_ARCHIVE_ARCHIVE];
        }

        ArrayHelper::deleteKeysFromArray($row, array(DocumentMetadata::ID,
                                                     DocumentMetadata::DATE_CREATED,
                                                     DocumentMetadata::ID_AUTHOR,
                                                     DocumentMetadata::ID_OFFICER,
                                                     DocumentMetadata::NAME,
                                                     DocumentMetadata::STATUS,
                                                     DocumentMetadata::ID_MANAGER,
                                                     DocumentMetadata::ID_GROUP,
                                                     DocumentMetadata::IS_DELETED,
                                                     DocumentMetadata::RANK,
                                                     DocumentMetadata::ID_FOLDER,
                                                     DocumentMetadata::FILE,
                                                     DocumentMetadata::SHRED_YEAR,
                                                     DocumentMetadata::AFTER_SHRED_ACTION,
                                                     DocumentMetadata::SHREDDING_STATUS,
                                                     DocumentMetadata::DATE_UPDATED,
                                                     DocumentMetadata::ID_ARCHIVE_DOCUMENT,
                                                     DocumentMetadata::ID_ARCHIVE_BOX,
                                                     DocumentMetadata::ID_ARCHIVE_ARCHIVE));

        $document = new Document($id, $dateCreated, $idAuthor, $idOfficer, $name, $status, $idManager, $idGroup, $isDeleted, $rank, $idFolder, $file, $shredYear, $afterShredAction, $shreddingStatus, $dateUpdated, $idArchiveDocument, $idArchiveBox, $idArchiveArchive);
        $document->setMetadata($row);

        return $document;
    }

    private function addFilterCondition(string $filter, QueryBuilder &$qb) {
        switch($filter) {
            case 'waitingForArchivation':
                $qb ->andWhere(DocumentMetadata::STATUS . ' = ?', [DocumentStatus::ARCHIVATION_APPROVED]);
                break;

            case 'new':
                $qb ->andWhere(DocumentMetadata::STATUS . ' = ?', [DocumentStatus::NEW]);
                break;
        }
    }
}

?>