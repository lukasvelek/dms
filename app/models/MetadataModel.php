<?php

namespace DMS\Models;

use DMS\Constants\Metadata\MetadataMetadata;
use DMS\Constants\Metadata\MetadataValueMetadata;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Metadata;
use DMS\Entities\MetadataValue;

class MetadataModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getAllViewableMetadataCount(array $ids) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('metadata')
            ->where($qb->getColumnInValues(MetadataMetadata::ID, $ids))
            ->execute();

        return $qb->fetchAll()->num_rows;
    }

    public function getAllViewableMetadataWithOffset(array $ids, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('metadata')
            ->where($qb->getColumnInValues(MetadataMetadata::ID, $ids))
            ->limit($limit)
            ->offset($offset)
            ->execute();

        $metadata = [];
        while($row = $qb->fetchAssoc()) {
            $metadata[] = $this->createMetadataObjectFromDbRow($row);
        }

        return $metadata;
    }

    public function updateMetadata(int $idMetadata, array $data) {
        return $this->updateExisting('metadata', $idMetadata, $data);
    }

    public function setDefaultMetadataValue(int $idMetadata, int $idMetadataValue) {
        return $this->updateDefaultMetadataValue($idMetadata, $idMetadataValue, true);
    }

    public function unsetDefaultMetadataValue(int $idMetadata, int $idMetadataValue) {
        return $this->updateDefaultMetadataValue($idMetadata, $idMetadataValue, false);
    }

    public function updateDefaultMetadataValue(int $idMetadata, int $idMetadataValue, bool $isDefault) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('metadata_values')
            ->set([MetadataValueMetadata::IS_DEFAULT => ($isDefault ? '1' : '0')])
            ->where(MetadataValueMetadata::ID_METADATA . ' = ?', [$idMetadata])
            ->andWhere(MetadataValueMetadata::ID . ' = ?', [$idMetadataValue])
            ->execute();

        return $qb->fetchAll();
    }

    public function hasMetadataDefaultValue(int $idMetadata) {
        $qb = $this->qb(__METHOD__);

        $qb ->select([MetadataValueMetadata::ID])
            ->from('metadata_values')
            ->where(MetadataValueMetadata::ID_METADATA . ' = ?', [$idMetadata])
            ->andWhere(MetadataValueMetadata::IS_DEFAULT . ' = ?', ['1'])
            ->limit(1)
            ->execute();

        return $qb->fetch(MetadataValueMetadata::ID);
    }

    public function deleteMetadataValueByIdMetadataValue(int $idMetadataValue) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('metadata_values')
            ->where(MetadataValueMetadata::ID . ' = ?', [$idMetadataValue])
            ->execute();

        return $qb->fetchAll();
    }

    public function deleteMetadata(int $idMetadata) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('metadata')
            ->where(MetadataMetadata::ID . ' = ?', [$idMetadata])
            ->execute();

        return $qb->fetchAll();
    }

    public function deleteMetadataValues(int $idMetadata) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('metadata_values')
            ->where(MetadataValueMetadata::ID_METADATA . ' = ?', [$idMetadata])
            ->execute();

        return $qb->fetchAll();
    }

    public function getMetadataByName(string $name, string $tableName) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('metadata')
            ->where(MetadataMetadata::NAME . ' = ?', [$name])
            ->andWhere(MetadataMetadata::TABLE_NAME . ' = ?', [$tableName])
            ->execute();

        return $this->createMetadataObjectFromDbRow($qb->fetch());
    }

    public function getAllMetadataForTableName(string $tableName) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('metadata')
            ->where(MetadataMetadata::TABLE_NAME . ' = ?', [$tableName])
            ->execute();

        $metadata = [];
        foreach($qb->fetchAll() as $row) {
            $metadata[] = $this->createMetadataObjectFromDbRow($row);
        }

        return $metadata;
    }

    public function insertMetadataValueForIdMetadata(int $idMetadata, string $name, string $value) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('metadata_values', [MetadataValueMetadata::ID_METADATA, MetadataValueMetadata::NAME, MetadataValueMetadata::VALUE])
            ->values([$idMetadata, $name, $value])
            ->execute();

        return $qb->fetchAll();
    }

    public function getMetadataById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('metadata')
            ->where(MetadataMetadata::ID . ' = ?', [$id])
            ->execute();

        return $this->createMetadataObjectFromDbRow($qb->fetch());
    }

    public function insertNewMetadata(array $data) {
        return $this->insertNew($data, 'metadata');
    }

    public function getLastInsertedMetadata() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('metadata')
            ->orderBy(MetadataMetadata::ID, 'DESC')
            ->limit(1)
            ->execute();

        return $this->createMetadataObjectFromDbRow($qb->fetch());
    }

    public function getAllMetadata() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('metadata')
            ->execute();

        $metadata = [];
        while($row = $qb->fetchAssoc()) {
            $metadata[] = $this->createMetadataObjectFromDbRow($row);
        }

        return $metadata;
    }

    public function getAllValuesForIdMetadata(int $idMetadata) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('metadata_values')
            ->where(MetadataValueMetadata::ID_METADATA . ' = ?', [$idMetadata])
            ->execute();

        $values = [];
        while($row = $qb->fetchAssoc()) {
            $values[] = $this->createMetadataValueObjectFromDbRow($row);
        }

        return $values;
    }

    private function createMetadataObjectFromDbRow($row) {
        if($row === NULL) {
            return null;
        }
        
        $id = $row[MetadataMetadata::ID];
        $name = $row[MetadataMetadata::NAME];
        $text = $row[MetadataMetadata::TEXT];
        $tableName = $row[MetadataMetadata::TABLE_NAME];
        $isSystem = $row[MetadataMetadata::IS_SYSTEM];
        $inputType = $row[MetadataMetadata::INPUT_TYPE];
        $inputLength = $row[MetadataMetadata::LENGTH];
        $selectExternalEnumName = null;
        $isReadonly = $row[MetadataMetadata::IS_READONLY];

        if(isset($row[MetadataMetadata::SELECT_EXTERNAL_ENUM_NAME]) && $row[MetadataMetadata::SELECT_EXTERNAL_ENUM_NAME] != NULL) {
            $selectExternalEnumName = $row[MetadataMetadata::SELECT_EXTERNAL_ENUM_NAME];
        }

        if($isSystem == '1') {
            $isSystem = true;
        } else {
            $isSystem = false;
        }

        if($isReadonly == '1') {
            $isReadonly = true;
        } else {
            $isReadonly = false;
        }

        return new Metadata($id, $name, $text, $tableName, $isSystem, $inputType, $inputLength, $selectExternalEnumName, $isReadonly);
    }

    private function createMetadataValueObjectFromDbRow($row) {
        if($row === NULL) {
            return null;
        }

        $id = $row[MetadataValueMetadata::ID];
        $idMetadata = $row[MetadataValueMetadata::ID_METADATA];
        $name = $row[MetadataValueMetadata::NAME];
        $value = $row[MetadataValueMetadata::VALUE];
        $isDefault = $row[MetadataValueMetadata::IS_DEFAULT];

        if($isDefault == '1') {
            $isDefault = true;
        } else {
            $isDefault = false;
        }

        return new MetadataValue($id, $idMetadata, $name, $value, $isDefault);
    }
}

?>