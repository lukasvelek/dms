<?php

namespace DMS\Authorizators;

use DMS\Components\DocumentLockComponent;
use DMS\Components\ProcessComponent;
use DMS\Constants\DocumentLockType;
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
    private DocumentLockComponent $documentLockComponent;

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
     * @param DocumentLockComponent $documentLockComponent DocumentLockComponent instance
     */
    public function __construct(Database $db, Logger $logger, DocumentModel $documentModel, UserModel $userModel, ProcessModel $processModel, ?User $user, ProcessComponent $processComponent, DocumentLockComponent $documentLockComponent) {
        parent::__construct($db, $logger, $user);
        $this->documentModel = $documentModel;
        $this->userModel = $userModel;
        $this->processModel = $processModel;
        $this->processComponent = $processComponent;
        $this->documentLockComponent = $documentLockComponent;
    }

    /**
     * Checks if given user can override document lock for given document
     * 
     * @param int $idDocument Document ID
     * @param int $idUser User ID
     * @return bool True if the lock can be overriden or false if not
     */
    public function canUserOverrideDocumentLock(int $idDocument, int $idUser) {
        $lock = $this->documentLockComponent->isDocumentLocked($idDocument);

        if($lock !== FALSE) {
            switch($lock->getType()) {
                case DocumentLockType::PROCESS_LOCK:
                    return false;

                case DocumentLockType::USER_LOCK:
                    if($lock->getIdUser() != $idUser) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * This method checks if a document can be moved to an archive document
     * 
     * @param Document $document Document instance
     * @param int $idUser User ID or null
     * @param bool $checkForExistingProcess True if the method should check for an existing process
     * @return bool True if the action is allowed or false if not
     */
    public function canMoveToArchiveDocument(Document $document, int $idUser = null, bool $checkForExistingProcess = false) {
        if($checkForExistingProcess === TRUE) {
            if($this->processComponent->checkIfDocumentIsInProcess($document->getId())) {
                return false;
            }
        }
        
        $lock = $this->documentLockComponent->isDocumentLocked($document->getId());

        if($lock !== FALSE) {
            if($lock->getType() != DocumentLockType::USER_LOCK) {
                if($idUser !== NULL) {
                    if($lock->getIdUser() !== $idUser) {
                        return false;
                    }
                }
            }

            if($lock->getType() == DocumentLockType::PROCESS_LOCK) {
                return false;
            } else if($lock->getType() == DocumentLockType::USER_LOCK) {
                if($idUser === NULL) {
                    return false;
                } else {
                    if($lock->getIdUser() != $idUser) {
                        return false;
                    }
                }
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

    /**
     * This method checks if a document can be moved from an archive document
     * 
     * @param Document $document Document instance
     * @param int $idUser User ID or null
     * @param bool $checkForExistingProcess True if the method should check for an existing process
     * @return bool True if the action is allowed or false if not
     */
    public function canMoveFromArchiveDocument(Document $document, int $idUser = null, bool $checkForExistingProcess = false) {
        if($checkForExistingProcess === TRUE) {
            if($this->processComponent->checkIfDocumentIsInProcess($document->getId())) {
                return false;
            }
        }

        $lock = $this->documentLockComponent->isDocumentLocked($document->getId());

        if($lock !== FALSE) {
            if($lock->getType() != DocumentLockType::USER_LOCK) {
                if($idUser !== NULL) {
                    if($lock->getIdUser() !== $idUser) {
                        return false;
                    }
                }
            }

            if($lock->getType() == DocumentLockType::PROCESS_LOCK) {
                return false;
            } else if($lock->getType() == DocumentLockType::USER_LOCK) {
                if($idUser === NULL) {
                    return false;
                } else {
                    if($lock->getIdUser() != $idUser) {
                        return false;
                    }
                }
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
     * @param Document $document Document object
     * @param int $idUser User ID or null
     * @param bool $checkForExistingProcess True if the method should check for an existing process
     * @return bool True if the document can be archived and false if not
     */
    public function canArchive(Document $document, int $idUser = null, bool $checkForExistingProcess = false) {
        if($checkForExistingProcess === TRUE) {
            if($this->processComponent->checkIfDocumentIsInProcess($document->getId())) {
                return false;
            }
        }

        $lock = $this->documentLockComponent->isDocumentLocked($document->getId());

        if($lock !== FALSE) {
            if($lock->getType() != DocumentLockType::USER_LOCK) {
                if($idUser !== NULL) {
                    if($lock->getIdUser() !== $idUser) {
                        return false;
                    }
                }
            }

            if($lock->getType() == DocumentLockType::PROCESS_LOCK) {
                return false;
            } else if($lock->getType() == DocumentLockType::USER_LOCK) {
                if($idUser === NULL) {
                    return false;
                } else {
                    if($lock->getIdUser() != $idUser) {
                        return false;
                    }
                }
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
     * @param Document $document Document object
     * @param int $idUser User ID or null
     * @param bool $checkForExistingProcess True if the method should check for an existing process
     * @return bool True if the document can be approved for archivation and false if not
     */
    public function canApproveArchivation(Document $document, int $idUser = null, bool $checkForExistingProcess = false) {
        if($checkForExistingProcess === TRUE) {
            if($this->processComponent->checkIfDocumentIsInProcess($document->getId())) {
                return false;
            }
        }

        $lock = $this->documentLockComponent->isDocumentLocked($document->getId());

        if($lock !== FALSE) {
            if($lock->getType() != DocumentLockType::USER_LOCK) {
                if($idUser !== NULL) {
                    if($lock->getIdUser() !== $idUser) {
                        return false;
                    }
                }
            }

            if($lock->getType() == DocumentLockType::PROCESS_LOCK) {
                return false;
            } else if($lock->getType() == DocumentLockType::USER_LOCK) {
                if($idUser === NULL) {
                    return false;
                } else {
                    if($lock->getIdUser() != $idUser) {
                        return false;
                    }
                }
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
     * @param Document $document Document object
     * @param int $idUser User ID or null
     * @param bool $checkForExistingProcess True if the method should check for an existing process
     * @return bool True if the document can be declined for archivation and false if not
     */
    public function canDeclineArchivation(Document $document, int $idUser = null, bool $checkForExistingProcess = false) {
        if($checkForExistingProcess === TRUE) {
            if($this->processComponent->checkIfDocumentIsInProcess($document->getId())) {
                return false;
            }
        }

        $lock = $this->documentLockComponent->isDocumentLocked($document->getId());

        if($lock !== FALSE) {
            if($lock->getType() != DocumentLockType::USER_LOCK) {
                if($idUser !== NULL) {
                    if($lock->getIdUser() !== $idUser) {
                        return false;
                    }
                }
            }

            if($lock->getType() == DocumentLockType::PROCESS_LOCK) {
                return false;
            } else if($lock->getType() == DocumentLockType::USER_LOCK) {
                if($idUser === NULL) {
                    return false;
                } else {
                    if($lock->getIdUser() != $idUser) {
                        return false;
                    }
                }
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
     * @param Document $document Document object
     * @param int $idUser User ID or null
     * @param bool $checkForExistingProcess True if the method should check for an existing process
     * @return bool True if the document can be deleted and false if not
     */
    public function canDeleteDocument(Document $document, int $idUser = null, bool $checkStatus = true, bool $checkForExistingProcess = false) {
        if($checkForExistingProcess === TRUE) {
            if($this->processComponent->checkIfDocumentIsInProcess($document->getId())) {
                return false;
            }
        }

        $lock = $this->documentLockComponent->isDocumentLocked($document->getId());

        if($lock !== FALSE) {
            if($lock->getType() != DocumentLockType::USER_LOCK) {
                if($idUser !== NULL) {
                    if($lock->getIdUser() !== $idUser) {
                        return false;
                    }
                }
            }

            if($lock->getType() == DocumentLockType::PROCESS_LOCK) {
                return false;
            } else if($lock->getType() == DocumentLockType::USER_LOCK) {
                if($idUser === NULL) {
                    return false;
                } else {
                    if($lock->getIdUser() != $idUser) {
                        return false;
                    }
                }
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
     * @param Document $document Document object
     * @param int $idUser User ID or null
     * @param bool $checkForExistingProcess True if the method should check for an existing process
     * @return bool True if the document can be shredded and false if not
     */
    public function canShred(Document $document, int $idUser = null, bool $checkForExistingProcess = false) {
        if($checkForExistingProcess === TRUE) {
            if($this->processComponent->checkIfDocumentIsInProcess($document->getId())) {
                return false;
            }
        }

        $lock = $this->documentLockComponent->isDocumentLocked($document->getId());

        if($lock !== FALSE) {
            if($lock->getType() != DocumentLockType::USER_LOCK) {
                if($idUser !== NULL) {
                    if($lock->getIdUser() !== $idUser) {
                        return false;
                    }
                }
            }

            if($lock->getType() == DocumentLockType::PROCESS_LOCK) {
                return false;
            } else if($lock->getType() == DocumentLockType::USER_LOCK) {
                if($idUser === NULL) {
                    return false;
                } else {
                    if($lock->getIdUser() != $idUser) {
                        return false;
                    }
                }
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

    /**
     * Checks if the document can be suggested for shredding
     * 
     * @param Document $document Document instance
     * @param int $idUser User ID or null
     * @param bool $checkForExistingProcess True if the method should check for an existing process
     * @return bool True if the action is allowed
     */
    public function canSuggestForShredding(Document $document, int $idUser = null, bool $checkForExistingProcess = false) {
        if($checkForExistingProcess === TRUE) {
            if($this->processComponent->checkIfDocumentIsInProcess($document->getId())) {
                return false;
            }
        }

        $lock = $this->documentLockComponent->isDocumentLocked($document->getId());

        if($lock !== FALSE) {
            if($lock->getType() != DocumentLockType::USER_LOCK) {
                if($idUser !== NULL) {
                    if($lock->getIdUser() !== $idUser) {
                        return false;
                    }
                }
            }

            if($lock->getType() == DocumentLockType::PROCESS_LOCK) {
                return false;
            } else if($lock->getType() == DocumentLockType::USER_LOCK) {
                if($idUser === NULL) {
                    return false;
                } else {
                    if($lock->getIdUser() != $idUser) {
                        return false;
                    }
                }
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
    
    /**
     * Checks if the document shredding can be approved
     * 
     * @param Document $document Document instance
     * @param int $idUser User ID or null
     * @param bool $checkForExistingProcess True if the method should check for an existing process
     * @return bool True if the action is allowed
     */
    public function canApproveShredding(Document $document, int $idUser = null, bool $checkForExistingProcess = false) {
        if($checkForExistingProcess === TRUE) {
            if($this->processComponent->checkIfDocumentIsInProcess($document->getId())) {
                return false;
            }
        }

        $lock = $this->documentLockComponent->isDocumentLocked($document->getId());

        if($lock !== FALSE) {
            if($lock->getType() != DocumentLockType::USER_LOCK) {
                if($idUser !== NULL) {
                    if($lock->getIdUser() !== $idUser) {
                        return false;
                    }
                }
            }

            if($lock->getType() == DocumentLockType::PROCESS_LOCK) {
                return false;
            } else if($lock->getType() == DocumentLockType::USER_LOCK) {
                if($idUser === NULL) {
                    return false;
                } else {
                    if($lock->getIdUser() != $idUser) {
                        return false;
                    }
                }
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

    /**
     * Checks if the document shredding can be declined
     * 
     * @param Document $document Document instance
     * @param int $idUser User ID or null
     * @param bool $checkForExistingProcess True if the method should check for an existing process
     * @return bool True if the action is allowed
     */
    public function canDeclineShredding(Document $document, int $idUser = null, bool $checkForExistingProcess = false) {
        if($checkForExistingProcess === TRUE) {
            if($this->processComponent->checkIfDocumentIsInProcess($document->getId())) {
                return false;
            }
        }

        $lock = $this->documentLockComponent->isDocumentLocked($document->getId());

        if($lock !== FALSE) {
            if($lock->getType() != DocumentLockType::USER_LOCK) {
                if($idUser !== NULL) {
                    if($lock->getIdUser() !== $idUser) {
                        return false;
                    }
                }
            }

            if($lock->getType() == DocumentLockType::PROCESS_LOCK) {
                return false;
            } else if($lock->getType() == DocumentLockType::USER_LOCK) {
                if($idUser === NULL) {
                    return false;
                } else {
                    if($lock->getIdUser() != $idUser) {
                        return false;
                    }
                }
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