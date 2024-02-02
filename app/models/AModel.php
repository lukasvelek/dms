<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use QueryBuilder\QueryBuilder;

abstract class AModel {
    public const VIEW = 'can_see';
    public const EDIT = 'can_edit';
    public const DELETE = 'can_delete';

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

    protected function updateExisting(string $tableName, int $id, array $data) {
        $qb = $this->qb(__METHOD__);

        $values = [];
        $params = [];

        foreach($data as $k => $v) {
            $values[$k] = ':' . $k;
            $params[':' . $k] = $v;
        }

        $result = $qb->update($tableName)
                     ->set($values)
                     ->setParams($params)
                     ->where('id=:id')
                     ->setParam(':id', $id)
                     ->execute()
                     ->fetch();

        return $result;
    }

    protected function updateExistingQb(string $tableName, array $data) {
        $qb = $this->qb(__METHOD__);

        $values = [];
        $params = [];

        foreach($data as $k => $v) {
            $values[$k] = ':' . $k;
            $params[':' . $k] = $v;
        }

        $qb->update($tableName)
           ->set($values)
           ->setParams($params);

        return $qb;    
    }

    protected function getLastInsertedRow(string $tableName, string $ordedCol = 'id', string $orded = 'DESC') {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from($tableName)
                  ->orderBy($ordedCol, $orded)
                  ->limit('1')
                  ->execute()
                  ->fetchSingle();

        return $row;
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

    public function beginTran() {
        return $this->db->beginTransaction();
    }

    public function commitTran() {
        return $this->db->commit();
    }

    public function rollbackTran() {
        return $this->db->rollback();
    }

    public function getRowCount(string $tableName, string $rowName = 'id', ?string $condition = null) {
        $sql = "SELECT COUNT(`$rowName`) AS `count` FROM `$tableName`";

        if(!is_null($condition)) {
            $sql .= ' ' . $condition;
        }

        $this->logger->sql($sql, __METHOD__);

        $count = 0;

        $rows = $this->db->query($sql);

        foreach($rows as $row) {
            $count = $row['count'];
        }

        return $count;
    }

    public function getFirstRowWithCountWithCond(int $count, string $tableName, array $cols, string $orderBy = 'id', string $condition) {
        $sql = "SELECT * FROM (SELECT ROW_NUMBER() OVER (ORDER BY `$orderBy`) AS `row_num`";

        $i = 0;
        foreach($cols as $col) {
            if(($i + 1) == count($cols)) {
                $sql .= ", $col";
            } else {
                $sql .= ", $col";
            }

            $i++;
        }

        $sql .= " FROM `$tableName` $condition) `t2` WHERE `row_num` = $count";

        $this->logger->sql($sql, __METHOD__);

        $row = $this->db->query($sql);

        if(count($cols) == 1) {
            $result = null;

            foreach($row as $r) {
                $result = $r[$cols[0]];
                break;
            }

            return $result;
        } else {
            return $row;
        }
    }

    public function getFirstRowWithCount(int $count, string $tableName, array $cols, string $orderBy = 'id') {
        $sql = "SELECT * FROM (SELECT ROW_NUMBER() OVER (ORDER BY `$orderBy`) AS `row_num`";

        $i = 0;
        foreach($cols as $col) {
            if(($i + 1) == count($cols)) {
                $sql .= ", $col";
            } else {
                $sql .= ", $col";
            }

            $i++;
        }

        $sql .= " FROM `$tableName`) `t2` WHERE `row_num` = $count";

        $this->logger->sql($sql, __METHOD__);

        $row = $this->db->query($sql);

        if(count($cols) == 1) {
            $result = null;

            foreach($row as $r) {
                $result = $r[$cols[0]];
                break;
            }

            return $result;
        } else {
            return $row;
        }
    }

    public function query(string $sql) {
        return $this->db->query($sql);
    }
}

?>