<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class MailModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getMailInQueueCount() {
        return $this->getRowCount('mail_queue');
    }

    public function insertNewQueueEntry(array $data) {
        return $this->insertNew($data, 'mail_queue');
    }

    public function getMailQueue() {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('mail_queue')
                   ->execute()
                   ->fetch();

        return $rows;
    }

    public function deleteFromQueue(int $id) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->delete()
                     ->from('mail_queue')
                     ->where('id=:id')
                     ->setParam(':id', $id)
                     ->execute()
                     ->fetch();

        return $result;
    }
}

?>