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
        if($this->archiveModel->getChildrenCount($archive->getId()) > 0) {
            return false;
        }

        if($this->archiveModel->getChildrenDocumentsCount($archive->getId()) > 0) {
            return false;
        }

        return true;
    }
}

?>