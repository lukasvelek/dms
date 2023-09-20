<?php

namespace DMS\App\Core\DB;

use QueryBuilder\IDbQueriable;

class Database implements IDbQueriable {
    /**
     * @var array
     */
    private $config;

    private $conn;

    /**
     * @var \DMS\Core\Logger\Logger
     */
    private $logger;

    /**
     * @var int
     */
    private $transactionCount;

    public function __construct(string $dbServer, string $dbUser, string $dbPass, ?string $dbName, \DMS\Core\Logger\Logger $logger) {
        $this->config = array();
        $this->conn = null;
        $this->transactionCount = 0;

        $this->logger = $logger;

        $this->config['dbServer'] = $dbServer;
        $this->config['dbUser'] = $dbUser;
        $this->config['dbPass'] = $dbPass;

        if(!is_null($dbName)) {
            $this->config['dbName'] = $dbName;
        }

        $this->startConnection();
    }
    
    public function query(string $sql) {
        if(!is_null($this->conn)) {
            return $this->conn->query($sql);
        } else {
            return null;
        }
    }

    public function beginTransaction() {
        if($this->transactionCount == 0) {
            $sql = 'BEGIN TRANSACTION';

            $this->query($sql);

            $this->transactionCount++;
        }

        return true;
    }

    public function commit() {
        if($this->transactionCount == 1) {
            $sql = 'COMMIT';

            $this->query($sql);
        }

        $this->transactionCount--;

        return true;
    }

    public function rollback() {
        $sql = 'ROLLBACK';

        $this->query($sql);

        $this->transactionCount = 0;

        return true;
    }

    public function createQueryBuilder(string $sql) {
        return new \QueryBuilder\QueryBuilder($this, $this->logger);
    }

    private function startConnection() {
        if(!empty($this->config)) {
            if(isset($this->config['dbName'])) {
                $this->conn = new \mysqli($this->config['dbServer'], $this->config['dbUser'], $this->config['dbPass'], $this->config['dbName']);
            } else {
                $this->conn = new \mysqli($this->config['dbServer'], $this->config['dbUser'], $this->config['dbPass']);
            }
        }
    }
}

?>