<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\FileStorageLocation;

class FileStorageModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getLocationById(int $id) {
        $qb = $this->composeCommonQuery(__METHOD__);

        $qb ->where('`id` = ?', [$id])
            ->execute();

        return $this->createFileStorageLocationObjectFromDbRow($qb->fetch());
    }

    public function switchLocationOrder(int $id1, int $order1, int $id2, int $order2) {
        $result1 = $this->updateLocation($id1, ['`order`' => $order1]);
        $result2 = $this->updateLocation($id2, ['`order`' => $order2]);

        return $result1 && $result2;
    }

    public function getLocationByOrder(int $order) {
        $qb = $this->composeCommonQuery(__METHOD__);

        $qb ->where('`order` = ?', [$order])
            ->execute();

        return $this->createFileStorageLocationObjectFromDbRow($qb->fetch());
    }

    public function removeLocation(int $id) {
        return $this->deleteById($id, 'file_storage_locations');
    }

    public function unsetAllLocationsAsDefault() {
        $qb = $this->qb(__METHOD__);

        $qb ->update('file_storage_locations')
            ->set(['is_default' => '0'])
            ->where('is_default = 1')
            ->execute();

        return $qb->fetchAll();
    }

    public function setLocationAsDefault(int $id) {
        return $this->updateLocation($id, ['is_default' => '1']);
    }

    public function updateLocation(int $id, array $data) {
        return $this->updateExisting('file_storage_locations', $id, $data);
    }

    public function insertNewLocation(array $data) {
        return $this->insertNew($data, 'file_storage_locations');
    }

    public function getLastLocationOrder() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['order'])
            ->from('file_storage_locations')
            ->orderBy('order', 'DESC')
            ->limit(1)
            ->execute();

        return $qb->fetch('order');
    }

    public function getAllActiveFileStorageLocations(bool $order = false) {
        $qb = $this->composeCommonQuery(__METHOD__)
            ->where('is_active = 1');

        if($order === TRUE) {
            $qb->orderBy('order');
        }

        $qb->execute();

        $locations = [];
        while($row = $qb->fetchAssoc()) {
            $locations[] = $this->createFileStorageLocationObjectFromDbRow($row);
        }

        return $locations;
    }

    public function getAllFileStorageLocations(bool $order = false) {
        $qb = $this->composeCommonQuery(__METHOD__);

        if($order === TRUE) {
            $qb->orderBy('order');
        }

        $qb->execute();

        $locations = [];
        while($row = $qb->fetchAssoc()) {
            $locations[] = $this->createFileStorageLocationObjectFromDbRow($row);
        }
        
        return $locations;
    }

    private function composeCommonQuery(?string $method) {
        $qb =  $this->qb($method ?? __METHOD__);

        $qb ->select(['*'])
            ->from('file_storage_locations');

        return $qb;
    }

    private function createFileStorageLocationObjectFromDbRow($row) {
        $id = $row['id'];
        $name = $row['name'];
        $path = $row['path'];
        $isDefault = $row['is_default'];
        $isActive = $row['is_active'];
        $order = $row['order'];
        $isSystem = $row['is_system'];

        if($isDefault == '1') {
            $isDefault = true;
        } else {
            $isDefault = false;
        }

        if($isActive == '1') {
            $isActive = true;
        } else {
            $isActive = false;
        }

        if($isSystem == '1') {
            $isSystem = true;
        } else {
            $isSystem = false;
        }

        return new FileStorageLocation($id, $name, $path, $isDefault, $isActive, $order, $isSystem);
    }
}

?>