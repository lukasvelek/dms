<?php

namespace DMS\Models;

use DMS\Constants\DocumentLockStatus;
use DMS\Constants\DocumentLockType;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\DocumentLockEntity;

class DocumentLockModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getNotOverridableLocksForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['id_document'])
            ->from('document_locks')
            ->where($this->xb()->lb()
                                    ->where('id_process IS NOT NULL')
                                    ->andWhere('status = ?', [DocumentLockStatus::ACTIVE])
                                ->rb()
                                ->or()
                                ->lb()
                                    ->where('id_user IS NOT NULL')
                                    ->andWhere('status = ?', [DocumentLockStatus::ACTIVE])
                                    ->andWhere('id_user <> ?', [$idUser])
                                ->rb()
                                ->build()
                    )
            ->execute();

        $ids = [];
        while($row = $qb->fetchAssoc()) {
            $ids[] = $row['id_document'];
        }

        return $ids;
    }

    public function deleteEntriesForIdDocument(int $idDocument) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('document_locks')
            ->where('id_document = ?', [$idDocument])
            ->execute();

        return $qb->fetch();
    }

    public function getActiveLocksForIdDocuments(array $idDocuments) {
        $qb = $this->composeStandardLockQuery(__METHOD__);

        $rows = $qb ->where('status = ?', [DocumentLockStatus::ACTIVE])
                    ->andWhere($qb->getColumnInValues('id_document', $idDocuments))
                    ->execute();

        $entities = [];
        while($row = $rows->fetchAssoc()) {
            $entities[] = $this->createDocumentLockEntityFromDbRow($row);
        }

        return $entities;
    }

    public function getActiveLockForIdDocument(int $idDocument) {
        $qb = $this->composeStandardLockQuery(__METHOD__);

        $rows = $qb ->where('status = ?', [DocumentLockStatus::ACTIVE])
                    ->andWhere('id_document = ?', [$idDocument])
                    ->limit(1)
                    ->execute();

        $entity = null;
        while($row = $rows->fetchAssoc()) {
            $entity = $this->createDocumentLockEntityFromDbRow($row);
        }

        return $entity;
    }

    public function getActiveLocks() {
        $qb = $this->composeStandardLockQuery(__METHOD__);

        $rows = $qb ->where('status = ?', [DocumentLockStatus::ACTIVE])
                    ->execute();

        $entities = [];
        while($row = $rows->fetchAssoc()) {
            $entities[] = $this->createDocumentLockEntityFromDbRow($row);
        }

        return $entities;
    }

    public function getLockEntriesForIdDocument(int $idDocument) {
        $qb = $this->composeStandardLockQuery(__METHOD__);

        $rows = $qb ->where('id_document = ?', [$idDocument])
                    ->execute();

        $entries = [];
        while($row = $rows->fetchAssoc()) {
            $entries[] = $this->createDocumentLockEntityFromDbRow($row);
        }

        return $entries;
    }

    public function insertNewLock(array $data) {
        return $this->insertNew($data, 'document_locks');
    }

    public function updateLock(int $id, array $data) {
        return $this->updateExisting('document_locks', $id, $data);
    }

    public function getLockEntriesForIdDocumentForGrid(int $idDocument) {
        $qb = $this->composeStandardLockQuery(__METHOD__);

        $rows = $qb ->where('id_document = ?', [$idDocument])
                    ->orderBy('date_updated', 'DESC')
                    ->execute();

        $entries = [];
        while($row = $rows->fetchAssoc()) {
            $entries[] = $this->createDocumentLockEntityFromDbRow($row);
        }

        return $entries;
    }

    private function composeStandardLockQuery(string $method = __METHOD__) {
        $qb = $this->qb($method);

        $qb ->select(['*'])
            ->from('document_locks');

        return $qb;
    }

    private function createDocumentLockEntityFromDbRow($row) {
        $id = $row['id'];
        $dateCreated = $row['date_created'];
        $dateUpdated = $row['date_updated'];
        $idDocument = $row['id_document'];
        $idUser = null;
        $idProcess = null;
        $description = $row['description'];
        $status = $row['status'];
        
        if(isset($row['id_user'])) {
            $idUser = $row['id_user'];
        }
        if(isset($row['id_process'])) {
            $idProcess = $row['id_process'];   
        }

        return new DocumentLockEntity($id, $dateCreated, $dateUpdated, $idDocument, $idProcess, $idUser, $status, $description);
    }
}

?>