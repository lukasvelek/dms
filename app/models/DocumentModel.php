<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Document;

class DocumentModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
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

    public function insertNewDocument(string $name, int $idManager, int $idAuthor, int $status, int $idGroup) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->insert('documents', 'name', 'id_manager', 'id_author', 'status', 'id_group')
                     ->values(':name', ':id_manager', ':id_author', ':status', ':id_group')
                     ->setParams(array(
                        ':name' => $name,
                        ':id_manager' => $idManager,
                        ':id_author' => $idAuthor,
                        ':status' => $status,
                        ':id_group' => $idGroup
                     ))
                     ->execute()
                     ->fetch();

        return $result;
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
        $idGroup = $row['id_group'];

        return new Document($id, $dateCreated, $idAuthor, $idOfficer, $name, $status, $idManager, $idGroup);
    }
}

?>