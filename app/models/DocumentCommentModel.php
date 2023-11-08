<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\DocumentComment;

class DocumentCommentModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function insertComment(array $data) {
        return $this->insertNew($data, 'document_comments');
    }

    public function getCommentsForIdDocument(int $id) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('document_comments')
                   ->where('id_document=:id_document')
                   ->setParam(':id_document', $id)
                   ->orderBy('id', 'DESC')
                   ->execute()
                   ->fetch();

        $comments = [];
        foreach($rows as $row) {
            $comments[] = $this->createCommentObjectFromDbRow($row);
        }

        return $comments;
    }

    private function createCommentObjectFromDbRow($row) {
        $id = $row['id'];
        $dateCreated = $row['date_created'];
        $idAuthor = $row['id_author'];
        $idDocument = $row['id_document'];
        $text = $row['text'];

        return new DocumentComment($id, $dateCreated, $idAuthor, $text, $idDocument);
    }
}

?>