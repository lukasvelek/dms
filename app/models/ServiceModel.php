<?php

namespace DMS\Models;

use DMS\Constants\Metadata\ServiceConfigMetadata;
use DMS\Constants\Metadata\ServiceLogMetadata;
use DMS\Constants\Metadata\ServiceMetadata;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\ServiceEntity;

class ServiceModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function updateService(int $id, array $data) {
        return $this->updateExisting('services', $id, $data);
    }

    public function getServiceByName(string $name) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('services')
            ->where(ServiceMetadata::SYSTEM_NAME . ' = ?', [$name])
            ->execute();

        return $this->createServiceObjectFromDbRow($qb->fetch());
    }

    public function getServiceById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('services')
            ->where(ServiceMetadata::ID . ' = ?', [$id])
            ->execute();

        return $this->createServiceObjectFromDbRow($qb->fetch());
    }

    public function insertNewService(array $data) {
        return $this->insertNew($data, 'services');
    }

    public function getAllServicesOrderedByLastRunDate() {
        $sql = "SELECT services.*, service_log.date_created, service_log.name FROM services JOIN service_log ON services.system_name = service_log.name ORDER BY service_log.date_created DESC";
        $this->logger->sql($sql, __METHOD__);

        $rows = $this->db->query($sql);

        $services = [];
        $serviceNames = [];
        foreach($rows as $row) {
            if(!in_array($row['system_name'], $serviceNames)) {
                $serviceNames[] = $row['system_name'];
                $services[] = $this->createServiceObjectFromDbRow($row);
            }
        }

        $qb = $this->qb(__METHOD__);

        $xb = $this->xb();

        $i = 0;
        foreach($serviceNames as $sn) {
            $xb ->lb()
                    ->where(ServiceMetadata::SYSTEM_NAME . ' <> ?', [$sn])
                ->rb();

            if(($i + 1) != count($serviceNames)) {
                $xb->and();
            }

            $i++;
        }
        
        $qb ->select(['*'])
            ->from('services')
            ->where($xb->build())
            ->execute();

        while($row = $qb->fetchAssoc()) {
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
            ->where(ServiceLogMetadata::NAME . ' = ?', [$serviceName])
            ->orderBy(ServiceLogMetadata::ID, 'DESC')
            ->limit(1)
            ->execute();

        return $qb->fetch();
    }

    public function insertServiceLog(array $data) {
        return $this->insertNew($data, 'service_log');
    }

    public function updateServiceConfig(string $name, string $key, string $value) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('service_config')
            ->set([ServiceConfigMetadata::VALUE => $value])
            ->where(ServiceConfigMetadata::NAME . ' = ?', [$name])
            ->andWhere('`' . ServiceConfigMetadata::KEY . '` = ?', [$key])
            ->execute();

        return $qb->fetchAll();
    }

    public function getConfigForServiceName(string $name) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('service_config')
            ->where(ServiceConfigMetadata::NAME . ' = ?', [$name])
            ->execute();

        $cfg = [];
        while($row = $qb->fetchAssoc()) {
            $cfg[$row[ServiceConfigMetadata::KEY]] = $row[ServiceConfigMetadata::VALUE];
        }

        return $cfg;
    }

    private function createServiceObjectFromDbRow($row) {
        $id = $row[ServiceMetadata::ID];
        $dateCreated = $row[ServiceMetadata::DATE_CREATED];
        $systemName = $row[ServiceMetadata::SYSTEM_NAME];
        $displayName = $row[ServiceMetadata::DISPLAY_NAME];
        $description = $row[ServiceMetadata::DESCRIPTION];
        $isEnabled = $row[ServiceMetadata::IS_ENABLED];
        $isSystem = $row[ServiceMetadata::IS_SYSTEM];
        $status = $row['status'];
        $pid = null;

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

        if(isset($row['pid'])) {
            $pid = $row['pid'];
        }

        return new ServiceEntity($id, $dateCreated, $systemName, $displayName, $description, $isEnabled, $isSystem, $status, $pid);
    }
}

?>