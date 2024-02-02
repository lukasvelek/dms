<?php

namespace DMS\Authorizators;

use DMS\Components\ProcessComponent;
use DMS\Constants\DocumentShreddingStatus;
use DMS\Constants\DocumentStatus;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Document;
use DMS\Entities\User;
use DMS\Models\DocumentModel;
use DMS\Models\ProcessModel;
use DMS\Models\UserModel;

/**
 * DocumentAuthorizator checks if a action is allowed to be performed on a document.
 * 
 * @author Lukas Velek
 */
class DocumentAuthorizator extends AAuthorizator {
    private DocumentModel $documentModel;
    private UserModel $userModel;
    private ProcessModel $processModel;
    private ProcessComponent $processComponent;

    /**
     * The DocumentAuthorizator constructor creates an object
     * 
     * @param Database $db Database instance
     * @param Logger $logger Logger instance
     * @param DocumentModel $documentModel DocumentModel instance
     * @param UserModel $userModel UserModel instance
     * @param ProcessModel $processModel ProcessModel instance
     * @param null|User $user User instance
     * @param ProcessComponent $processComponent ProcessComponent instance
     */
    public function __construct(Database $db, Logger $logger, DocumentModel $documentModel, UserModel $userModel, ProcessModel $processModel, ?User $user, ProcessComponent $processComponent) {
        parent::__construct($db, $logger, $user);
        $this->documentModel = $documentModel;
        $this->userModel = $userModel;
        $this->processModel = $processModel;
        $this->processComponent = $processComponent;
    }

    public function canMoveToArchiveDocument(Document $document, bool $checkForExistingProcess = false) {
        if($checkForExistingProcess) {
            if($this->processComponent->checkIfDocumentIsInProcess($document->getId())) {
                return false;
            }
        }

        if($document->getStatus() != DocumentStatus::ARCHIVED) {
            return false;
        }

        if($document->getIdArchiveDocument() != NULL) {
            return false;
        }

        return true;
    }

    public function canMoveFromArchiveDocument(Document $document, bool $checkForExistingProcess = false) {
        if($checkForExistingProcess) {
            if($this->processComponent->checkIfDocumentIsInProcess($document->getId())) {
                return false;
            }
        }

        if($document->getStatus() != DocumentStatus::ARCHIVED) {
            return false;
        }

        if($document->getIdArchiveDocument() == NULL) {
            return false;
        }

        return true;
    }

    /**
     * Checks if a document can be archived
     * 
     * @param Document Document object
     * @return bool True if the document can be archived and false if not
     */
    public function canArchive(Document $document, bool $checkForExistingProcess = false) {
        if($checkForExistingProcess) {
            if($this->processComponent->checkIfDocumentIsInProcess($document->getId())) {
                return false;
            }
        }

        if($document->getStatus() != DocumentStatus::ARCHIVATION_APPROVED) {
            return false;
        }

        return true;
    }

    /**
     * Checks if a document can be approved for archivation
     * 
     * @param Document Document object
     * @return bool True if the document can be approved for archivation and false if not
     */
    public function canApproveArchivation(Document $document, bool $checkForExistingProcess = false) {
        if($checkForExistingProcess) {
            if($this->processComponent->checkIfDocumentIsInProcess($document->getId())) {
                return false;
            }
        }

        // STATUS
        if($document->getStatus() != DocumentStatus::NEW) {
            return false;
        }

        return true;
    }

    /**
     * Checks if a document can be declined for archivation
     * 
     * @param Document Document object
     * @return bool True if the document can be declined for archivation and false if not
     */
    public function canDeclineArchivation(Document $document, bool $checkForExistingProcess = false) {
        if($checkForExistingProcess) {
            if($this->processComponent->checkIfDocumentIsInProcess($document->getId())) {
                return false;
            }
        }

        //STATUS
        if($document->getStatus() != DocumentStatus::NEW) {
            return false;
        }

        return true;
    }

    /**
     * Checks if a document can be deleted
     * 
     * @param Document Document object
     * @return bool True if the document can be deleted and false if not
     */
    public function canDeleteDocument(Document $document, bool $checkStatus = true, bool $checkForExistingProcess = false) {
        if($checkForExistingProcess) {
            if($this->processComponent->checkIfDocumentIsInProcess($document->getId())) {
                return false;
            }
        }

        if($checkStatus) {
            if(!in_array($document->getStatus(), array(
                DocumentStatus::ARCHIVED,
                DocumentStatus::SHREDDED
            ))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if a document can be shredded
     * 
     * @param Document Document object
     * @return bool True if the document can be shredded and false if not
     */
    public function canShred(Document $document, bool $checkForExistingProcess = false) {
        if($checkForExistingProcess) {
            if($this->processComponent->checkIfDocumentIsInProcess($document->getId())) {
                return false;
            }
        }

        if($document->getStatus() != DocumentStatus::ARCHIVED) {
            return false;
        }

        if($document->getShreddingStatus() != DocumentShreddingStatus::APPROVED) {
            return false;
        }

        if($document->getShredYear() >= date('Y')) {
            return false;
        }

        return true;
    }

    public function canSuggestForShredding(Document $document, bool $checkForExistingProcess = false) {
        if($checkForExistingProcess) {
            if($this->processComponent->checkIfDocumentIsInProcess($document->getId())) {
                return false;
            }
        }

        if($document->getStatus() != DocumentStatus::ARCHIVED) {
            return false;
        }

        if($document->getShreddingStatus() != DocumentShreddingStatus::NO_STATUS) {
            return false;
        }

        if($document->getShredYear() >= date('Y')) {
            return false;
        }

        return true;
    }
    
    public function canApproveShredding(Document $document, bool $checkForExistingProcess = false) {
        if($checkForExistingProcess) {
            if($this->processComponent->checkIfDocumentIsInProcess($document->getId())) {
                return false;
            }
        }

        if($document->getStatus() != DocumentStatus::ARCHIVED) {
            return false;
        }

        if($document->getShreddingStatus() != DocumentShreddingStatus::IN_APPROVAL) {
            return false;
        }

        if($document->getShredYear() >= date('Y')) {
            return false;
        }

        return true;
    }

    public function canDeclineShredding(Document $document, bool $checkForExistingProcess = false) {
        if($checkForExistingProcess) {
            if($this->processComponent->checkIfDocumentIsInProcess($document->getId())) {
                return false;
            }
        }

        if($document->getStatus() != DocumentStatus::ARCHIVED) {
            return false;
        }

        if($document->getShreddingStatus() != DocumentShreddingStatus::IN_APPROVAL) {
            return false;
        }

        return true;
    }
}

?>