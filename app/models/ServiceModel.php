<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class ServiceModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function insertServiceLog(array $data) {
        return $this->insertNew($data, 'service_log');
    }

    public function updateService(string $name, string $key, string $value) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->update('service_config')
                     ->set(array('value' => ':value'))
                     ->where('name=:name')
                     ->andWhere('key=:key')
                     ->setParams(array(
                        ':value' => $value,
                        ':key' => $key,
                        ':name' => $name
                     ))
                     ->execute()
                     ->fetch();

        return $result;
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