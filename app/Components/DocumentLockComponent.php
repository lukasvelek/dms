<?php

namespace DMS\Components;

use DMS\Constants\DocumentLockStatus;
use DMS\Constants\DocumentLockType;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\DocumentLockEntity;
use DMS\Models\DocumentLockModel;
use DMS\UI\LinkBuilder;

class DocumentLockComponent extends AComponent {
    private DocumentLockModel $dlm;

    public function __construct(Database $db, Logger $logger, DocumentLockModel $dlm) {
        parent::__construct($db, $logger);

        $this->dlm = $dlm;
    }

    public function unlockDocument(int $idDocument) {
        $lock = $this->dlm->getActiveLockForIdDocument($idDocument);

        if($lock !== NULL) {
            $data = [
                'status' => DocumentLockStatus::INACTIVE,
                'date_updated' => date('Y-m-d H:i:s')
            ];

            return $this->dlm->updateLock($lock->getId(), $data);
        } else {
            return false;
        }
    }

    public function lockDocumentForUser(int $idDocument, int $idUser) {
        $lock = $this->dlm->getActiveLockForIdDocument($idDocument);

        if($lock !== NULL) {
            return false;
        } else {
            $data = [
                'id_document' => $idDocument,
                'id_user' => $idUser,
                'description' => 'Document locked by user'
            ];
    
            return $this->dlm->insertNewLock($data);
        }
    }

    public function lockDocumentForProcess(int $idDocument, int $idProcess) {
        $data = [
            'id_document' => $idDocument,
            'id_process' => $idProcess,
            'description' => 'Document locked by process'
        ];

        return $this->dlm->insertNewLock($data);
    }

    public function isDocumentLocked(int $idDocument) {
        $lock = $this->dlm->getActiveLockForIdDocument($idDocument);

        if($lock !== NULL) {
            return $lock;
        } else {
            return false;
        }
    }

    public function createLockText(DocumentLockEntity $lock, int $idCallingUser) {
        $html = '<span style="color: $COLOR$">$TEXT$</span>';

        switch($lock->getType()) {
            case DocumentLockType::PROCESS_LOCK:
                $html = str_replace(['$COLOR$', '$TEXT$'], ['orange', DocumentLockType::$texts[$lock->getType()]], $html);
                break;

            case DocumentLockType::USER_LOCK:
                $html = str_replace(['$COLOR$', '$TEXT$'], ['blue', DocumentLockType::$texts[$lock->getType()]], $html);
                
                if($lock->getIdUser() == $idCallingUser) {
                    $html = LinkBuilder::createAdvLink(['page' => 'UserModule:Documents:unlockDocumentForUser', 'id_document' => $lock->getIdDocument(), 'id_user' => $lock->getIdUser()], $html);
                }

                break;
        }

        return $html;
    }
}

?>