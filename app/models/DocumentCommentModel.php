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

        /*$result = $qb->delete()
                     ->from('document_comments')
                     ->where('id_document=:id_document')
                     ->setParam(':id_document', $idDocument)
                     ->execute()
                     ->fetch();

        return $result;*/

        $qb ->delete()
            ->from('document_comments')
            ->where('id_document = ?', [$idDocument])
            ->execute();

        return $qb->fetchAll();
    }

    public function getCommentById(int $id) {
        $qb = $this->qb(__METHOD__);

        /*$row = $qb->select('*')
                  ->from('document_comments')
                  ->where('id=:id')
                  ->setParam(':id', $id)
                  ->execute()
                  ->fetchSingle();

        return $this->createCommentObjectFromDbRow($row);*/

        $qb ->select(['*'])
            ->from('document_comments')
            ->where('id = ?', [$id])
            ->execute();

        return $this->createCommentObjectFromDbRow($qb->fetch());
    }

    public function getLastInsertedCommentForIdUserAndIdDocument(int $idAuthor, int $idDocument) {
        $qb = $this->qb(__METHOD__);

        /*$row = $qb->select('*')
                  ->from('document_comments')
                  ->where('id_author=:id_author')
                  ->andWhere('id_document=:id_document')
                  ->setParams(array(
                    ':id_author' => $idAuthor,
                    ':id_document' => $idDocument
                  ))
                  ->orderBy('id', 'DESC')
                  ->limit('1')
                  ->execute()
                  ->fetchSingle();

        return $this->createCommentObjectFromDbRow($row);*/

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

        /*$result = $qb->delete()
                     ->from('document_comments')
                     ->where('id=:id')
                     ->setParam(':id', $id)
                     ->execute()
                     ->fetch();

        return $result;*/

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

        /*$rows = $qb->select('*')
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

        return $comments;*/

        $qb ->select(['*'])
            ->from('document_comments')
            ->where('id_document = ?', [$id])
            ->orderBy('id', 'DESC')
            ->execute();

        $comments = [];
        foreach($qb->fetchAll() as $row) {
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