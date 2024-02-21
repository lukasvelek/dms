<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class ServiceModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getIdServiceConfigForNameAndKey(string $name, string $key) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['id'])
            ->from('service_config')
            ->where('name = ?', [$name])
            ->andWhere('key = ?', [$key])
            ->execute();

        return $qb->fetch('id');
    }

    public function getServiceLogLastEntryForServiceName(string $serviceName) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('service_log')
            ->where('name = ?', [$serviceName])
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->execute();

        return $qb->fetch();
    }

    public function insertServiceLog(array $data) {
        return $this->insertNew($data, 'service_log');
    }

    public function updateService(string $name, string $key, string $value) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('service_config')
            ->set(['value' => $value])
            ->where('name = ?', [$name])
            ->andWhere('`key` = ?', [$key])
            ->execute();

        return $qb->fetchAll();
    }

    public function getConfigForServiceName(string $name) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('service_config')
            ->where('name = ?', [$name])
            ->execute();

        $cfg = [];
        while($row = $qb->fetchAssoc()) {
            $cfg[$row['key']] = $row['value'];
        }

        return $cfg;
    }
}

?>