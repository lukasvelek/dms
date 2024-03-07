<?php

namespace DMS\Models;

use DMS\Constants\DocumentStatus;
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
            ->where('file LIKE "?%"', [$path])
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

        $qb ->select(['COUNT(id) AS cnt'])
            ->from('documents');

        if($idFolder !== NULL) {
            $qb ->andWhere('id_folder = ?', [$idFolder]);
        } else {
            if(AppConfiguration::getGridMainFolderHasAllComments() === FALSE) {
                $qb ->andWhere('id_folder IS NULL');
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
            ->orderBy('date_updated', 'DESC')
            ->limit($limit)
            ->offset($offset);

        if($idFolder !== NULL) {
            $qb ->andWhere('id_folder = ?', [$idFolder]);
        } else {
            if(AppConfiguration::getGridMainFolderHasAllComments() === FALSE) {
                $qb ->andWhere('id_folder IS NULL');
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
        return $this->getRowCount('documents', 'id', 'WHERE `id_archive_document` = ' . $idArchiveDocument);
    }

    public function moveToArchiveDocument(int $id, int $idArchiveDocument) {
        $data = ['id_archive_document' => $idArchiveDocument];

        return $this->updateDocument($id, $data);
    }

    public function bulkMoveToArchiveDocument(array $ids, int $idArchiveDocument) {
        $data = ['id_archive_document' => $idArchiveDocument];

        return $this->bulkUpdateExisting($data, $ids, 'documents');
    }

    public function moveFromArchiveDocument(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('documents')
            ->setNull(['id_archive_document'])
            ->where('id = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function bulkMoveFromArchiveDocument(array $ids) {
        $qb = $this->qb(__METHOD__);
        
        $qb ->update('documents')
            ->setNull(['id_archive_document'])
            ->where($qb->getColumnInValues('id', $ids))
            ->execute();

        return $qb->fetchAll();
    }

    public function getDocumentForIdArchiveEntity(int $idArchiveEntity) {
        $qb = $this->composeQueryStandardDocuments();

        $qb ->andWhere('id_archive_document = ? OR id_archive_box = ? OR id_archive_archive = ?', [$idArchiveEntity, $idArchiveEntity, $idArchiveEntity])
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

        $qb ->andWhere('status = ?', [$status])
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
            ->where('id_archive_document = ?', [$idArchiveDocument])
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
            $cond = 'WHERE id_folder = ' . $idFolder;
        } else {
            if($useConfigValueToShowAll === TRUE && AppConfiguration::getGridMainFolderHasAllComments() === FALSE) {
                $cond = 'WHERE id_folder IS NULL';
            }
        }
        return $this->getRowCount('documents', 'id', $cond);
    }

    public function getAllDocumentIds() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['id'])
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
                    'status' => DocumentStatus::DELETED,
                    'is_deleted' => '1',
                    'date_updated' => date(Database::DB_DATE_FORMAT)
                ]);
        } else {
            $qb ->delete()
                ->from('documents');
        }

        $qb ->where('id = ?', [$id])
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
            ->where($qb->getColumnInValues('id', $documentSharings))
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

        $qb = $qb->select(['COUNT(id) AS cnt'])
                 ->from('documents');

        switch($status) {
            case 0:
                break;
            
            default:
                $qb->where('status = ?', [$status]);

                break;
        }

        $qb->execute();

        return $qb->fetch('cnt');
    }

    public function getFilteredDocumentsForName(string $name, ?int $idFolder, string $filter) {
        $qb = $this->composeQueryStandardDocuments();

        $qb ->andWhere('name LIKE \'%?%\'', [$name]);

        if(!is_null($idFolder)) {
            $qb ->andWhere('id_folder = ?', [$idFolder]);
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

        $qb ->andWhere('name LIKE \'%?%\'', [$name], false);

        if($idFolder != null) {
            $qb ->andWhere('id_folder = ?', [$idFolder]);
        } else {
            if(AppConfiguration::getGridMainFolderHasAllComments() === FALSE) {
                $qb ->andWhere('id_folder IS NULL');
            }
        }

        if($filter != null) {
            $this->addFilterCondition($filter, $qb);
        }
        
        $qb ->orderBy('date_created', 'DESC')
            ->execute();

        return $qb->fetchAll()->num_rows;
    }

    public function getDocumentsForName(string $name, ?int $idFolder, ?string $filter, int $limit, int $offset) {
        $qb = $this->composeQueryStandardDocuments();

        $qb ->andWhere('name LIKE \'%?%\'', [$name], false);

        if($idFolder != null) {
            $qb ->andWhere('id_folder = ?', [$idFolder]);
        } else {
            if(AppConfiguration::getGridMainFolderHasAllComments() === FALSE) {
                $qb ->andWhere('id_folder IS NULL');
            }
        }
        
        if($limit >= 0) {
            $qb ->limit($limit)
                ->offset($offset);
        }

        if($filter != null) {
            $this->addFilterCondition($filter, $qb);
        }
        
        $qb ->orderBy('date_created', 'DESC')
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
            ->where('file = ?', [$filename])
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
            ->setNull(['id_folder'])
            ->set(['date_updated' => date(Database::DB_DATE_FORMAT)])
            ->where('id = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function nullIdOfficer(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('documents')
            ->set([
                'date_updated' => date(Database::DB_DATE_FORMAT),
                'id_officer' => 'NULL'
            ])
            ->where('id = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateDocument(int $id, array $values) {
        $qb = $this->qb(__METHOD__);

        $values['date_updated'] = date(Database::DB_DATE_FORMAT);

        $qb ->update('documents')
            ->set($values)
            ->where('id = ?', [$id])
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
            ->where('id = ?', [$id])
            ->execute();

        return $this->createDocumentObjectFromDbRow($qb->fetch());
    }

    public function updateStatus(int $id, int $status) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('documents')
            ->set([
                    'status' => $status,
                    'date_updated' => date(Database::DB_DATE_FORMAT)
            ])
            ->where('id = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function getLastInsertedDocumentForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('documents')
            ->where('id_author = ?', [$idUser])
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->execute();

        return $this->createDocumentObjectFromDbRow($qb->fetch());
    }

    public function updateOfficer(int $id, int $idOfficer) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('documents')
            ->set([
                    'id_officer' => $idOfficer,
                    'date_updated' => date(Database::DB_DATE_FORMAT)
            ])
            ->where('id = ?', [$id])
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
            $qb ->andWhere('id_folder = ?', [$idFolder]);
        } else {
            if(AppConfiguration::getGridMainFolderHasAllComments() === FALSE) {
                $qb ->andWhere('id_folder IS NULL');
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

        $qb ->andWhere('id_folder = ?', [$idFolder])
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
            $qb ->where('is_deleted = ?', ['0']);
        }

        return $qb;
    }

    public function createDocumentObjectFromDbRow($row) {
        if($row === NULL) {
            return null;
        }
        
        $id = $row['id'];
        $dateCreated = $row['date_created'];
        $idAuthor = $row['id_author'];
        $idOfficer = $row['id_officer'];
        $name = $row['name'];
        $status = $row['status'];
        $idManager = $row['id_manager'];
        $idGroup = $row['id_group'];
        $isDeleted = $row['is_deleted'];
        $rank = $row['rank'];
        $idFolder = null;
        $file = null;
        $shredYear = $row['shred_year'];
        $afterShredAction = $row['after_shred_action'];
        $shreddingStatus = $row['shredding_status'];
        $dateUpdated = $row['date_updated'];
        $idArchiveDocument = null;
        $idArchiveBox = null;
        $idArchiveArchive = null;

        if(isset($row['id_folder'])) {
            $idFolder = $row['id_folder'];
        }

        if(isset($row['file'])) {
            $file = $row['file'];
        }

        if(isset($row['id_archive_document'])) {
            $idArchiveDocument = $row['id_archive_document'];
        }

        if(isset($row['id_archive_box'])) {
            $idArchiveBox = $row['id_archive_box'];
        }

        if(isset($row['id_archive_archive'])) {
            $idArchiveArchive = $row['id_archive_archive'];
        }

        ArrayHelper::deleteKeysFromArray($row, array('id', 'date_created', 'id_author', 'id_officer', 'name', 'status', 'id_manager', 'id_group', 'is_deleted', 'rank', 'id_folder', 'file', 'shred_year', 'after_shred_action', 'shredding_status', 'date_updated', 'id_archive_document', 'id_archive_box', 'id_archive_archive'));

        $document = new Document($id, $dateCreated, $idAuthor, $idOfficer, $name, $status, $idManager, $idGroup, $isDeleted, $rank, $idFolder, $file, $shredYear, $afterShredAction, $shreddingStatus, $dateUpdated, $idArchiveDocument, $idArchiveBox, $idArchiveArchive);
        $document->setMetadata($row);

        return $document;
    }

    private function addFilterCondition(string $filter, QueryBuilder &$qb) {
        switch($filter) {
            case 'waitingForArchivation':
                $qb ->andWhere('status = ?', [DocumentStatus::ARCHIVATION_APPROVED]);
                break;

            case 'new':
                $qb ->andWhere('status = ?', [DocumentStatus::NEW]);
                break;
        }
    }
}

?>