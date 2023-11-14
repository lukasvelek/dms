<?php

namespace DMS\Authorizators;

use DMS\Constants\DocumentStatus;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\User;
use DMS\Models\DocumentModel;
use DMS\Models\ProcessModel;
use DMS\Models\UserModel;

class DocumentAuthorizator extends AAuthorizator {
    private DocumentModel $documentModel;
    private UserModel $userModel;
    private ProcessModel $processModel;

    public function __construct(Database $db, Logger $logger, DocumentModel $documentModel, UserModel $userModel, ProcessModel $processModel, ?User $user) {
        parent::__construct($db, $logger, $user);
        $this->documentModel = $documentModel;
        $this->userModel = $userModel;
        $this->processModel = $processModel;
    }

    public function canArchive(int $id) {
        $document = $this->documentModel->getDocumentById($id);

        if($document->getStatus() != DocumentStatus::ARCHIVATION_APPROVED) {
            return false;
        }

        return true;
    }

    public function canApproveArchivation(int $id) {
        $document = $this->documentModel->getDocumentById($id);

        // STATUS
        if($document->getStatus() != DocumentStatus::NEW) {
            return false;
        }

        return true;
    }

    public function canDeclineArchivation(int $id) {
        $document = $this->documentModel->getDocumentById($id);

        //STATUS
        if($document->getStatus() != DocumentStatus::NEW) {
            return false;
        }

        return true;
    }

    public function canDeleteDocument(int $id, int $idCallingUser) {
        $document = $this->documentModel->getDocumentById($id);
        $callingUser = $this->userModel->getUserById($idCallingUser);
        $process = $this->processModel->getProcessForIdDocument($id);

        // PROCESS
        if($process !== NULL) {
            return false;
        }

        return true;
    }
}

?>