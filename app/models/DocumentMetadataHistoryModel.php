<?php

namespace DMS\Models;

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
            ->where($qb->getColumnInValues('id_document', $idDocuments))
            ->execute();

        return $qb->fetchAll();
    }

    public function deleteEntriesForIdDocument(int $idDocument) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('document_metadata_history')
            ->where('id_document = ?', [$idDocument])
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
            $tmp = [
                'metadata_name' => $metadata,
                'metadata_value' => $value,
                'id_document' => $idDocument,
                'id_user' => $idUser
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
            ->where('id_document = ?', [$idDocument])
            ->orderBy('date_created', $orderByDateCreated)
            ->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = $this->createObjectFromDbRow($row);
        }

        return $entities;
    }

    private function createObjectFromDbRow($row) {
        $id = $row['id'];
        $dateCreated = $row['date_created'];
        $idDocument = $row['id_document'];
        $idUser = $row['id_user'];
        $metadataName = $row['metadata_name'];
        $metadataValue = $row['metadata_value'];

        return new DocumentMetadataHistoryEntity($id, $dateCreated, $idDocument, $idUser, $metadataName, $metadataValue);
    }
}

?>