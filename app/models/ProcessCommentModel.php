<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\ProcessComment;

class ProcessCommentModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getLastInsertedCommentForIdUserAndIdProcess(int $idAuthor, int $idProcess) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('process_comments')
                  ->where('id_author=:id_author')
                  ->andWhere('id_process=:id_process')
                  ->setParams(array(
                    ':id_author' => $idAuthor,
                    ':id_process' => $idProcess
                  ))
                  ->orderBy('id', 'DESC')
                  ->limit('1')
                  ->execute()
                  ->fetchSingle();

        return $this->createCommentObjectFromDbRow($row);
    }

    public function deleteComment(int $id) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->delete()
                     ->from('process_comments')
                     ->where('id=:id')
                     ->setParam(':id', $id)
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function insertComment(array $data) {
        return $this->insertNew($data, 'process_comments');
    }

    public function getCommentsForIdProcess(int $id) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('process_comments')
                   ->where('id_process=:id_process')
                   ->setParam(':id_process', $id)
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
        $idProcess = $row['id_process'];
        $text = $row['text'];

        return new ProcessComment($id, $dateCreated, $idAuthor, $text, $idProcess);
    }
}

?>