<?php

namespace DMS\Models;

use DMS\Constants\DocumentLockStatus;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\DocumentLockEntity;

class DocumentLockModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
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