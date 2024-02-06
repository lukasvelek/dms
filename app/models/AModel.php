<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use QueryBuilder\ExpressionBuilder;
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
        $qb->setCallingMethod($methodName);
        return $qb;
    }

    protected function xb() {
        return new ExpressionBuilder();
    }

    protected function updateExisting(string $tableName, int $id, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update($tableName)
            ->set($data)
            ->where('id = ?', [$id])
            ->execute();
        
        return $qb->fetchAll();
    }

    protected function getLastInsertedRow(string $tableName, string $orderCol = 'id', string $order = 'DESC') {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from($tableName)
            ->orderBy($orderCol, $order)
            ->execute();
        
        return $qb->fetchAll();
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

    protected function deleteById(int $id, string $tableName) {
        return $this->deleteByCol('id', $id, $tableName);
    }

    protected function deleteByCol(string $colName, string $colValue, string $tableName) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from($tableName)
            ->where($colName . ' = ?', [$colValue])
            ->execute();

        return $qb->fetchAll();
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

    public function updateToNull(string $tableName, int $id, array $cols) {
        $qb = $this->qb(__METHOD__);

        $qb ->update($tableName)
            ->setNull($cols)
            ->where('id = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }
}

?>