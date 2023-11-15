<?php

namespace DMS\Authorizators;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\User;

class DocumentBulkActionAuthorizator extends AAuthorizator {
    private DocumentAuthorizator $documentAuthorizator;
    private BulkActionAuthorizator $bulkActionAuthorizator;

    public function __construct(Database $db, Logger $logger, ?User $user, DocumentAuthorizator $documentAuthorizator, BulkActionAuthorizator $bulkActionAuthorizator) {
        parent::__construct($db, $logger, $user);

        $this->documentAuthorizator = $documentAuthorizator;
        $this->bulkActionAuthorizator = $bulkActionAuthorizator;
    }

    public function canApproveArchivation(int $idDocument, ?int $idUser = null, bool $checkCache = true) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if(!$this->bulkActionAuthorizator->checkBulkActionRight('approve_archivation', $idUser, $checkCache)) {
            return false;
        }

        if(!$this->documentAuthorizator->canApproveArchivation($idDocument)) {
            return false;
        }

        return true;
    }

    public function canDeclineArchivation(int $idDocument, ?int $idUser = null, bool $checkCache = true) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if(!$this->bulkActionAuthorizator->checkBulkActionRight('decline_archivation', $idUser, $checkCache)) {
            return false;
        }

        if(!$this->documentAuthorizator->canDeclineArchivation($idDocument)) {
            return false;
        }

        return true;
    }

    public function canArchive(int $idDocument, ?int $idUser = null, bool $checkCache = true) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if(!$this->bulkActionAuthorizator->checkBulkActionRight('archive', $idUser, $checkCache)) {
            return false;
        }

        if(!$this->documentAuthorizator->canArchive($idDocument)) {
            return false;
        }

        return true;
    }

    public function canDelete(int $idDocument, ?int $idUser = null, bool $checkCache = true) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if(!$this->bulkActionAuthorizator->checkBulkActionRight('delete_documents', $idUser, $checkCache)) {
            return false;
        }

        if(!$this->documentAuthorizator->canDeleteDocument($idDocument)) {
            return false;
        }

        return true;
    }

    private function assignUser(?int &$idUser) {
        if($this->idUser == null) {
            if($idUser == null) {
                return false;
            }
        } else {
            if($idUser == null) {
                $idUser = $this->idUser;
            }
        }

        return true;
    }
}

?>