<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\DocumentFilter;

class FilterModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getAllDocumentFiltersForIdUser(int $idUser) {
        $qb = $this->composeStandardDocumentFilterQuery();

        $rows = $qb->where('id_author=:id_user')
                   ->setParam(':id_user', $idUser)
                   ->execute()
                   ->fetch();

        return $this->createDocumentsFiltersFromDbRows($rows);
    }

    public function getAllDocumentFilters() {
        $qb = $this->composeStandardDocumentFilterQuery();

        $rows = $qb->execute()->fetch();

        return $this->createDocumentsFiltersFromDbRows($rows);
    }

    private function composeStandardDocumentFilterQuery() {
        $qb = $this->qb(__METHOD__);

        $qb->select('*')
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

        $description = null;
        $idAuthor = null;

        if(isset($row['id_author'])) {
            $idAuthor = $row['id_author'];
        }

        if(isset($row['description'])) {
            $description = $row['description'];
        }

        return new DocumentFilter($id, $idAuthor, $name, $description, $sql);
    }
}

?>