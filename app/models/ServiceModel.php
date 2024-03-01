<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\ServiceEntity;

class ServiceModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getAllServicesOrderedByLastRunDate() {
        $sql = "SELECT services.*, service_log.* FROM services JOIN service_log ON services.system_name = service_log.name ORDER BY service_log.date_created DESC";

        $rows = $this->db->query($sql);

        $services = [];
        foreach($rows as $row) {
            $services[] = $this->createServiceObjectFromDbRow($row);
        }
        return $services;
    }

    public function getAllServices() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('services')
            ->execute();

        $services = [];
        while($row = $qb->fetchAssoc()) {
            $services[] = $this->createServiceObjectFromDbRow($row);
        }

        return $services;
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

    private function createServiceObjectFromDbRow($row) {
        $id = $row['id'];
        $dateCreated = $row['date_created'];
        $systemName = $row['system_name'];
        $displayName = $row['display_name'];
        $description = $row['description'];
        $isEnabled = $row['is_enabled'];
        $isSystem = $row['is_system'];

        if($isEnabled == '1') {
            $isEnabled = true;
        } else {
            $isEnabled = false;
        }

        if($isSystem == '1') {
            $isSystem = true;
        } else {
            $isSystem = false;
        }

        return new ServiceEntity($id, $dateCreated, $systemName, $displayName, $description, $isEnabled, $isSystem);
    }
}

?>