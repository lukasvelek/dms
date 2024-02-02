<?php

namespace DMS\Models;

use DMS\Constants\DocumentStatus;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Document;
use DMS\Helpers\ArrayHelper;
use QueryBuilder\QueryBuilder;

class DocumentModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function moveToArchiveDocument(int $id, int $idArchiveDocument) {
        $data = ['id_archive_document' => $idArchiveDocument];

        return $this->updateDocument($id, $data);
    }

    public function moveFromArchiveDocument(int $id) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->update('documents')
                     ->setNull(['id_archive_document'])
                     ->where('id=:id')
                     ->setParam(':id', $id)
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function getDocumentForIdArchiveEntity(int $idArchiveEntity) {
        $qb = $this->composeQueryStandardDocuments();
        $qb ->explicit('AND (')
            ->where('id_archive_document=:id_archive_entity', false, false)
            ->orWhere('id_archive_box=:id_archive_entity')
            ->orWhere('id_archive_archive=:id_archive_entity')
            ->rightBracket()
            ->setParam(':id_archive_entity', $idArchiveEntity);

        $rows = $qb->execute()->fetch();

        $documents = array();
        foreach($rows as $row) {
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
        foreach($result as $row) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    public function getAllDocumentsByStatus(int $status) {
        $qb = $this->composeQueryStandardDocuments();

        $qb ->andWhere('status=:status')
            ->setParam(':status', $status);

        $rows = $qb->execute()->fetch();

        $documents = array();
        foreach($rows as $row) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    public function getFirstIdDocumentInIdArchiveDocumentOnAGridPage(int $idArchiveDocument, int $gridPage) {
        if($gridPage == 0) $gridPage = 1;
        return $this->getFirstRowWithCountWithCond($gridPage, 'documents', ['id'], 'id', 'WHERE `id_archive_document` = ' . $idArchiveDocument);
    }

    public function getDocumentsInIdArchiveDocumentFromId(?int $idFrom, int $idArchiveDocument, int $limit) {
        if(is_null($idFrom)) return [];

        $qb = $this->composeQueryStandardDocuments();

        if($idFrom == 1) {
            $qb->explicit('AND `id` >= ' . $idFrom . ' ');
        } else {
            $qb->explicit('AND `id` > ' . $idFrom . ' ');
        }

        $qb ->andWhere('id_archive_document=:id_archive_document')
            ->setParam(':id_archive_document', $idArchiveDocument);

        $qb->limit($limit);

        $rows = $qb->execute()->fetch();
    
        $documents = array();
        foreach($rows as $row) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }
    
        return $documents;
    }

    public function getFirstIdDocumentOnAGridPage(int $gridPage) {
        if($gridPage == 0) $gridPage = 1;
        return $this->getFirstRowWithCount($gridPage, 'documents', ['id']);
    }

    public function getStandardDocumentsFromId(?int $idFrom, ?int $idFolder, ?string $filter, int $limit) {
        if(is_null($idFrom)) {
            return [];
        }

        $qb = $this->composeQueryStandardDocuments();

        if($idFrom == 1) {
            $qb->explicit('AND `id` >= ' . $idFrom . ' ');
        } else {
            $qb->explicit('AND `id` > ' . $idFrom . ' ');
        }

        if($idFolder != null) {
            $qb ->andWhere('id_folder=:id_folder')->setParam(':id_folder', $idFolder);
        }

        if($filter != null) {
            $this->addFilterCondition($filter, $qb);
        }

        $qb->limit($limit);

        $rows = $qb->execute()->fetch();

        $documents = array();
        foreach($rows as $row) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    public function getLastDocumentStatsEntry() {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('document_stats')
                  ->orderBy('id', 'DESC')
                  ->limit('1')
                  ->execute()
                  ->fetchSingle();

        return $row;
    }

    public function insertDocumentStatsEntry(array $data) {
        return $this->insertNew($data, 'document_stats');
    }

    public function getTotalDocumentCount() {
        return $this->getRowCount('documents');
    }

    public function getAllDocumentIds() {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('id')
                   ->from('documents')
                   ->execute()
                   ->fetch();

        $ids = [];
        foreach($rows as $row) {
            $ids[] = $row['id'];
        }

        return $ids;
    }

    public function removeDocumentSharingForIdDocument(int $idDocument) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->delete()
                     ->from('document_sharing')
                     ->where('id_document=:id_document')
                     ->setParam(':id_document', $idDocument)
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function deleteDocument(int $id, bool $keepInDb = true) {
        $qb = $this->qb(__METHOD__);

        if($keepInDb) {
            $qb ->update('documents')
                ->set(array(
                    'is_deleted' => ':is_deleted',
                    'status' => ':status',
                    'date_updated' => ':date'
                ))
                ->setParams(array(
                    ':status' => DocumentStatus::DELETED,
                    ':is_deleted' => '1',
                    ':date' => date(Database::DB_DATE_FORMAT)
                ));
        } else {
            $qb ->delete()
                ->from('documents');
        }

        $result = $qb->where('id=:id')
                     ->setParam(':id', $id)
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function getDocumentSharingByIdDocumentAndIdUser(int $idUser, int $idDocument) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('document_sharing')
                  ->where('id_user=:id_user')
                  ->andWhere('id_document=:id_document')
                  ->setParams(array(
                   ':id_user' => $idUser,
                   ':id_document' => $idDocument
                  ))
                  ->execute()
                  ->fetchSingle();

        return $row;
    }

    public function getCountDocumentsSharedWithUser(int $idUser) {
        return $this->getRowCount('document_sharing', 'id', "WHERE `id_user`='" . $idUser . "' AND (`date_from` < current_timestamp AND `date_to` > current_timestamp)");
    }

    public function getDocumentSharingsSharedWithUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('document_sharing')
                   ->where('id_user=:id_user')
                   ->explicit(' AND ')
                   ->leftBracket()
                   ->explicit(' `date_from` < current_timestamp AND `date_to` > current_timestamp')
                   ->rightBracket()
                   ->setParam(':id_user', $idUser) 
                   ->execute()
                   ->fetch();

        return $rows;
    }

    public function getSharedDocumentsWithUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $documentSharings = $this->getDocumentSharingsSharedWithUser($idUser);

        if($documentSharings->num_rows == 0) {
            return [];
        }

        $idDocuments = [];
        foreach($documentSharings as $ds) {
            $idDocuments[] = $ds['id_document'];
        }

        $rows = $qb->select('*')
                   ->from('documents')
                   ->inWhere('id', $idDocuments)
                   ->execute()
                   ->fetch();

        $documents = [];
        foreach($rows as $row) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    public function insertDocumentSharing(array $data) {
        return $this->insertNew($data, 'document_sharing');
    }

    public function isDocumentSharedToUser(int $idUser, int $idDocument) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('document_sharing')
                   ->where('id_user=:id_user')
                   ->andWhere('id_document=:id_document')
                   ->setParams(array(
                    ':id_user' => $idUser,
                    ':id_document' => $idDocument
                   ))
                   ->execute()
                   ->fetch();

        $result = false;

        foreach($rows as $row) {
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

        $result = $qb->delete()
                     ->from('document_sharing')
                     ->where('id=:id')
                     ->setParam(':id', $idShare)
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function getDocumentCountByStatus(int $status = 0) {
        $qb = $this->qb(__METHOD__);

        $qb = $qb->selectCount('id', 'cnt')
                 ->from('documents');

        switch($status) {
            case 0:
                break;
            
            default:
                $qb->where('status=:status')
                   ->setParam(':status', $status);

                break;
        }

        $row = $qb->execute()
                  ->fetchSingle('cnt');

        return $row;
    }

    public function getFilteredDocumentsForName(string $name, ?int $idFolder, string $filter) {
        $qb = $this->composeQueryStandardDocuments();

        $qb ->andWhere('name=:name', true)->setParam(':name', $name);

        if(!is_null($idFolder)) {
            $qb ->andWhere('id_folder=:id_folder')->setParam(':id_folder', $idFolder);
        }

        $this->addFilterCondition($filter, $qb);

        $rows = $qb->execute()->fetch();

        $documents = [];
        foreach($rows as $row) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    public function getDocumentsForName(string $name, ?int $idFolder, ?string $filter) {
        $qb = $this->composeQueryStandardDocuments();

        $qb ->andWhere('name=:name', true)->setParam(':name', $name);

        if($filter != null) {
            $this->addFilterCondition($filter, $qb);
        }

        if($idFolder != null) {
            $qb ->andWhere('id_folder=:id_folder')->setParam(':id_folder', $idFolder);
        }

        $qb->limit('20');

        $rows = $qb->execute()->fetch();

        $documents = [];
        foreach($rows as $row) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    public function getDocumentsForFilename(string $filename) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('documents')
                   ->where('file=:file')
                   ->setParam(':file', $filename)
                   ->execute()
                   ->fetch();

        $documents = [];
        foreach($rows as $row) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    public function nullIdFolder(int $id) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->update('documents')
                     ->setNull(array('id_folder'))
                     ->explicit(', `date_updated`=\'' . date(Database::DB_DATE_FORMAT) . '\'')
                     ->where('id=:id')
                     ->setParam(':id', $id)
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function nullIdOfficer(int $id) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->update('documents')
                     ->setNull(array('id_officer'))
                     ->explicit(', `date_updated`=\'' . date(Database::DB_DATE_FORMAT) . '\'')
                     ->where('id=:id')
                     ->setParam(':id', $id)
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function updateDocument(int $id, array $values) {
        $qb = $this->qb(__METHOD__);

        $keys = [];
        $params = [];

        foreach($values as $k => $v) {
            $keys[$k] = ':' . $k;
            $params[':' . $k] = $v;
        }

        if(!array_key_exists('date_updated', $keys)) {
            $keys['date_updated'] = ':date_updated';
            $params[':date_updated'] = date(Database::DB_DATE_FORMAT);
        }

        $result = $qb->update('documents')
                     ->set($keys)
                     ->setParams($params)
                     ->where('id=:id')
                     ->setParam(':id', $id)
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function getAllDocuments() {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('documents')
                   ->execute()
                   ->fetch();

        $qb = $qb->select('*')
                 ->from('documents');

        $documents = [];
        foreach($rows as $row) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    public function getDocumentById(int $id) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('documents')
                  ->where('id=:id')
                  ->setParam(':id', $id)
                  ->execute()
                  ->fetchSingle();

        return $this->createDocumentObjectFromDbRow($row);
    }

    public function updateStatus(int $id, int $status) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->update('documents')
                     ->set(array(
                        'status' => ':status',
                        'date_updated' => ':date'
                     ))
                     ->setParam(':status', $status)
                     ->setParam(':date', date(Database::DB_DATE_FORMAT))
                     ->where('id=:id')
                     ->setParam(':id', $id)
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function getLastInsertedDocumentForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('documents')
                  ->where('id_author=:id_user')
                  ->setParam(':id_user', $idUser)
                  ->orderBy('id', 'DESC')
                  ->limit('1')
                  ->execute()
                  ->fetchSingle();

        return $this->createDocumentObjectFromDbRow($row);
    }

    public function updateOfficer(int $id, int $idOfficer) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->update('documents')
                     ->set(array('id_officer' => ':id_officer', 'date_updated' => ':date'))
                     ->where('id=:id')
                     ->setParam(':id_officer', $idOfficer)
                     ->setParam(':id', $id)
                     ->setParam(':date', date(Database::DB_DATE_FORMAT))
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function insertNewDocument(array $data) {
        return $this->insertNew($data, 'documents');
    }

    public function getStandardFilteredDocuments(string $filter) {
        $qb = $this->composeQueryStandardDocuments();
        $this->addFilterCondition($filter, $qb);

        $rows = $qb->execute()->fetch();

        $documents = array();
        foreach($rows as $row) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    public function getStandardDocuments(?int $idFolder, ?string $filter, int $limit) {
        $qb = $this->composeQueryStandardDocuments();

        if($idFolder != null) {
            $qb ->andWhere('id_folder=:id_folder')->setParam(':id_folder', $idFolder);
        }

        if($filter != null) {
            $this->addFilterCondition($filter, $qb);
        }

        $qb->limit($limit);

        $rows = $qb->execute()->fetch();

        $documents = array();
        foreach($rows as $row) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    public function getStandardDocumentsInIdFolder(int $idFolder) {
        $qb = $this->composeQueryStandardDocuments();

        $qb ->andWhere('id_folder=:id_folder')->setParam(':id_folder', $idFolder);

        $rows = $qb->execute()->fetch();

        $documents = array();
        foreach($rows as $row) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    public function composeQueryStandardDocuments(bool $ignoreDeleted = true) {
        $qb = $this->qb(__METHOD__);

        $qb ->select('*')
            ->from('documents');

        if($ignoreDeleted) {
            $qb ->where('is_deleted=:deleted')
                ->setParam(':deleted', '0');
        }

        return $qb;
    }

    private function createDocumentObjectFromDbRow($row) {
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
                $qb ->andWhere('status=:status')->setParam(':status', DocumentStatus::ARCHIVATION_APPROVED);
                break;

            case 'new':
                $qb ->andWhere('status=:status')->setParam(':status', DocumentStatus::NEW);
                break;
        }
    }
}

?>