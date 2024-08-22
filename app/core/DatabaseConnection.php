<?php

namespace App\Core;

use App\Exceptions\Core\DatabaseConnectionException;
use Exception;
use QueryBuilder\IDbQueriable;

class DatabaseConnection implements IDbQueriable {
    private \mysqli $conn;

    public function __construct(array $cfg) {
        $this->connectToDatabase($cfg);
    }

    public function beginTransaction() {
        return $this->conn->begin_transaction();
    }

    public function commit() {
        return $this->conn->commit();
    }

    public function rollback() {
        return $this->conn->rollback();
    }

    public function query(string $sql, array $params = []) {
        return $this->conn->execute_query($sql, $params);
    }

    private function connectToDatabase(array $cfg) {
        try {
            $this->conn = new \mysqli($cfg['DB_SERVER'], $cfg['DB_USER'], $cfg['DB_PASS'], $cfg['DB_NAME']);
        } catch(Exception $e) {
            throw new DatabaseConnectionException();
        }
    }
}

?>