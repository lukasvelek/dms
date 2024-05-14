<?php

namespace DMS\Models;

use DMS\Constants\Metadata\DocumentMetadataHistoryMetadata;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\DocumentMetadataHistoryEntity;

class DocumentMetadataHistoryModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function bulkDeleteEntriesForIdDocuments(array $idDocuments) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('document_metadata_history')
            ->where($qb->getColumnInValues(DocumentMetadataHistoryMetadata::ID_DOCUMENT, $idDocuments))
            ->execute();

        return $qb->fetchAll();
    }

    public function deleteEntriesForIdDocument(int $idDocument) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('document_metadata_history')
            ->where(DocumentMetadataHistoryMetadata::ID_DOCUMENT . ' = ?', [$idDocument])
            ->execute();

        return $qb->fetchAll();
    }

    public function bulkInsertNewMetadataHistoryEntriesBasedOnDocumentMetadataArray(array $data, array $ids, int $idUser) {
        foreach($ids as $id) {
            $this->insertNewMetadataHistoryEntriesBasedOnDocumentMetadataArray($data, $id, $idUser);
        }

        return true;
    }

    public function insertNewMetadataHistoryEntriesBasedOnDocumentMetadataArray(array $data, int $idDocument, int $idUser) {
        foreach($data as $metadata => $value) {
            if($value === NULL) {
                continue;
            }
            
            $tmp = [
                DocumentMetadataHistoryMetadata::METADATA_NAME => $metadata,
                DocumentMetadataHistoryMetadata::METADATA_VALUE => $value,
                DocumentMetadataHistoryMetadata::ID_DOCUMENT => $idDocument,
                DocumentMetadataHistoryMetadata::ID_USER => $idUser
            ];

            $this->insertNewMetadataHistoryEntry($tmp);
        }

        return true;
    }

    public function insertNewMetadataHistoryEntry(array $data) {
        return $this->insertNew($data, 'document_metadata_history');
    }

    public function getAllEntriesForIdDocument(int $idDocument, string $orderByDateCreated = 'DESC') {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('document_metadata_history')
            ->where(DocumentMetadataHistoryMetadata::ID_DOCUMENT . ' = ?', [$idDocument])
            ->orderBy(DocumentMetadataHistoryMetadata::DATE_CREATED, $orderByDateCreated)
            ->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = $this->createObjectFromDbRow($row);
        }

        return $entities;
    }

    private function createObjectFromDbRow($row) {
        $id = $row[DocumentMetadataHistoryMetadata::ID];
        $dateCreated = $row[DocumentMetadataHistoryMetadata::DATE_CREATED];
        $idDocument = $row[DocumentMetadataHistoryMetadata::ID_DOCUMENT];
        $idUser = $row[DocumentMetadataHistoryMetadata::ID_USER];
        $metadataName = $row[DocumentMetadataHistoryMetadata::METADATA_NAME];
        $metadataValue = $row[DocumentMetadataHistoryMetadata::METADATA_VALUE];

        return new DocumentMetadataHistoryEntity($id, $dateCreated, $idDocument, $idUser, $metadataName, $metadataValue);
    }
}

?>