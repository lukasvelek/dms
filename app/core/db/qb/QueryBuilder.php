<?php

namespace QueryBuilder;

class QueryBuilder
{
    private const STATE_CLEAN = 1;
    private const STATE_DIRTY = 2;

    private IDbQueriable $conn;
    private ILoggerCallable $logger;
    private string $sql;
    private array $params;
    private mixed $queryResult;
    private string $queryType;
    private array $queryData;
    private bool $hasCustomParams;
    private string $callingMethod;
    private int $openBrackets;
    private int $currentState;

    public function __construct(IDbQueriable $conn, ILoggerCallable $logger, string $callingMethod = '') {
        $this->conn = $conn;
        $this->logger = $logger;

        $this->clean();
        return $this;
    }

    // WILL DEPRECATE
    public function setMethod(string $callingMethod) {
        return $this->setCallingMethod($callingMethod);
    }

    public function setCallingMethod(string $callingMethod) {
        $this->callingMethod = $callingMethod;

        return $this;
    }

    public function getColumnInValues(string $column, array $values) {
        $code = $column . ' IN (';

        $i = 0;
        foreach($values as $value) {
            if(($i + 1) == count($values)) {
                $code .= $value;
            } else {
                $code .= $value . ', ';
            }

            $i++;
        }

        $code .= ')';

        return $code;
    }

    public function getColumnNotInValues(string $column, array $values) {
        $code = $column . ' NOT IN (';

        $i = 0;
        foreach($values as $value) {
            if(($i + 1) == count($values)) {
                $code .= $value . ')';
            } else {
                $code .= $value . ', ';
            }

            $i++;
        }

        return $code;
    }

    public function delete() {
        $this->queryType = 'delete';
        $this->currentState = self::STATE_DIRTY;

        return $this;
    }

    public function update(string $tableName) {
        $this->queryType = 'update';
        $this->queryData['table'] = $tableName;
        $this->currentState = self::STATE_DIRTY;

        return $this;
    }

    public function set(array $values) {
        if(!isset($this->queryData['values'])) {
            $this->queryData['values'] = $values;
        } else{
            $this->queryData['values'] = array_merge($values, $this->queryData['values']);
        }

        return $this;
    }

    public function setNull(array $values) {
        $this->queryType = 'update_null';

        if(!isset($this->queryData['values'])) {
            $this->queryData['values'] = $values;
        } else{
            $this->queryData['values'] = array_merge($values, $this->queryData['values']);
        }

        return $this;
    }

    public function insert(string $tableName, array $keys) {
        $this->queryType = 'insert';
        $this->queryData['table'] = $tableName;
        $this->queryData['keys'] = $keys;
        $this->currentState = self::STATE_DIRTY;

        return $this;
    }

    public function values(array $values) {
        $this->queryData['values'] = $values;

        return $this;
    }

    public function select(array$keys) {
        $this->queryType = 'select';
        $this->queryData['keys'] = $keys;
        $this->currentState = self::STATE_DIRTY;

        return $this;
    }

    public function from(string $tableName) {
        $this->queryData['table'] = $tableName;

        return $this;
    }

    public function whereEx(string $where) {
        $this->queryData['where'] = $where;

        return $this;
    }

    public function where(string $cond, array $values = []) {
        if(str_contains($cond, '?') && !empty($values)) {
            $count = count(explode('?', $cond));

            if($count != (count($values) + 1)) {
                die('QueryBuilder: Number of condition parameters does not equal to the number of passed parameters!');
            }

            $search = [];

            for($i = 0; $i < ($count - 1); $i++) {
                $search[] = '?';
            }

            $tmp = [];
            foreach($values as $value) {
                $tmp[] = "'" . $value . "'";
            }

            $values = $tmp;

            $cond = str_replace($search, $values, $cond);
        }

        $this->queryData['where'] = $cond;

        return $this;
    }

    public function andWhere(string $cond, array $values = []) {
        if(!array_key_exists('where', $this->queryData)) {
            $this->queryData['where'] = '';
        }

        if(str_contains($cond, '?') && !empty($values)) {
            $count = count(explode('?', $cond));

            if($count != (count($values) + 1)) {
                die('QueryBuilder: Number of condition parameters does not equal to the number of passed parameters!');
            }

            $search = [];

            for($i = 0; $i < ($count - 1); $i++) {
                $search[] = '?';
            }

            $tmp = [];
            foreach($values as $value) {
                $tmp[] = "'" . $value . "'";
            }

            $values = $tmp;

            $cond = str_replace($search, $values, $cond);
        }

        if(!isset($this->queryData['where']) || ($this->queryData['where'] == '')) {
            $this->queryData['where'] .= $cond;    
        } else {
            $this->queryData['where'] .= ' AND ' . $cond;
        }

        return $this;
    }

    public function orWhere(string $cond, array $values = []) {
        if(!array_key_exists('where', $this->queryData)) {
            $this->queryData['where'] = '';
        }

        if(str_contains($cond, '?') && !empty($values)) {
            $count = count(explode('?', $cond));

            if($count != (count($values) + 1)) {
                die('QueryBuilder: Number of condition parameters does not equal to the number of passed parameters!');
            }

            $search = [];

            for($i = 0; $i < ($count - 1); $i++) {
                $search[] = '?';
            }

            $tmp = [];
            foreach($values as $value) {
                $tmp[] = "'" . $value . "'";
            }

            $values = $tmp;

            $cond = str_replace($search, $values, $cond);
        }

        if(!isset($this->queryData['where']) || ($this->queryData['where'] == '')) {
            $this->queryData['where'] .= $cond;    
        } else {
            $this->queryData['where'] .= ' OR ' . $cond;
        }

        return $this;
    }

    public function orderBy(string $key, string $order = 'ASC') {
        $this->queryData['order'] = ' ORDER BY `' . $key . '` ' . $order;

        return $this;
    }

    public function limit(int $limit) {
        $this->queryData['limit'] = $limit;

        return $this;
    }

    public function setParams(array $params) {
        foreach($params as $k => $v) {
            if($k[0] != ':') {
                $this->params[':' . $k] = "'" . $v . "'";
            } else {
                $this->params[$k] = "'" . $v . "'";
            }
        }

        $this->hasCustomParams = true;

        return $this;
    }

    public function leftBracket(int $count = 1) {
        $this->openBrackets += $count;

        $this->queryData['where'] .= ' ';

        for($i = 0; $i < $count; $i++) {
            $this->queryData['where'] .= '(';
        }

        $this->queryData['where'] .= ' ';

        return $this;
    }

    public function rightBracket(int $count = 1) {
        $this->openBrackets -= $count;

        $this->queryData['where'] .= ' ';

        for($i = 0; $i < $count; $i++) {
            $this->queryData['where'] .= ')';
        }

        $this->queryData['where'] .= ' ';

        return $this;
    }

    public function clean() {
        $this->sql = '';
        $this->params = [];
        $this->queryResult = null;
        $this->queryType = '';
        $this->queryData = [];
        $this->hasCustomParams = false;
        $this->openBrackets = 0;
        $this->currentState = self::STATE_CLEAN;
    }

    public function getSQL() {
        $this->createSQLQuery();

        return $this->sql;
    }

    public function execute() {
        if($this->currentState != self::STATE_DIRTY) {
            die('QueryBuilder: No query has been created!');
        }

        if($this->sql === '') {
            $this->createSQLQuery();
        }

        if($this->openBrackets > 0) {
            die('QueryBuilder: Not all brackets have been closed: ' . $this->sql);
        }

        if($this->conn === NULL) {
            //return null;
            die('QueryBuilder: No connection has been found!');
        }

        $this->queryResult = $this->conn->query($this->sql);

        $this->log();

        $this->currentState = self::STATE_CLEAN;

        return $this;
    }

    public function fetchAssoc() {
        return $this->queryResult->fetch_assoc();
    }

    public function fetchAll() {
        if($this->currentState != self::STATE_CLEAN) {
            return null;
        }

        return $this->queryResult;
    }

    public function fetch(?string $param = null) {
        $result = null;

        if($this->currentState != self::STATE_CLEAN) {
            return $result;
        }

        if($this->queryResult === NULL) {
            return $result;
        }

        if($this->queryResult->num_rows > 1) {
            return $result;
        }

        foreach($this->queryResult as $row) {
            if($param !== NULL) {
                if(array_key_exists($param, $row)) {
                    $result = $row[$param];
                    break;
                } else {
                    break;
                }
            } else {
                $result = $row;
                break;
            }
        }

        return $result;
    }

    private function createSQLQuery() {
        switch($this->queryType) {
            case 'select':
                $this->createSelectSQLQuery();
                break;

            case 'insert':
                $this->createInsertSQLQuery();
                break;

            case 'update':
                $this->createUpdateSQLQuery();
                break;

            case 'delete':
                $this->createDeleteSQLQuery();
                break;

            case 'update_null':
                $this->createUpdateNullSQLQuery();
                break;
        }

        $keys = [];
        $values = [];
        foreach($this->params as $k => $v) {
            $keys[] = $k;
            $values[] = $v;
        }

        if($this->hasCustomParams) {
            $this->sql = str_replace($keys, $values, $this->sql);
        }
    }

    private function createUpdateNullSQLQuery() {
        $sql = 'UPDATE ' . $this->queryData['table'] . ' SET ';

        $i = 0;
        foreach($this->queryData['values'] as $key) {
            if(($i + 1) == count($this->queryData['values'])) {
                $sql .= $key . ' = NULL';
            } else {
                $sql .= $key . ' = NULL, ';
            }
        }

        if(str_contains($this->queryData['where'], 'WHERE')) {
            // explicit
            $sql .= ' ' . $this->queryData['where'];
        } else {
            $sql .= ' WHERE ' . $this->queryData['where'];
        }

        $this->sql = $sql;
    }

    private function createDeleteSQLQuery() {
        $sql = 'DELETE FROM ' . $this->queryData['table'];

        if(str_contains($this->queryData['where'], 'WHERE')) {
            $sql .= ' ' . $this->queryData['where'];
        } else {
            $sql .= ' WHERE ' . $this->queryData['where'];
        }

        $this->sql = $sql;
    }

    private function createUpdateSQLQuery() {
        $sql = 'UPDATE ' . $this->queryData['table'] . ' SET ';

        $i = 0;
        foreach($this->queryData['values'] as $key => $value) {
            if(($i + 1) == count($this->queryData['values'])) {
                $sql .= $key . ' = \'' . $value . '\'';
            } else {
                $sql .= $key . ' = \'' . $value . '\', ';
            }
        }

        if(str_contains($this->queryData['where'], 'WHERE')) {
            // explicit
            $sql .= ' ' . $this->queryData['where'];
        } else {
            $sql .= ' WHERE ' . $this->queryData['where'];
        }

        $this->sql = $sql;
    }

    private function createInsertSQLQuery() {
        $sql = 'INSERT INTO ' . $this->queryData['table'] . ' (';

        $i = 0;
        foreach($this->queryData['keys'] as $key) {
            if(($i + 1) == count($this->queryData['keys'])) {
                $sql .= $key . ') VALUES (';
            } else {
                $sql  .= $key . ', ';
            }

            $i++;
        }

        $i = 0;
        foreach($this->queryData['values'] as $value) {
            if(($i + 1) == count($this->queryData['values'])) {
                $sql .= $value . ')';
            } else {
                $sql  .= $value . ', ';
            }

            $i++;
        }

        $this->sql = $sql;
    }

    private function createSelectSQLQuery() {
        $sql = 'SELECT ';

        $i = 0;
        foreach($this->queryData['keys'] as $key) {
            if(($i + 1) == count($this->queryData['keys'])) {
                if($key === '*') {
                    $sql .= $key . ' ';
                } else if(str_starts_with($key, 'COUNT')) {
                    $sql .= $key . ' ';
                } else {
                    $sql .= '`' . $key . '` ';
                }
            } else {
                $sql .= '`' . $key . '`, ';
            }

            $i++;
        }

        $sql .= 'FROM ' . $this->queryData['table'];

        if(isset($this->queryData['where'])) {
            if(str_contains($this->queryData['where'], 'WHERE')) {
                // explicit
                $sql .= ' ' . $this->queryData['where'];
            } else {
                $sql .= ' WHERE ' . $this->queryData['where'];
            }
        }

        $this->sql = $sql;
    }

    private function log() {
        if($this->logger !== NULL) {
            $this->logger->sql($this->sql, $this->callingMethod);
        }
    }
}

?>