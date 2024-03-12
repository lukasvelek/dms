<?php

namespace DMS\Models;

use DMS\Constants\Metadata\DocumentCommentsMetadata;
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
            ->where(DocumentCommentsMetadata::ID_DOCUMENT . ' = ?', [$idDocument])
            ->execute();

        return $qb->fetchAll();
    }

    public function getCommentById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('document_comments')
            ->where(DocumentCommentsMetadata::ID . ' = ?', [$id])
            ->execute();

        return $this->createCommentObjectFromDbRow($qb->fetch());
    }

    public function getLastInsertedCommentForIdUserAndIdDocument(int $idAuthor, int $idDocument) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('document_comments')
            ->where(DocumentCommentsMetadata::ID_AUTHOR . ' = ?', [$idAuthor])
            ->andWhere(DocumentCommentsMetadata::ID_DOCUMENT . ' = ?', [$idDocument])
            ->orderBy(DocumentCommentsMetadata::ID, 'DESC')
            ->limit(1)
            ->execute();

        return $this->createCommentObjectFromDbRow($qb->fetch());
    }

    public function deleteComment(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('document_comments')
            ->where(DocumentCommentsMetadata::ID . ' = ?', [$id])
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
            ->where(DocumentCommentsMetadata::ID_DOCUMENT . ' = ?', [$id])
            ->orderBy(DocumentCommentsMetadata::ID, 'DESC')
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
        
        $id = $row[DocumentCommentsMetadata::ID];
        $dateCreated = $row[DocumentCommentsMetadata::DATE_CREATED];
        $idAuthor = $row[DocumentCommentsMetadata::ID_AUTHOR];
        $idDocument = $row[DocumentCommentsMetadata::ID_DOCUMENT];
        $text = $row[DocumentCommentsMetadata::TEXT];

        return new DocumentComment($id, $dateCreated, $idAuthor, $text, $idDocument);
    }
}

?>