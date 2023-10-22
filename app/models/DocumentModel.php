<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Document;
use DMS\Helpers\ArrayHelper;

class DocumentModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
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

    public function insertNewDocument(string $name, int $idManager, int $idAuthor, int $status, int $idGroup, array $customMetadata) {
        $qb = $this->qb(__METHOD__);

        $keys = array(
            'name',
            'id_manager',
            'id_author',
            'status',
            'id_group'
        );

        $values = array(
            ':name',
            ':id_manager',
            ':id_author',
            ':status',
            ':id_group'
        );

        $params = array(
            ':name' => $name,
            ':id_manager' => $idManager,
            ':id_author' => $idAuthor,
            ':id_group' => $idGroup,
            ':status' => $status
        );

        foreach($customMetadata as $k => $v) {
            $keys[] = $k;
            $values[] = ':' . $k;
            $params[':' . $k] = $v;
        }

        $result = $qb->insertArr('documents', $keys)
                     ->valuesArr($values)
                     ->setParams($params)
                     ->execute()
                     ->fetch();

        return $result;
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

        ArrayHelper::deleteKeysFromArray($row, array('id', 'date_created', 'id_author', 'id_officer', 'name', 'status', 'id_maanger', 'id_group', 'is_deleted'));

        return new Document($id, $dateCreated, $idAuthor, $idOfficer, $name, $status, $idManager, $idGroup, $isDeleted, $row);
    }
}

?>