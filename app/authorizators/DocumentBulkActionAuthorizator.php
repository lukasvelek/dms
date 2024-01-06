<?php

namespace DMS\Authorizators;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Document;
use DMS\Entities\User;

/**
 * DocumentBulkActionAuthorizator checks if a bulk action can be displayed.
 * 
 * @author Lukas Velek
 */
class DocumentBulkActionAuthorizator extends AAuthorizator {
    private DocumentAuthorizator $documentAuthorizator;
    private BulkActionAuthorizator $bulkActionAuthorizator;

    /**
     * The DocumentBulkActionAuthorizator constructor creates an object
     * 
     * @param DocumentAuthorizator $documentAuthorizator DocumentAuthorizator instance
     * @param BulkActionAuthorizator $bulkActionAuthorizator BulkActionAuthorizator instance
     */
    public function __construct(Database $db, Logger $logger, ?User $user, DocumentAuthorizator $documentAuthorizator, BulkActionAuthorizator $bulkActionAuthorizator) {
        parent::__construct($db, $logger, $user);

        $this->documentAuthorizator = $documentAuthorizator;
        $this->bulkActionAuthorizator = $bulkActionAuthorizator;
    }

    /**
     * Checks if bulk action "Approve archivation" can be displayed.
     * 
     * @param Document Document object
     * @param null|int $idUser User ID
     * @param bool $checkCache True if cache should be checked and false if not
     */
    public function canApproveArchivation(Document $document, ?int $idUser = null, bool $checkCache = true, bool $checkForExistingProcess = false, array $cfg = []) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if(!$this->bulkActionAuthorizator->checkBulkActionRight('approve_archivation', $idUser, $checkCache, $cfg)) {
            return false;
        }

        if(!$this->documentAuthorizator->canApproveArchivation($document, $checkForExistingProcess)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if bulk action "Decline archivation" can be displayed.
     * 
     * @param Document Document object
     * @param null|int $idUser User ID
     * @param bool $checkCache True if cache should be checked and false if not
     */
    public function canDeclineArchivation(Document $document, ?int $idUser = null, bool $checkCache = true, bool $checkForExistingProcess = false, array $cfg = []) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if(!$this->bulkActionAuthorizator->checkBulkActionRight('decline_archivation', $idUser, $checkCache, $cfg)) {
            return false;
        }

        if(!$this->documentAuthorizator->canDeclineArchivation($document, $checkForExistingProcess)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if bulk action "Archive" can be displayed.
     * 
     * @param Document Document object
     * @param null|int $idUser User ID
     * @param bool $checkCache True if cache should be checked and false if not
     */
    public function canArchive(Document $document, ?int $idUser = null, bool $checkCache = true, bool $checkForExistingProcess = false, array $cfg = []) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if(!$this->bulkActionAuthorizator->checkBulkActionRight('archive', $idUser, $checkCache, $cfg)) {
            return false;
        }

        if(!$this->documentAuthorizator->canArchive($document, $checkForExistingProcess)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if bulk action "Delete document" can be displayed.
     * 
     * @param Document Document object
     * @param null|int $idUser User ID
     * @param bool $checkCache True if cache should be checked and false if not
     */
    public function canDelete(Document $document, ?int $idUser = null, bool $checkCache = true, bool $checkForExistingProcess = false, array $cfg = []) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if(!$this->bulkActionAuthorizator->checkBulkActionRight('delete_documents', $idUser, $checkCache, $cfg)) {
            return false;
        }

        if(!$this->documentAuthorizator->canDeleteDocument($document, true, $checkForExistingProcess)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if bulk action "Approve shredding" can be displayed.
     * 
     * @param Document Document object
     * @param null|int $idUser User ID
     * @param bool $checkCache True if cache should be checked and false if not
     */
    public function canApproveShredding(Document $document, ?int $idUser = null, bool $checkCache = true, bool $checkForExistingProcess = false, array $cfg = []) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if(!$this->bulkActionAuthorizator->checkBulkActionRight('approve_shredding', $idUser, $checkCache, $cfg)) {
            return false;
        }

        if(!$this->documentAuthorizator->canApproveShredding($document, true, $checkForExistingProcess)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if bulk action "Decline shredding" can be displayed.
     * 
     * @param Document Document object
     * @param null|int $idUser User ID
     * @param bool $checkCache True if cache should be checked and false if not
     */
    public function canDeclineShredding(Document $document, ?int $idUser = null, bool $checkCache = true, bool $checkForExistingProcess = false, array $cfg = []) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if(!$this->bulkActionAuthorizator->checkBulkActionRight('decline_shredding', $idUser, $checkCache, $cfg)) {
            return false;
        }

        if(!$this->documentAuthorizator->canDeclineShredding($document, true, $checkForExistingProcess)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if bulk action "Suggest for shredding" can be displayed.
     * 
     * @param Document Document object
     * @param null|int $idUser User ID
     * @param bool $checkCache True if cache should be checked and false if not
     */
    public function canSuggestForShredding(Document $document, ?int $idUser = null, bool $checkCache = true, bool $checkForExistingProcess = false, array $cfg = []) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if(!$this->bulkActionAuthorizator->checkBulkActionRight('suggest_shredding', $idUser, $checkCache, $cfg)) {
            return false;
        }

        if(!$this->documentAuthorizator->canSuggestForShredding($document, true, $checkForExistingProcess)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if bulk action "Shred" can be displayed.
     * 
     * @param Document Document object
     * @param null|int $idUser User ID
     * @param bool $checkCache True if cache should be checked and false if not
     */
    public function canShred(Document $document, ?int $idUser = null, bool $checkCache = true, bool $checkForExistingProcess = false, array $cfg = []) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if(!$this->bulkActionAuthorizator->checkBulkActionRight('shred', $idUser, $checkCache, $cfg)) {
            return false;
        }

        if(!$this->documentAuthorizator->canShred($document, true, $checkForExistingProcess)) {
            return false;
        }

        return true;
    }

    /**
     * Tries to assign user either from AAuthorizator or from passed variable
     * 
     * @param int $idUser User ID
     * @return bool True if user has been assigned and false if not
     */
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