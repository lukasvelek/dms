<?php

namespace DMS\Models;

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

        $qb ->where('id = ?', [$id])
            ->execute();

        return $this->createDocumentFilterFromDbRow($qb->fetch());
    }

    public function insertNewDocumentFilter(array $data) {
        return $this->insertNew($data, 'document_filters');
    }

    public function getAllDocumentFiltersForIdUser(int $idUser) {
        $qb = $this->composeStandardDocumentFilterQuery();

        $qb ->where('id_author = ?', [$idUser])
            ->execute();

        return $this->createDocumentsFiltersFromDbRows($qb->fetchAll());
    }

    public function getAllDocumentFilters(bool $appendSystemFilters = true, bool $appendOtherUsersResults = true, ?int $idUser = null) {
        $qb = $this->composeStandardDocumentFilterQuery();
        
        if($appendSystemFilters && (!$appendOtherUsersResults && !is_null($idUser))) {
            $qb->andWhere('id_author IS NULL OR id_author = ?', [$idUser]);
        } else if(!$appendSystemFilters && (!$appendOtherUsersResults && !is_null($idUser))) {
            $qb->andWhere('id_author = ?', [$idUser]);
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
        $id = $row['id'];
        $name = $row['name'];
        $sql = $row['filter_sql'];
        $hasOrdering = false;

        if($row['has_ordering'] == '1') {
            $hasOrdering = true;
        }

        $description = null;
        $idAuthor = null;

        if(isset($row['id_author'])) {
            $idAuthor = $row['id_author'];
        }

        if(isset($row['description'])) {
            $description = $row['description'];
        }

        return new DocumentFilter($id, $idAuthor, $name, $description, $sql, $hasOrdering);
    }
}

?>