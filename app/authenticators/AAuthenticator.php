<?php

namespace DMS\Authenticators;

use DMS\App\Core\DB\Database;
use DMS\Core\Logger\Logger;

abstract class AAuthenticator {
    /**
     * @var Database
     */
    private $db;

    /**
     * @var Logger
     */
    private $logger;

    protected function __construct(Database $db, Logger $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }

    protected function qb() {
        return $this->db->createQueryBuilder();
    }
}

?>