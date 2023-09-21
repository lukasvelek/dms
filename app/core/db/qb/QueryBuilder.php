<?php

namespace QueryBuilder;

/**
 * QueryBuilder allows user to simply create a SQL query and retrieve the result with just a couple of commands.
 * 
 * @version 1.1
 * @author Lukas Velek
 */
class QueryBuilder {
  /**
   * Connection to the database server
   * 
   * @var IDbQueriable
   */
  private $conn;

  /**
   * Logger instance
   * 
   * @var ILoggerCallable
   */
  private $logger;

  /**
   * SQL string
   * 
   * @var string
   */
  private $sql;

  /**
   * Query result
   * 
   * @var array
   */
  private $queryResult;

  /**
   * Method the Query Builder is called from
   * 
   * @var string
   */
  private $method;

  /**
   * Variables array
   * Indexed by variable names and values are variable values
   * E.g.: $variables['foo'] = 'bar'; would be the same as $foo = 'bar';
   */
  private $variables;

  public function __construct(IDbQueriable $conn, ?ILoggerCallable $logger = null, ?string $method = '') {
    if(!($conn instanceof IDbQueriable)) {
      die();
    }

    if($logger != null) {
      if(!($logger instanceof ILoggerCallable)) {
        die();
      }
    }

    $this->method = $method;

    $this->conn = $conn;
    $this->logger = $logger;
    $this->sql = '';
    $this->variables = array();
    $this->queryResult = null;
  }

  public function select(string ...$keys) {
    if(count($keys) == 1) {
      if($keys[0] == '*') {
        $this->sql .= 'SELECT *';
        
        return $this;
      }
    }

    $this->sql .= 'SELECT ';

    $i = 0;
    foreach($keys as $key) {
      if(count($keys) != 1) {
        if(($i + 1) == count($keys)) {
          $this->sql .= '`' . $key . '`';
        } else {
          $this->sql .= '`' . $key . '`, ';
        }
      } else {
        $this->sql .= '`' . $key . '`';
      }

      $i++;
    }

    return $this;
  }

  public function selectArr(array $keys) {
    $this->sql .= 'SELECT (';

    $i = 0;
    foreach($keys as $key) {
      if(count($keys) != 1) {
        if(($i + 1) == count($keys)) {
          $this->sql .= '`' . $key . '`) ';
        } else {
          $this->sql .= '`' . $key . '`, ';
        }
      } else {
        $this->sql .= '`' . $key . '`';
      }

      $i++;
    }

    return $this;
  }

  public function from(string ...$tables) {
    $this->sql .= ' FROM ';

    $i = 0;
    foreach($tables as $table) {
      if(($i + 1) == count($tables)) {
        $this->sql .= '`' . $table . '`';
      } else {
        $this->sql .= '`' . $table . '`, ';
      }

      $i++;
    }

    return $this;
  }

  public function where(string $text, bool $like = false, bool $renderText = true) {
    $text = trim($text);

    $dbKey = explode('=', $text)[0];
    $dbVal = explode('=', $text)[1];

    if($renderText == true) {
      $this->sql .= ' WHERE ';
    }

    $dbVal = '$' . $dbVal . '$';

    if($like == true) {
      $this->sql .= '`' . $dbKey . '` LIKE \'%' . $dbVal . '%\'';
    } else {
      $this->sql .= '`' . $dbKey . '` = \'' . $dbVal . '\'';
    }

    return $this;
  }

  public function whereNot(string $text, bool $like = false, bool $renderText = true) {
    $text = trim($text);

    $dbKey = explode('=', $text)[0];
    $dbVal = explode('=', $text)[1];

    if($renderText == true) {
      $this->sql .= ' WHERE ';
    }

    $dbVal = '$' . $dbVal . '$';

    if($like == true) {
      $this->sql .= '`' . $dbKey . '` NOT LIKE \'%' . $dbVal . '%\'';
    } else {
      $this->sql .= '`' . $dbKey . '` <> \'' . $dbVal . '\'';
    }

    return $this;
  }

  public function andWhere(string $text, bool $like = false) {
    $text = trim($text);

    $dbKey = explode('=', $text)[0];
    $dbVal = explode('=', $text)[1];

    $this->sql .= ' AND ';

    $dbVal = '$' . $dbVal . '$';

    if($like == true) {
      $this->sql .= '`' . $dbKey . '` LIKE \'%' . $dbVal . '%\'';
    } else {
      $this->sql .= '`' . $dbKey . '` = \'' . $dbVal . '\'';
    }

    return $this;
  }

  public function andWhereNot(string $text, bool $like = false) {
    $text = trim($text);

    $dbKey = explode('=', $text)[0];
    $dbVal = explode('=', $text)[1];

    $this->sql .= ' AND ';

    $dbVal = '$' . $dbVal . '$';

    if($like == true) {
      $this->sql .= '`' . $dbKey . '` NOT LIKE \'%' . $dbVal . '%\'';
    } else {
      $this->sql .= '`' . $dbKey . '` <> \'' . $dbVal . '\'';
    }

    return $this;
  }

  public function orWhere(string $text, bool $like = false) {
    $text = trim($text);

    $dbKey = explode('=', $text)[0];
    $dbVal = explode('=', $text)[1];

    $this->sql .= ' OR ';

    $dbVal = '$' . $dbVal . '$';

    if($like == true) {
      $this->sql .= '`' . $dbKey . '` LIKE \'%' . $dbVal . '%\'';
    } else {
      $this->sql .= '`' . $dbKey . '` = \'' . $dbVal . '\'';
    }

    return $this;
  }

  public function orWhereNot(string $text, bool $like = false) {
    $text = trim($text);

    $dbKey = explode('=', $text)[0];
    $dbVal = explode('=', $text)[1];

    $this->sql .= ' OR ';

    $dbVal = '$' . $dbVal . '$';

    if($like == true) {
      $this->sql .= '`' . $dbKey . '` NOT LIKE \'%' . $dbVal . '%\'';
    } else {
      $this->sql .= '`' . $dbKey . '` <> \'' . $dbVal . '\'';
    }

    return $this;
  }

  public function leftBracket() {
    $this->sql .= ' ( ';

    return $this;
  }

  public function rightBracket() {
    $this->sql .= ' ) ';

    return $this;
  }

  public function orderBy(string $key, string $ascDesc = 'ASC') {
    $this->sql .= ' ORDER BY `' . $key . '` ' . $ascDesc;

    return $this;
  }

  public function limit(string $number) {
    $this->sql .= ' LIMIT ' . $number;

    return $this;
  }

  public function update(string $tableName) {
    $this->sql .= 'UPDATE `' . $tableName . '`';

    return $this;
  }

  public function set(array $values) {
    $this->sql .= ' SET ';

    $i = 0;
    foreach($values as $k => $v) {
      $v = trim($v);
      $value = '$' . $v . '$';

      if(count($values) != 1) {
        if(($i + 1) == count($values)) {
          $this->sql .= '`' . $k . '` = \'' . $value . '\'';
        } else {
          $this->sql .= '`' . $k . '` = \'' . $value . '\', ';
        }
      } else {
        $this->sql .= '`' . $k . '` = \'' . $value . '\'';
      }

      $i++;
    }

    return $this;
  }

  public function insert(string $tableName, string ...$keys) {
    $this->sql .= 'INSERT INTO `' . $tableName . '` (';

    $i = 0;
    foreach($keys as $key) {
      if(count($keys) != 1) {
        if(($i + 1) == count($keys)) {
          $this->sql .= '`' . $key . '`';
        } else {
          $this->sql .= '`' . $key . '`, ';
        }
      } else {
        $this->sql .= '`' . $key . '`';
      }

      $i++;
    }

    $this->sql .= ')';

    return $this;
  }

  public function insertArr(string $tableName, array $keys) {
    $this->sql .= 'INSERT INTO `' . $tableName . '` (';

    $i = 0;
    foreach($keys as $key) {
      if(count($keys) != 1) {
        if(($i + 1) == count($keys)) {
          $this->sql .= '`' . $key . '`';
        } else {
          $this->sql .= '`' . $key . '`, ';
        }
      } else {
        $this->sql .= '`' . $key . '`';
      }

      $i++;
    }

    $this->sql .= ')';

    return $this;
  }

  public function values(string ...$values) {
    $this->sql .= ' VALUES (';

    $i = 0;
    foreach($values as $value) {
      $value = trim($value);
      $v = '$' . $value . '$';

      if(count($values) != 1) {
        if(($i + 1) == count($values)) {
          $this->sql .= '\'' . $v . '\'';
        } else {
          $this->sql .= '\'' . $v . '\', ';
        }
      } else {
        $this->sql .= '\'' . $v . '\'';
      }

      $i++;
    }

    $this->sql .= ')';

    return $this;
  }

  public function valuesArr(array $values) {
    $this->sql .= ' VALUES (';

    $i = 0;
    foreach($values as $value) {
      $value = trim($value);
      $v = '$' . $value . '$';

      if(count($values) != 1) {
        if(($i + 1) == count($values)) {
          $this->sql .= '\'' . $v . '\'';
        } else {
          $this->sql .= '\'' . $v . '\', ';
        }
      } else {
        $this->sql .= '\'' . $v . '\'';
      }

      $i++;
    }

    $this->sql .= ')';

    return $this;
  }

  public function delete() {
    $this->sql .= 'DELETE ';

    return $this;
  }

  public function setParam(string $key, string $value) {
    $this->variables[$key] = $value;

    return $this;
  }

  public function setParams(array $array) {
    foreach($array as $key => $value) {
      $this->variables[$key] = $value;
    }

    return $this;
  }

  public function execute() {
    $this->createQuery();

    $this->log();

    $this->queryResult = $this->conn->query($this->sql);

    return $this;
  }

  public function fetch() {
    $result = $this->queryResult;

    $this->clean();

    return $result;
  }

  public function fetchSingle(string $key = '') {
    $result = $this->queryResult;

    $this->clean();

    if($result != null) {
      foreach($result as $r) {
        if($key == '') {
          return $r;
        } else {
          return $r[$key];
        }
      }
    }

    return null;
  }

  public function setMethod(string $method) {
    $this->method = $method;
  }

  private function createQuery() {
    foreach($this->variables as $key => $value) {
      $this->sql = str_replace('$' . $key . '$', $value, $this->sql);
    }
  }

  private function clean() {
    $this->variables = array();
    $this->sql = '';
    $this->queryResult = null;
  }

  private function tryGetVariable(string $varKey) {
    if(count($this->variables) == 0) {
      return $varKey;
    }

    if(array_key_exists($varKey, $this->variables)) {
      return $this->variables[$varKey];
    }

    return $varKey;
  }

  private function log() {
    if($this->logger != null) {
      $this->logger->sql($this->sql, $this->method);
    }
  }
}

?>
