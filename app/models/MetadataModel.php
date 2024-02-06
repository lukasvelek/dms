<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Metadata;
use DMS\Entities\MetadataValue;

class MetadataModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
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
            ->set(['is_default' => ($isDefault ? '1' : '0')])
            ->where('id_metadata = ?', [$idMetadata])
            ->andWhere('id = ?', [$idMetadataValue])
            ->execute();

        return $qb->fetchAll();
    }

    public function hasMetadataDefaultValue(int $idMetadata) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['id'])
            ->from('metadata_values')
            ->where('id_metadata = ?', [$idMetadata])
            ->andWhere('is_default = ?', ['1'])
            ->limit(1)
            ->execute();

        return $qb->fetch('id');
    }

    public function deleteMetadataValueByIdMetadataValue(int $idMetadataValue) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('metadata_values')
            ->where('id = ?', [$idMetadataValue])
            ->execute();

        return $qb->fetchAll();
    }

    public function deleteMetadata(int $idMetadata) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('metadata')
            ->where('id = ?', [$idMetadata])
            ->execute();

        return $qb->fetchAll();
    }

    public function deleteMetadataValues(int $idMetadata) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('metadata_values')
            ->where('id_metadata = ?', [$idMetadata])
            ->execute();

        return $qb->fetchAll();
    }

    public function getMetadataByName(string $name, string $tableName) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('metadata')
            ->where('name = ?', [$name])
            ->andWhere('table_name = ?', [$tableName])
            ->execute();

        return $this->createMetadataObjectFromDbRow($qb->fetch());
    }

    public function getAllMetadataForTableName(string $tableName) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('metadata')
            ->where('table_name = ?', [$tableName])
            ->execute();

        $metadata = [];
        foreach($qb->fetchAll() as $row) {
            $metadata[] = $this->createMetadataObjectFromDbRow($row);
        }

        return $metadata;
    }

    public function insertMetadataValueForIdMetadata(int $idMetadata, string $name, string $value) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('metadata_values', ['id_metadata', 'name', 'value'])
            ->values([$idMetadata, $name, $value])
            ->execute();

        return $qb->fetchAll();
    }

    public function getMetadataById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('metadata')
            ->where('id = ?', [$id])
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
            ->orderBy('id', 'DESC')
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
            ->where('id_metadata = ?', [$idMetadata])
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
        
        $id = $row['id'];
        $name = $row['name'];
        $text = $row['text'];
        $tableName = $row['table_name'];
        $isSystem = $row['is_system'];
        $inputType = $row['input_type'];
        $inputLength = $row['length'];
        $selectExternalEnumName = null;
        $isReadonly = $row['is_readonly'];

        if(isset($row['select_external_enum_name']) && $row['select_external_enum_name'] != NULL) {
            $selectExternalEnumName = $row['select_external_enum_name'];
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

        $id = $row['id'];
        $idMetadata = $row['id_metadata'];
        $name = $row['name'];
        $value = $row['value'];
        $isDefault = $row['is_default'];

        if($isDefault == '1') {
            $isDefault = true;
        } else {
            $isDefault = false;
        }

        return new MetadataValue($id, $idMetadata, $name, $value, $isDefault);
    }
}

?>