<?php

namespace DMS\Components;

use DMS\Constants\DocumentLockStatus;
use DMS\Constants\DocumentLockType;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\DocumentLockEntity;
use DMS\Helpers\TextHelper;
use DMS\Models\DocumentLockModel;
use DMS\Models\UserModel;
use DMS\UI\LinkBuilder;

/**
 * Component that handles working with document locks
 * 
 * @author Lukas Velek
 */
class DocumentLockComponent extends AComponent {
    private DocumentLockModel $dlm;
    private UserModel $userModel;

    /**
     * True if logging is enabled
     */
    private const LOG = false;

    /**
     * Class constructor
     * 
     * @param Database $db Database instance
     * @param Logger $logger Logger instance
     * @param DocumentLockModel $dlm DocumentLockModel instance
     * @param UserModel $userModel UserModel instance
     */
    public function __construct(Database $db, Logger $logger, DocumentLockModel $dlm, UserModel $userModel) {
        parent::__construct($db, $logger);

        $this->dlm = $dlm;
        $this->userModel = $userModel;
    }

    /**
     * Deletes lock entries for given ID document
     * 
     * @param int $idDocument Document ID
     * @return true
     */
    public function deleteLockEntriesForIdDocument(int $idDocument) {
        if(self::LOG) $this->logger->info('Deleting lock entries for document #' . $idDocument, __METHOD__);
        $this->dlm->deleteEntriesForIdDocument($idDocument);
        return true;
    }

    /**
     * Unlocks given document
     * 
     * @param int $idDocument
     * @return mixed|false False if the document could not be unlocked
     */
    public function unlockDocument(int $idDocument) {
        $lock = $this->dlm->getActiveLockForIdDocument($idDocument);

        if($lock !== NULL) {
            $data = [
                'status' => DocumentLockStatus::INACTIVE,
                'date_updated' => date('Y-m-d H:i:s')
            ];

            $this->logger->warn('Unlocking document #' . $idDocument, __METHOD__);

            return $this->dlm->updateLock($lock->getId(), $data);
        } else {
            return false;
        }
    }

    /**
     * Locks given document for given user
     * 
     * @param int $idDocument Document ID
     * @param int $idUser User ID
     * @return mixed|false False if the document could not be locked
     */
    public function lockDocumentForUser(int $idDocument, int $idUser) {
        $lock = $this->isDocumentLocked($idDocument);

        if($lock !== false) {
            return false;
        } else {
            $data = [
                'id_document' => $idDocument,
                'id_user' => $idUser,
                'description' => 'Document locked by user'
            ];

            $this->logger->warn('Locking document #' . $idDocument . ' by user #' . $idUser, __METHOD__);
    
            return $this->dlm->insertNewLock($data);
        }
    }

    /**
     * Locks given document for given process
     * 
     * @param int $idDocument Document ID
     * @param int $idProcess Process ID
     * @return mixed|false False if the document could not be locked
     */
    public function lockDocumentForProcess(int $idDocument, int $idProcess) {
        $lock = $this->isDocumentLocked($idDocument);

        if($lock !== false) {
            return false;
        } else {
            $data = [
                'id_document' => $idDocument,
                'id_process' => $idProcess,
                'description' => 'Document locked by process'
            ];

            $this->logger->warn('Locking document #' . $idDocument . ' by process #' . $idProcess, __METHOD__);
    
            return $this->dlm->insertNewLock($data);
        }
    }

    /**
     * Checks if given document is locked
     * 
     * @param int $idDocument Document ID
     * @return DocumentLockEntity|false DocumentLockEntity instance if the document is locked or false if not
     */
    public function isDocumentLocked(int $idDocument) {
        $lock = $this->dlm->getActiveLockForIdDocument($idDocument);

        if($lock !== NULL) {
            if(self::LOG) $this->logger->info('Found active lock #' . $lock->getId() . ' for document #' . $idDocument, __METHOD__);
            return $lock;
        } else {
            if(self::LOG) $this->logger->info('Found no active lock for document #' . $idDocument, __METHOD__);
            return false;
        }
    }

    /**
     * Creates lock type text
     * 
     * @param DocumentLockEntity $lock DocumentLockEntity instance
     * @param int $idCallingUser Calling user ID
     * @param bool $createLink True if link should be created or false if not
     * @return string HTML text
     */
    public function createLockText(DocumentLockEntity $lock, int $idCallingUser, bool $createLink = true) {
        $html = '';
        switch($lock->getType()) {
            case DocumentLockType::PROCESS_LOCK:
                $html = TextHelper::colorText(DocumentLockType::$texts[$lock->getType()], DocumentLockType::$colors[$lock->getType()]);
                break;

            case DocumentLockType::USER_LOCK:
                $user = $this->userModel->getUserById($lock->getIdUser());

                if($lock->getIdUser() == $idCallingUser) {
                    $html = TextHelper::colorText(DocumentLockType::$texts[$lock->getType()] . ' (Me)', DocumentLockType::$colors[$lock->getType()]);
                    if($createLink == true) {
                        $html = LinkBuilder::createAdvLink(['page' => 'UserModule:Documents:unlockDocumentForUser', 'id_document' => $lock->getIdDocument(), 'id_user' => $lock->getIdUser()], $html);
                    }
                } else {
                    $html = TextHelper::colorText(DocumentLockType::$texts[$lock->getType()] . ' (' . $user->getFullname() . ')', DocumentLockType::$colors[$lock->getType()]);
                }

                break;
        }

        return $html;
    }
}

?>