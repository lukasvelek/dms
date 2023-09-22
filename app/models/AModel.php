<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

abstract class AModel {
    /**
     * @var \DMS\Core\DB\Database
     */
    private $db;

    /**
     * @var \DMS\Core\Logger\Logger
     */
    private $logger;

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