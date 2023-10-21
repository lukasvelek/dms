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

    public function insertNewMetadata(string $name, string $text) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->insert('metadata', 'name', 'text')
                     ->values(':name', ':text')
                     ->setParams(array(
                        ':name' => $name,
                        ':text' => $text
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

        return new Metadata($id, $name, $text);
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