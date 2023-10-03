<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Document;

class DocumentModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getStandardDocuments() {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('documents')
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

        return new Document($id, $dateCreated, $idAuthor, $idOfficer, $name, $status, $idManager);
    }
}

?>