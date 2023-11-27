<?php

namespace DMS\Models;

use DMS\Constants\DocumentStatus;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Document;
use DMS\Helpers\ArrayHelper;

class DocumentModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getSharedDocumentsWithUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('documents')
                   ->explicit('
                   WHERE 
                        EXISTS(SELECT 1 FROM `document_sharing` 
                               WHERE `document_sharing`.`id_user`=' . $idUser . '
                               AND `document_sharing`.`id_document`=`documents`.`id`
                        )
                   ')
                   ->execute()
                   ->fetch();

        $documents = [];
        foreach($rows as $row) {
            $documents = $this->createDocumentObjectFromDbRow($row);
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

    public function getDocumentsForName(string $name, ?int $idFolder) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('documents')
                   ->where('name=:name', true)
                   ->andWhere('is_deleted=:deleted')
                   ->setParam(':name', $name)
                   ->setParam(':deleted', '0');

        if(!is_null($idFolder)) {
            $rows = $rows->andWhere('id_folder=:id_folder')->setParam(':id_folder', $idFolder);
        }

        $rows = $rows->execute()->fetch();

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
                        'status' => ':status'
                     ))
                     ->setParam(':status', $status)
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
                     ->set(array('id_officer' => ':id_officer'))
                     ->where('id=:id')
                     ->setParam(':id_officer', $idOfficer)
                     ->setParam(':id', $id)
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function insertNewDocument(array $data) {
        return $this->insertNew($data, 'documents');
    }

    public function getStandardDocuments() {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('documents')
                   ->where('is_deleted=:deleted')
                   ->setParam(':deleted', '0')
                   ->execute()
                   ->fetch();

        $documents = array();
        foreach($rows as $row) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    public function getStandardDocumentsInIdFolder(int $idFolder) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('documents')
                   ->where('is_deleted=:deleted')
                   ->andWhere('id_folder=:id_folder')
                   ->setParam(':deleted', '0')
                   ->setParam(':id_folder', $idFolder)
                   ->execute()
                   ->fetch();

        $documents = array();
        foreach($rows as $row) {
            $documents[] = $this->createDocumentObjectFromDbRow($row);
        }

        return $documents;
    }

    private function createDocumentObjectFromDbRow($row) {
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

        if(isset($row['id_folder'])) {
            $idFolder = $row['id_folder'];
        }

        if(isset($row['file'])) {
            $file = $row['file'];
        }

        ArrayHelper::deleteKeysFromArray($row, array('id', 'date_created', 'id_author', 'id_officer', 'name', 'status', 'id_manager', 'id_group', 'is_deleted', 'rank', 'id_folder', 'file', 'shred_year', 'after_shred_action', 'shredding_status'));

        $document = new Document($id, $dateCreated, $idAuthor, $idOfficer, $name, $status, $idManager, $idGroup, $isDeleted, $rank, $idFolder, $file, $shredYear, $afterShredAction, $shreddingStatus);
        $document->setMetadata($row);

        return $document;
    }
}

?>