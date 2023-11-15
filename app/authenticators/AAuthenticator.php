<?php

namespace DMS\Authenticators;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

/**
 * Abstract class that is common for all authenticators.
 * 
 * @author Lukas Velek
 */
abstract class AAuthenticator {
    private Database $db;
    private Logger $logger;

    /**
     * Common constructor for all authenticators
     * 
     * @param Database $db Database instance
     * @param Logger $logger Logger instance
     */
    protected function __construct(Database $db, Logger $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Returns a QueryBuilder instance
     * 
     * @param string $methodName Name of the method that calls qb() - for logging purposes
     * @return QueryBuilder QueryBuilder instance
     */
    protected function qb(string $methodName) {
        $qb = $this->db->createQueryBuilder();
        $qb->setMethod($methodName);

        return $qb;
    }
}

?>