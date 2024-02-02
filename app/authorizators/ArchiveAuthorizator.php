<?php

namespace DMS\Authorizators;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Archive;
use DMS\Entities\User;
use DMS\Models\ArchiveModel;

class ArchiveAuthorizator extends AAuthorizator {
    private ArchiveModel $archiveModel;

    public function __construct(Database $db, Logger $logger, ArchiveModel $archiveModel, ?User $user) {
        parent::__construct($db, $logger, $user);

        $this->archiveModel = $archiveModel;
    }

    public function canDeleteDocument(Archive $archive) {
        if($this->archiveModel->getChildrenCount($archive->getId(), $archive->getType()) > 0) {
            return false;
        }

        return true;
    }

    public function bulkActionMoveDocumentToBox(Archive $archive, ?int $idUser = null, bool $checkCache = true, bool $checkForExistingProcess = false) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        return true;
    }

    public function bulkActionMoveDocumentFromBox(Archive $archive, ?int $idUser = null, bool $checkCache = true, bool $checkForExistingProcess = false) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if($archive->getIdParentArchiveEntity() === NULL) {
            return false;
        }

        return true;
    }

    public function bulkActionMoveBoxToArchive(Archive $archive, ?int $idUser = null, bool $checkCache = true, bool $checkForExistingProcess = false) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        return true;
    }

    public function bulkActionMoveBoxFromArchive(Archive $archive, ?int $idUser = null, bool $checkCache = true, bool $checkForExistingProcess = false) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if($archive->getIdParentArchiveEntity() === NULL) {
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