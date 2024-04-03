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

class DocumentLockComponent extends AComponent {
    private DocumentLockModel $dlm;
    private UserModel $userModel;

    public function __construct(Database $db, Logger $logger, DocumentLockModel $dlm, UserModel $userModel) {
        parent::__construct($db, $logger);

        $this->dlm = $dlm;
        $this->userModel = $userModel;
    }

    public function deleteLockEntriesForIdDocument(int $idDocument) {
        $this->dlm->deleteEntriesForIdDocument($idDocument);
        return true;
    }

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

    public function isDocumentLocked(int $idDocument) {
        $lock = $this->dlm->getActiveLockForIdDocument($idDocument);

        if($lock !== NULL) {
            return $lock;
        } else {
            return false;
        }
    }

    public function createLockText(DocumentLockEntity $lock, int $idCallingUser, bool $createLink = true) {
        switch($lock->getType()) {
            case DocumentLockType::PROCESS_LOCK:
                $html = TextHelper::colorText(DocumentLockType::$texts[$lock->getType()], DocumentLockType::$colors[$lock->getType()]);
                break;

            case DocumentLockType::USER_LOCK:
                $user = $this->userModel->getUserById($idCallingUser);
                
                if($lock->getIdUser() == $idCallingUser) {
                    $html = TextHelper::colorText(DocumentLockType::$texts[$lock->getType()] . ' (Me)', DocumentLockType::$colors[$lock->getType()]);
                    if($createLink === TRUE) {
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