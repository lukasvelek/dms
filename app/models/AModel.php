<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use QueryBuilder\ExpressionBuilder;
use QueryBuilder\QueryBuilder;

/**
 * Common model class
 * 
 * @author Lukas Velek
 */
abstract class AModel {
    public const VIEW = 'can_see';
    public const EDIT = 'can_edit';
    public const DELETE = 'can_delete';

    protected Database $db;
    protected Logger $logger;

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
    public function qb(string $methodName) {
        $qb = $this->db->createQueryBuilder();
        $qb->setCallingMethod($methodName);
        return $qb;
    }

    /**
     * Returns Expression Builder instance
     * 
     * @return ExpressionBuilder ExpressionBuilder instance
     */
    public function xb() {
        return new ExpressionBuilder();
    }

    /**
     * Updates existing database table entry
     * 
     * @param string $tableName Database table name
     * @param int $id Database table entry ID
     * @param array $data Database table data
     * @return null|mixed Database query result
     */
    protected function updateExisting(string $tableName, int $id, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update($tableName)
            ->set($data)
            ->where('id = ?', [$id])
            ->execute();
        
        return $qb->fetchAll();
    }

    /**
     * Returns last inserted row
     * 
     * @param string $tableName Database table name
     * @param string $orderCol Orded column
     * @param string $order Ordering order
     */
    protected function getLastInsertedRow(string $tableName, string $orderCol = 'id', string $order = 'DESC') {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from($tableName)
            ->orderBy($orderCol, $order)
            ->limit(1)
            ->execute();
        
        return $qb->fetch();
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

        foreach($data as $k => $v) {
            $keys[] = $k;
            $values[] = $v;
        }

        $qb ->insert($tableName, $keys)
            ->values($values)
            ->execute();

        return $qb->fetchAll();
    }

    /**
     * Delete database table entry by ID
     * 
     * @param int $id Database table entry ID
     * @param string $tableName Database table name
     * @return null|mixed Database query result
     */
    protected function deleteById(int $id, string $tableName) {
        return $this->deleteByCol('id', $id, $tableName);
    }

    /**
     * Delete database table entry by column value
     * 
     * @param string $colName Database table column name
     * @param string $colValue Database table column value
     * @param string $tableName Database table name
     * @return null|mixed Database query result
     */
    protected function deleteByCol(string $colName, string $colValue, string $tableName) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from($tableName)
            ->where($colName . ' = ?', [$colValue])
            ->execute();

        return $qb->fetchAll();
    }

    /**
     * Begins a transaction
     * 
     * @return true
     */
    public function beginTran() {
        return $this->db->beginTransaction();
    }

    /**
     * Commits a transaction
     * 
     * @return true
     */
    public function commitTran() {
        return $this->db->commit();
    }

    /**
     * Rolls back a transaction
     * 
     * @return true
     */
    public function rollbackTran() {
        return $this->db->rollback();
    }

    /**
     * Gets row count
     * 
     * @param string $tableName Database table name
     * @param string $rowName Database table row name
     * @param null|string $codition Condition or null
     * @return int Row count
     */
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

    /**
     * Gets first row with count with condition
     * 
     * @param int $count Count
     * @param string $tableName Database table name
     * @param array $cols Database table columns
     * @param string $orderBy Order column
     * @param string $condition Condition
     * @return null|mixed Database query result or null
     */
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

    /**
     * Gets first row with count
     * 
     * @param int $count Count
     * @param string $tableName Database table name
     * @param array $cols Database table columns
     * @param string $orderBy Order column
     * @return null|mixed Database query result or null
     */
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

    /**
     * Performs a database query and returns the result
     * 
     * @param string $sql SQL string
     * @return mixed Database query result
     */
    public function query(string $sql) {
        return $this->db->query($sql);
    }

    /**
     * Updates given columns to NULL
     * 
     * @param string $tableName Database table name
     * @param int $id Row ID
     * @param array $cols Database table columns
     * @return null|mixed Result of SQL query
     */
    public function updateToNull(string $tableName, int $id, array $cols) {
        $qb = $this->qb(__METHOD__);

        $qb ->update($tableName)
            ->setNull($cols)
            ->where('id = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    /**
     * Updates existing database table entries that match the given ids
     * 
     * @param array $data Data to update
     * @param array $ids Entry IDs
     * @param string $tableName Database table name
     * @return null|mixed Result of SQL query
     */
    public function bulkUpdateExisting(array $data, array $ids, string $tableName) {
        $qb = $this->qb(__METHOD__);

        $qb ->update($tableName)
            ->set($data)
            ->where($qb->getColumnInValues('id', $ids))
            ->execute();

        return $qb->fetchAll();
    }
}

?>