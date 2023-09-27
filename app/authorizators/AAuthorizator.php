<?php

namespace DMS\Authorizators;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\User;

abstract class AAuthorizator {
    /**
     * @var Database
     */
    private $db;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var User
     */
    protected $currentUser;

    protected function __construct(Database $db, Logger $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }

    protected function qb(string $methodName) {
        $qb = $this->db->createQueryBuilder();
        $qb->setMethod($methodName);

        return $qb;
    }

    public function setCurrentUser(User $user) {
        $this->currentUser = $user;
    }

    public function getCurrentUser() {
        return $this->currentUser;
    }
}

?>