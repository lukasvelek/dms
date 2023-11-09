<?php

namespace DMS\Authorizators;

use DMS\Constants\CacheCategories;
use DMS\Core\CacheManager;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class MetadataAuthorizator extends AAuthorizator {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function canUserViewMetadata(int $idUser, int $idMetadata) {
        $row = $this->getRightRow($idUser, $idMetadata, 'view');

        if(is_null($row)) {
            return false;
        }

        return $row ? true : false;
    }

    public function canUserEditMetadata(int $idUser, int $idMetadata) {
        $row = $this->getRightRow($idUser, $idMetadata, 'edit');

        if(is_null($row)) {
            return false;
        }

        return $row ? true : false;
    }

    public function canUserViewMetadataValues(int $idUser, int $idMetadata) {
        $row = $this->getRightRow($idUser, $idMetadata, 'view_values');

        if(is_null($row)) {
            return false;
        }

        return $row ? true : false;
    }

    public function canUserEditMetadataValues(int $idUser, int $idMetadata) {
        $row = $this->getRightRow($idUser, $idMetadata, 'edit_values');

        if(is_null($row)) {
            return false;
        }

        return $row ? true : false;
    }

    private function getRightRow(int $idUser, int $idMetadata, string $key) {
        $qb = $this->qb(__METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::METADATA);

        $valFromCache = $cm->loadMetadataRight($idUser, $idMetadata, $key);

        if($valFromCache != null) {
            return $valFromCache;
        }

        $row = $qb->select('*')
                  ->from('user_metadata_rights')
                  ->where('id_user=:id_user')
                  ->andWhere('id_metadata=:id_metadata')
                  ->setParams(array(
                    ':id_user' => $idUser,
                    ':id_metadata' => $idMetadata
                  ))
                  ->execute()
                  ->fetchSingle();

        $result = $row[$key];

        if(!is_null($result)) {
            $cm->saveMetadataRight($idUser, $idMetadata, $key, $result);
        }

        return $result;
    }
}

?>