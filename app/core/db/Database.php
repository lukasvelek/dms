<?php

namespace DMS\Core\DB;

use DMS\Core\Logger\Logger;
use QueryBuilder\IDbQueriable;

/**
 * Class representing database connection
 * 
 * @author Lukas Velek
 */
class Database implements IDbQueriable {
    public const DB_DATE_FORMAT = 'Y-m-d H:i:s';

    public DatabaseInstaller $installer;

    /**
     * Configuration file
     */
    private array $config;

    private int $transactionCount;
    
    /**
     * Connection to the database server
     */
    private $conn;
    
    private Logger $logger;
    
    /**
     * Class constructor
     * 
     * @param string $dbServer DB server address
     * @param string $dbUser DB user name
     * @param string $dbPass DB user password
     * @param null|string $dbName DB name
     * @param Logger $logger Logger instance
     */
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

        $this->installer = new DatabaseInstaller($this, $this->logger);
    }
    
    /**
     * Executes a SQL string
     * 
     * @param string $sql SQL string
     * @param array $params Paramater values
     * @return null|mixed Null or result
     */
    public function query(string $sql, array $params = []) {
        if(!is_null($this->conn)) {
            if(empty($params)) {
                return $this->conn->query($sql);
            } else {
                $types = '';

                foreach($params as $param) {
                    if(is_integer($param)) {
                        $types .= 'i';
                    }
                    if(is_double($param)) {
                        $types .= 'd';
                    }
                    if(is_string($param)) {
                        $types .= 's';
                    }
                }

                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                return $stmt->execute();
            }
        } else {
            return null;
        }
    }

    /**
     * Begins a transaction
     * 
     * @return true
     */
    public function beginTransaction() {
        if($this->transactionCount == 0) {
            $sql = 'START TRANSACTION';

            $this->query($sql);

            $this->transactionCount++;
        }

        return true;
    }

    /**
     * Commits the ongoing transaction
     * 
     * @return true
     */
    public function commit() {
        if($this->transactionCount == 1) {
            $sql = 'COMMIT';

            $this->query($sql);
        }

        $this->transactionCount--;

        return true;
    }

    /**
     * Rolls back the ongoing transaction
     * 
     * @return true
     */
    public function rollback() {
        $sql = 'ROLLBACK';

        $this->query($sql);

        $this->transactionCount = 0;

        return true;
    }

    /**
     * Creates a QueryBuilder instance
     * 
     * @return QueryBuilder QueryBuilder instance
     */
    public function createQueryBuilder() {
        return new \QueryBuilder\QueryBuilder($this, $this->logger);
    }

    /**
     * Establishes the connection to the DB server
     */
    private function startConnection() {
        if(!empty($this->config)) {
            if(isset($this->config['dbName'])) {
                $this->conn = new \mysqli($this->config['dbServer'], $this->config['dbUser'], $this->config['dbPass'], $this->config['dbName']);
            } else {
                $this->conn = new \mysqli($this->config['dbServer'], $this->config['dbUser'], $this->config['dbPass']);
            }
        }
    }

    /**
     * Converts mysqli result to array
     * 
     * @param mixed $mysqli_result MySQLi result
     * @param array $keys Column names
     * @return array
     */
    public static function convertMysqliResultToArray($mysqli_result, array $keys) {
        $array = [];
        foreach($keys as $key) {
            foreach($mysqli_result as $row) {
                if(isset($row[$key])) {
                    $array[] = $row[$key];
                }
            }
        }
        return $array;
    }
}

?>