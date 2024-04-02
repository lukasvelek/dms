<?php

namespace DMS\Models;

use DMS\Constants\Metadata\ProcessCommentMetadata;
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
            ->where(ProcessCommentMetadata::ID_PROCESS . ' = ?', [$idProcess])
            ->execute();

        return $qb->fetchAll();
    }

    public function getLastInsertedCommentForIdUserAndIdProcess(int $idAuthor, int $idProcess) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('process_comments')
            ->where(ProcessCommentMetadata::ID_AUTHOR . ' = ?', [$idAuthor])
            ->andWhere(ProcessCommentMetadata::ID_PROCESS . ' = ?', [$idProcess])
            ->orderBy(ProcessCommentMetadata::ID, 'DESC')
            ->limit(1)
            ->execute();

        return $this->createCommentObjectFromDbRow($qb->fetch());
    }

    public function deleteComment(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('process_comments')
            ->where(ProcessCommentMetadata::ID . ' = ?', [$id])
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
            ->where(ProcessCommentMetadata::ID_PROCESS . ' = ?', [$id])
            ->orderBy(ProcessCommentMetadata::ID, 'DESC')
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

        $id = $row[ProcessCommentMetadata::ID];
        $dateCreated = $row[ProcessCommentMetadata::DATE_CREATED];
        $idAuthor = $row[ProcessCommentMetadata::ID_AUTHOR];
        $idProcess = $row[ProcessCommentMetadata::ID_PROCESS];
        $text = $row[ProcessCommentMetadata::TEXT];

        return new ProcessComment($id, $dateCreated, $idAuthor, $text, $idProcess);
    }
}

?>