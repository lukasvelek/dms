<?php

namespace DMS\Authorizators;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\User;

abstract class AAuthorizator {
    private Database $db;
    private Logger $logger;
    protected int $idUser;

    protected function __construct(Database $db, Logger $logger, ?User $user) {
        $this->db = $db;
        $this->logger = $logger;

        if($user != null) {
            $this->idUser = $user->getId();
        }
    }

    public function setIdUser($idUser) {
        $this->idUser = $idUser;
    }

    protected function qb(string $methodName) {
        $qb = $this->db->createQueryBuilder();
        $qb->setMethod($methodName);

        return $qb;
    }
}

?>