<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class TableModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function addColToTable(string $tableName, string $colName, string $colType, int $maxLength) {
        $sql = "ALTER TABLE `$tableName` ADD `$colName` $colType($maxLength)";

        $result = $this->db->query($sql);

        return $result;
    }
}

?>