<?php

namespace DMS\Authorizators;

use DMS\Components\ProcessComponent;
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
    private ProcessComponent $processComponent;

    public function __construct(Database $db, Logger $logger, DocumentModel $documentModel, UserModel $userModel, ProcessModel $processModel, ?User $user, ProcessComponent $processComponent) {
        parent::__construct($db, $logger, $user);
        $this->documentModel = $documentModel;
        $this->userModel = $userModel;
        $this->processModel = $processModel;
        $this->processComponent = $processComponent;
    }

    public function canArchive(int $id) {
        $document = $this->documentModel->getDocumentById($id);

        if($this->processComponent->checkIfDocumentIsInProcess($id)) {
            return false;
        }

        if($document->getStatus() != DocumentStatus::ARCHIVATION_APPROVED) {
            return false;
        }

        return true;
    }

    public function canApproveArchivation(int $id) {
        $document = $this->documentModel->getDocumentById($id);

        if($this->processComponent->checkIfDocumentIsInProcess($id)) {
            return false;
        }

        // STATUS
        if($document->getStatus() != DocumentStatus::NEW) {
            return false;
        }

        return true;
    }

    public function canDeclineArchivation(int $id) {
        $document = $this->documentModel->getDocumentById($id);

        if($this->processComponent->checkIfDocumentIsInProcess($id)) {
            return false;
        }

        //STATUS
        if($document->getStatus() != DocumentStatus::NEW) {
            return false;
        }

        return true;
    }

    public function canDeleteDocument(int $id) {
        $document = $this->documentModel->getDocumentById($id);

        if($this->processComponent->checkIfDocumentIsInProcess($id)) {
            return false;
        }

        if(!in_array($document->getStatus(), array(
            DocumentStatus::ARCHIVED,
            DocumentStatus::SHREDDED
        ))) {
            return false;
        }

        return true;
    }
}

?>