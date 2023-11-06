<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class ServiceModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getConfigForServiceName(string $name) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('service_config')
                   ->where('name=:name')
                   ->setParam(':name', $name)
                   ->execute()
                   ->fetch();

        $cfg = [];
        foreach($rows as $row) {
            $cfg[$row['key']] = $row['value'];
        }

        return $cfg;
    }
}

?>