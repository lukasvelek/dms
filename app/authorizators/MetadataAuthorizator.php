<?php

namespace DMS\Authorizators;

use DMS\Core\CacheManager;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class MetadataAuthorizator extends AAuthorizator {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function canUserViewMetadata(int $idUser, int $idMetadata) {
        $row = $this->getRightRow($idUser, $idMetadata);

        return $row['view'] ? true : false;
    }

    public function canUserEditMetadata(int $idUser, int $idMetadata) {
        $row = $this->getRightRow($idUser, $idMetadata);

        return $row['edit'] ? true : false;
    }

    public function canUserViewMetadataValues(int $idUser, int $idMetadata) {
        $row = $this->getRightRow($idUser, $idMetadata);

        return $row['view_values'] ? true : false;
    }

    public function canUserEditMetadataValues(int $idUser, int $idMetadata) {
        $row = $this->getRightRow($idUser, $idMetadata);

        return $row['edit_values'] ? true : false;
    }

    private function getRightRow(int $idUser, int $idMetadata) {
        $qb = $this->qb(__METHOD__);

        //$cm = CacheManager::getTemporaryObject('metadata_authorizator');

        

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

        return $row;
    }
}

?>