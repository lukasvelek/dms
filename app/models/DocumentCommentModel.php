<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\DocumentComment;

class DocumentCommentModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function removeCommentsForIdDocument(int $idDocument) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('document_comments')
            ->where('id_document = ?', [$idDocument])
            ->execute();

        return $qb->fetchAll();
    }

    public function getCommentById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('document_comments')
            ->where('id = ?', [$id])
            ->execute();

        return $this->createCommentObjectFromDbRow($qb->fetch());
    }

    public function getLastInsertedCommentForIdUserAndIdDocument(int $idAuthor, int $idDocument) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('document_comments')
            ->where('id_author = ?', [$idAuthor])
            ->andWhere('id_document = ?', [$idDocument])
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->execute();

        return $this->createCommentObjectFromDbRow($qb->fetch());
    }

    public function deleteComment(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('document_comments')
            ->where('id = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function insertComment(array $data) {
        return $this->insertNew($data, 'document_comments');
    }

    public function getCommentsForIdDocument(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('document_comments')
            ->where('id_document = ?', [$id])
            ->orderBy('id', 'DESC')
            ->execute();

        $comments = [];
        while($row = $qb->fetchAssoc()) {
            $comments[] = $this->createCommentObjectFromDbRow($row);
        }
    
        return $comments;
    }

    private function createCommentObjectFromDbRow($row) {
        if($row === NULL) {
            return null;
        }
        
        $id = $row['id'];
        $dateCreated = $row['date_created'];
        $idAuthor = $row['id_author'];
        $idDocument = $row['id_document'];
        $text = $row['text'];

        return new DocumentComment($id, $dateCreated, $idAuthor, $text, $idDocument);
    }
}

?>