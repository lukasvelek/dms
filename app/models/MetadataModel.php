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

    public function deleteMetadata(int $idMetadata) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->delete()
                     ->from('metadata')
                     ->where('id=:id')
                     ->setParam(':id', $idMetadata)
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function deleteMetadataValues(int $idMetadata) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->delete()
                     ->from('metadata_values')
                     ->where('id_metadata=:id_metadata')
                     ->setParam(':id_metadata', $idMetadata)
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function getMetadataByName(string $name, string $tableName) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('metadata')
                  ->where('name=:name')
                  ->andWhere('table_name=:table_name')
                  ->setParam(':name', $name)
                  ->setParam(':table_name', $tableName)
                  ->execute()
                  ->fetchSingle();

        return $this->createMetadataObjectFromDbRow($row);
    }

    public function getAllMetadataForTableName(string $tableName) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('metadata')
                   ->where('table_name=:table_name')
                   ->setParam(':table_name', $tableName)
                   ->execute()
                   ->fetch();

        $metadata = [];
        foreach($rows as $row) {
            $metadata[] = $this->createMetadataObjectFromDbRow($row);
        }

        return $metadata;
    }

    public function insertMetadataValueForIdMetadata(int $idMetadata, string $name, string $value) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->insert('metadata_values', 'id_metadata', 'name', 'value')
                     ->values(':id_metadata', ':name', ':value')
                     ->setParams(array(
                        ':id_metadata' => $idMetadata,
                        ':name' => $name,
                        ':value' => $value
                     ))
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function getMetadataById(int $id) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('metadata')
                  ->where('id=:id')
                  ->setParam(':id', $id)
                  ->execute()
                  ->fetchSingle();

        return $this->createMetadataObjectFromDbRow($row);
    }

    public function insertNewMetadata(string $name, string $text, string $tableName, string $inputType, string $inputLength) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->insert('metadata', 'name', 'text', 'table_name', 'input_type', 'length')
                     ->values(':name', ':text', ':table_name', ':input_type', ':input_length')
                     ->setParams(array(
                        ':name' => $name,
                        ':text' => $text,
                        ':table_name' => $tableName,
                        ':input_type' => $inputType,
                        ':input_length' => $inputLength
                     ))
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function getLastInsertedMetadata() {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('metadata')
                  ->orderBy('id', 'DESC')
                  ->limit('1')
                  ->execute()
                  ->fetchSingle();

        return $this->createMetadataObjectFromDbRow($row);
    }

    public function getAllMetadata() {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('metadata')
                   ->execute()
                   ->fetch();

        $metadata = [];
        foreach($rows as $row) {
            $metadata[] = $this->createMetadataObjectFromDbRow($row);
        }

        return $metadata;
    }

    public function getAllValuesForIdMetadata(int $idMetadata) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('metadata_values')
                   ->where('id_metadata=:id_metadata')
                   ->setParam(':id_metadata', $idMetadata)
                   ->execute()
                   ->fetch();

        $values = [];
        foreach($rows as $row) {
            $values[] = $this->createMetadataValueObjectFromDbRow($row);
        }

        return $values;
    }

    private function createMetadataObjectFromDbRow($row) {
        $id = $row['id'];
        $name = $row['name'];
        $text = $row['text'];
        $tableName = $row['table_name'];
        $isSystem = $row['is_system'];
        $inputType = $row['input_type'];
        $inputLength = $row['length'];

        if($isSystem == '1') {
            $isSystem = true;
        } else {
            $isSystem = false;
        }

        return new Metadata($id, $name, $text, $tableName, $isSystem, $inputType, $inputLength);
    }

    private function createMetadataValueObjectFromDbRow($row) {
        $id = $row['id'];
        $idMetadata = $row['id_metadata'];
        $name = $row['name'];
        $value = $row['value'];

        return new MetadataValue($id, $idMetadata, $name, $value);
    }
}

?>