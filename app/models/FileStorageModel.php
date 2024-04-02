<?php

namespace DMS\Models;

use DMS\Constants\Metadata\FileStorageLocationMetadata;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\FileStorageLocation;

class FileStorageModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getLocationById(int $id) {
        $qb = $this->composeCommonQuery(__METHOD__);

        $qb ->where('`' . FileStorageLocationMetadata::ID . '` = ?', [$id])
            ->execute();

        return $this->createFileStorageLocationObjectFromDbRow($qb->fetch());
    }

    public function switchLocationOrder(int $id1, int $order1, int $id2, int $order2) {
        $result1 = $this->updateLocation($id1, ['`' . FileStorageLocationMetadata::ORDER . '`' => $order1]);
        $result2 = $this->updateLocation($id2, ['`' . FileStorageLocationMetadata::ORDER . '`' => $order2]);

        return $result1 && $result2;
    }

    public function getLocationByOrder(int $order) {
        $qb = $this->composeCommonQuery(__METHOD__);

        $qb ->where('`' . FileStorageLocationMetadata::ORDER . '` = ?', [$order])
            ->execute();

        return $this->createFileStorageLocationObjectFromDbRow($qb->fetch());
    }

    public function removeLocation(int $id) {
        return $this->deleteById($id, 'file_storage_locations');
    }

    public function unsetAllLocationsAsDefault(string $type) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('file_storage_locations')
            ->set([FileStorageLocationMetadata::IS_DEFAULT => '0'])
            ->where(FileStorageLocationMetadata::IS_DEFAULT . ' = 1')
            ->andWhere(FileStorageLocationMetadata::TYPE . ' = ?', [$type])
            ->execute();

        return $qb->fetchAll();
    }

    public function setLocationAsDefault(int $id) {
        return $this->updateLocation($id, [FileStorageLocationMetadata::IS_DEFAULT => '1']);
    }

    public function updateLocation(int $id, array $data) {
        return $this->updateExisting('file_storage_locations', $id, $data);
    }

    public function insertNewLocation(array $data) {
        return $this->insertNew($data, 'file_storage_locations');
    }

    public function getLastLocationOrder() {
        $qb = $this->qb(__METHOD__);

        $qb ->select([FileStorageLocationMetadata::ORDER])
            ->from('file_storage_locations')
            ->orderBy(FileStorageLocationMetadata::ORDER, 'DESC')
            ->limit(1)
            ->execute();

        return $qb->fetch(FileStorageLocationMetadata::ORDER);
    }

    public function getAllActiveFileOnlyStorageLocations(bool $order = false) {
        $qb = $this->composeCommonQuery(__METHOD__)
            ->where(FileStorageLocationMetadata::IS_ACTIVE . ' = 1')
            ->andWhere(FileStorageLocationMetadata::TYPE . ' = ?', ['files']);

        if($order === TRUE) {
            $qb->orderBy(FileStorageLocationMetadata::ORDER);
        }

        $qb->execute();

        $locations = [];
        while($row = $qb->fetchAssoc()) {
            $locations[] = $this->createFileStorageLocationObjectFromDbRow($row);
        }

        return $locations;
    }

    public function getAllActiveDocumentReportStorageLocations(bool $order = false) {
        $qb = $this->composeCommonQuery(__METHOD__)
            ->where(FileStorageLocationMetadata::IS_ACTIVE . ' = 1')
            ->andWhere(FileStorageLocationMetadata::TYPE . ' = ?', ['document_reports']);

        if($order === TRUE) {
            $qb->orderBy(FileStorageLocationMetadata::ORDER);
        }

        $qb->execute();

        $locations = [];
        while($row = $qb->fetchAssoc()) {
            $locations[] = $this->createFileStorageLocationObjectFromDbRow($row);
        }

        return $locations;
    }
    
    public function getAllActiveFileStorageLocations(bool $order = false) {
        $qb = $this->composeCommonQuery(__METHOD__)
            ->where(FileStorageLocationMetadata::IS_ACTIVE . ' = 1');

        if($order === TRUE) {
            $qb->orderBy(FileStorageLocationMetadata::ORDER);
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
            $qb->orderBy(FileStorageLocationMetadata::ORDER);
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
        $id = $row[FileStorageLocationMetadata::ID];
        $name = $row[FileStorageLocationMetadata::NAME];
        $path = $row[FileStorageLocationMetadata::PATH];
        $isDefault = $row[FileStorageLocationMetadata::IS_DEFAULT];
        $isActive = $row[FileStorageLocationMetadata::IS_ACTIVE];
        $order = $row[FileStorageLocationMetadata::ORDER];
        $isSystem = $row[FileStorageLocationMetadata::IS_SYSTEM];
        $type = $row[FileStorageLocationMetadata::TYPE];
        $absolutePath = $row[FileStorageLocationMetadata::ABSOLUTE_PATH];

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

        return new FileStorageLocation($id, $name, $path, $isDefault, $isActive, $order, $isSystem, $type, $absolutePath);
    }
}

?>