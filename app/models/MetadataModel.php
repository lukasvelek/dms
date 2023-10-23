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

    public function getMetadataByName(string $name) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('metadata')
                  ->where('name=:name')
                  ->setParam(':name', $name)
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

    public function insertNewMetadata(string $name, string $text, string $tableName) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->insert('metadata', 'name', 'text', 'table_name')
                     ->values(':name', ':text', ':table_name')
                     ->setParams(array(
                        ':name' => $name,
                        ':text' => $text,
                        ':table_name' => $tableName
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

        return new Metadata($id, $name, $text, $tableName);
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