<?php

namespace DMS\Authorizators;

use DMS\Constants\DocumentStatus;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class DocumentAuthorizator extends AAuthorizator {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function canArchive(int $id) {
        global $app;

        $document = $app->documentModel->getDocumentById($id);

        if($document->getStatus() != DocumentStatus::ARCHIVATION_APPROVED) {
            return false;
        }

        return true;
    }

    public function canApproveArchivation(int $id) {
        global $app;

        $document = $app->documentModel->getDocumentById($id);

        // STATUS
        if($document->getStatus() != DocumentStatus::NEW) {
            return false;
        }

        return true;
    }

    public function canDeclineArchivation(int $id) {
        global $app;

        $document = $app->documentModel->getDocumentById($id);

        //STATUS
        if($document->getStatus() != DocumentStatus::NEW) {
            return false;
        }

        return true;
    }

    public function canDeleteDocument(int $id, int $idCallingUser) {
        global $app;

        $document = $app->documentModel->getDocumentById($id);
        $callingUser = $app->userModel->getUserById($idCallingUser);
        $process = $app->processModel->getProcessForIdDocument($id);

        // PROCESS
        if($process !== NULL) {
            return false;
        }

        

        return true;
    }
}

?>