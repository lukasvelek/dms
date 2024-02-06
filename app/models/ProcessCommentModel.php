<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\ProcessComment;

class ProcessCommentModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function removeProcessCommentsForIdProcess(int $idProcess) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('process_comments')
            ->where('id_process = ?', [$idProcess])
            ->execute();

        return $qb->fetchAll();
    }

    public function getLastInsertedCommentForIdUserAndIdProcess(int $idAuthor, int $idProcess) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('process_comments')
            ->where('id_author = ?', [$idAuthor])
            ->andWhere('id_process = ?', [$idProcess])
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->execute();

        return $this->createCommentObjectFromDbRow($qb->fetch());
    }

    public function deleteComment(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('process_comments')
            ->where('id = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function insertComment(array $data) {
        return $this->insertNew($data, 'process_comments');
    }

    public function getCommentsForIdProcess(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('process_comments')
            ->where('id_process = ?', [$id])
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
        $idProcess = $row['id_process'];
        $text = $row['text'];

        return new ProcessComment($id, $dateCreated, $idAuthor, $text, $idProcess);
    }
}

?>