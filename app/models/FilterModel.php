<?php

namespace DMS\Models;

use DMS\Constants\Metadata\DocumentFilterMetadata;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\DocumentFilter;

class FilterModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function deleteDocumentFilter(int $id) {
        return $this->deleteById($id, 'document_filters');
    }

    public function updateDocumentFilter(array $data, int $id) {
        return $this->updateExisting('document_filters', $id, $data);
    }

    public function getDocumentFilterById(int $id) {
        $qb = $this->composeStandardDocumentFilterQuery();

        $qb ->where(DocumentFilterMetadata::ID . ' = ?', [$id])
            ->execute();

        return $this->createDocumentFilterFromDbRow($qb->fetch());
    }

    public function insertNewDocumentFilter(array $data) {
        return $this->insertNew($data, 'document_filters');
    }

    public function getAllDocumentFiltersForIdUser(int $idUser) {
        $qb = $this->composeStandardDocumentFilterQuery();

        $qb ->where(DocumentFilterMetadata::ID_AUTHOR . ' = ?', [$idUser])
            ->execute();

        return $this->createDocumentsFiltersFromDbRows($qb->fetchAll());
    }

    public function getAllDocumentFilters(bool $appendSystemFilters = true, bool $appendOtherUsersResults = true, ?int $idUser = null) {
        $qb = $this->composeStandardDocumentFilterQuery();

        if($appendOtherUsersResults === TRUE) {
            $qb ->orWhere(DocumentFilterMetadata::ID_AUTHOR . ' IS NOT NULL');
        }
        if($appendSystemFilters === TRUE) {
            $qb ->orWhere(DocumentFilterMetadata::ID_AUTHOR . ' IS NULL');
        }
        if($idUser !== NULL) {
            $qb ->orWhere(DocumentFilterMetadata::ID_AUTHOR . ' = ?', [$idUser]);
        }
        
        $qb->execute();

        return $this->createDocumentsFiltersFromDbRows($qb->fetchAll());
    }

    private function composeStandardDocumentFilterQuery() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
           ->from('document_filters');

        return $qb;
    }

    private function createDocumentsFiltersFromDbRows($rows) {
        $objects = [];

        foreach($rows as $row) {
            $objects[] = $this->createDocumentFilterFromDbRow($row);
        }

        return $objects;
    }

    private function createDocumentFilterFromDbRow($row) {
        $id = $row[DocumentFilterMetadata::ID];
        $name = $row[DocumentFilterMetadata::NAME];
        $sql = $row[DocumentFilterMetadata::FILTER_SQL];
        $hasOrdering = false;

        if($row[DocumentFilterMetadata::HAS_ORDERING] == '1') {
            $hasOrdering = true;
        }

        $description = null;
        $idAuthor = null;

        if(isset($row[DocumentFilterMetadata::ID_AUTHOR])) {
            $idAuthor = $row[DocumentFilterMetadata::ID_AUTHOR];
        }

        if(isset($row[DocumentFilterMetadata::DESCRIPTION])) {
            $description = $row[DocumentFilterMetadata::DESCRIPTION];
        }

        return new DocumentFilter($id, $idAuthor, $name, $description, $sql, $hasOrdering);
    }
}

?>