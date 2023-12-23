<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use QueryBuilder\QueryBuilder;

abstract class AModel {
    protected Database $db;
    private Logger $logger;

    /**
     * Constructor for the common model abstract class
     * 
     * @param Database $db Database connection
     * @param Logger $logger Logger instance
     */
    protected function __construct(Database $db, Logger $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Returns Query Builder instance
     * 
     * @param string $methodName Calling method name
     * @return QueryBuilder QueryBuilder instance
     */
    protected function qb(string $methodName) : QueryBuilder {
        $qb = $this->db->createQueryBuilder();
        $qb->setMethod($methodName);
        return $qb;
    }

    /**
     * Inserts a new entity to the database
     * 
     * @param array $data Array of values indexed by table col names
     * @param string $tableName Name of the database table
     * @return mixed $result Result of the insert operation
     */
    protected function insertNew(array $data, string $tableName) {
        $qb =  $this->qb(__METHOD__);

        $keys = [];
        $values = [];
        $params = [];

        foreach($data as $k => $v) {
            $keys[] = $k;
            $values[] = ':' . $k;
            $params[':' . $k] = $v;
        }

        $result = $qb->insertArr($tableName, $keys)
                     ->valuesArr($values)
                     ->setParams($params)
                     ->execute()
                     ->fetch();

        return $result;
    }

    protected function deleteById(int $id, string $tableName) {
        return $this->deleteByCol('id', $id, $tableName);
    }

    protected function deleteByCol(string $colName, string $colValue, string $tableName) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->delete()
                     ->from($tableName)
                     ->where($colName . '=:' . $colName)
                     ->setParam(':' . $colName, $colValue)
                     ->execute()
                     ->fetch();

        return $result;
    }
}

?>