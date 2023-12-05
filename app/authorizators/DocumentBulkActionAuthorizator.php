<?php

namespace DMS\Authorizators;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
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
     * @param int $idDocument Document ID
     * @param null|int $idUser User ID
     * @param bool $checkCache True if cache shulkd be checked and false if not
     */
    public function canApproveArchivation(int $idDocument, ?int $idUser = null, bool $checkCache = true) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if(!$this->bulkActionAuthorizator->checkBulkActionRight('approve_archivation', $idUser, $checkCache)) {
            return false;
        }

        if(!$this->documentAuthorizator->canApproveArchivation($idDocument)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if bulk action "Decline archivation" can be displayed.
     * 
     * @param int $idDocument Document ID
     * @param null|int $idUser User ID
     * @param bool $checkCache True if cache shulkd be checked and false if not
     */
    public function canDeclineArchivation(int $idDocument, ?int $idUser = null, bool $checkCache = true) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if(!$this->bulkActionAuthorizator->checkBulkActionRight('decline_archivation', $idUser, $checkCache)) {
            return false;
        }

        if(!$this->documentAuthorizator->canDeclineArchivation($idDocument)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if bulk action "Archive" can be displayed.
     * 
     * @param int $idDocument Document ID
     * @param null|int $idUser User ID
     * @param bool $checkCache True if cache shulkd be checked and false if not
     */
    public function canArchive(int $idDocument, ?int $idUser = null, bool $checkCache = true) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if(!$this->bulkActionAuthorizator->checkBulkActionRight('archive', $idUser, $checkCache)) {
            return false;
        }

        if(!$this->documentAuthorizator->canArchive($idDocument)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if bulk action "Delete document" can be displayed.
     * 
     * @param int $idDocument Document ID
     * @param null|int $idUser User ID
     * @param bool $checkCache True if cache shulkd be checked and false if not
     */
    public function canDelete(int $idDocument, ?int $idUser = null, bool $checkCache = true) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if(!$this->bulkActionAuthorizator->checkBulkActionRight('delete_documents', $idUser, $checkCache)) {
            return false;
        }

        if(!$this->documentAuthorizator->canDeleteDocument($idDocument)) {
            return false;
        }

        return true;
    }

    public function canApproveShredding(int $idDocument, ?int $idUser = null, bool $checkCache = true) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if(!$this->bulkActionAuthorizator->checkBulkActionRight('approve_shredding', $idUser, $checkCache)) {
            return false;
        }

        if(!$this->documentAuthorizator->canApproveShredding($idDocument)) {
            return false;
        }

        return true;
    }

    public function canDeclineShredding(int $idDocument, ?int $idUser = null, bool $checkCache = true) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if(!$this->bulkActionAuthorizator->checkBulkActionRight('decline_shredding', $idUser, $checkCache)) {
            return false;
        }

        if(!$this->documentAuthorizator->canDeclineShredding($idDocument)) {
            return false;
        }

        return true;
    }

    public function canSuggestForShredding(int $idDocument, ?int $idUser = null, bool $checkCache = true) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if(!$this->bulkActionAuthorizator->checkBulkActionRight('suggest_shredding', $idUser, $checkCache)) {
            return false;
        }

        if(!$this->documentAuthorizator->canSuggestForShredding($idDocument)) {
            return false;
        }

        return true;
    }

    public function canShred(int $idDocument, ?int $idUser = null, bool $checkCache = true) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if(!$this->bulkActionAuthorizator->checkBulkActionRight('shred', $idUser, $checkCache)) {
            return false;
        }

        if(!$this->documentAuthorizator->canShred($idDocument)) {
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