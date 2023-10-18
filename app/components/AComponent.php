<?php

namespace DMS\Components;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

abstract class AComponent {
    private Database $db;
    private Logger $logger;

    protected function __construct(Database $db, Logger $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }

    protected function qb(string $methodName) {
        $qb = $this->db->createQueryBuilder();
        $qb->setMethod($methodName);

        return $qb;
    }
}

?>