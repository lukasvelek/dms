<?php

namespace DMS\Authorizators;

use DMS\Components\ProcessComponent;
use DMS\Constants\ArchiveStatus;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Archive;
use DMS\Entities\User;
use DMS\Models\ArchiveModel;

/**
 * ArchiveAuthorizator checks if an entity is allowed to perform an action
 * 
 * @author Lukas Velek
 */
class ArchiveAuthorizator extends AAuthorizator {
    private ArchiveModel $archiveModel;
    private ProcessComponent $processComponent;

    /**
     * ArchiveAuthorizator contstructor creates an object
     * 
     * @param Database $db Database instance
     * @param Logger $logger Logger instance
     * @param ArchiveModel $archiveModel ArchiveModel
     * @param null|User $user User instance or null
     * @param ProcessComponent $processComponent ProcessComponent instance
     */
    public function __construct(Database $db, Logger $logger, ArchiveModel $archiveModel, ?User $user, ProcessComponent $processComponent) {
        parent::__construct($db, $logger, $user);

        $this->archiveModel = $archiveModel;
        $this->processComponent = $processComponent;
    }

    /**
     * This method checks if an archive document can be deleted
     * 
     * @param Archive $archive Archive entity instance
     * @return bool True if the action is allowed or false if not
     */
    public function canDeleteDocument(Archive $archive) {
        if($this->archiveModel->getChildrenCount($archive->getId(), $archive->getType()) > 0) {
            return false;
        }

        return true;
    }

    /**
     * This method checks if an archive document can be moved to an archive box
     * 
     * @param Archive $archive Archive entity instance
     * @param null|int $idUser ID of calling user or null
     * @param bool $checkCache True if cache can be used or false if not
     * @param bool $checkForExistingProcess True if the method should check for an existing process
     * @return bool True if the action is allowed or false if not
     */
    public function bulkActionMoveDocumentToBox(Archive $archive, ?int $idUser = null, bool $checkCache = true, bool $checkForExistingProcess = false) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if($archive->getIdParentArchiveEntity() !== NULL) {
            return false;
        }

        if($archive->getStatus() === ArchiveStatus::IN_BOX) {
            return false;
        }

        return true;
    }

    /**
     * This method checks if an archive document can be moved from an archive box
     * 
     * @param Archive $archive Archive entity instance
     * @param null|int $idUser ID of calling user or null
     * @param bool $checkCache True if cache can be used or false if not
     * @param bool $checkForExistingProcess True if the method should check for an existing process
     * @return bool True if the action is allowed or false if not
     */
    public function bulkActionMoveDocumentFromBox(Archive $archive, ?int $idUser = null, bool $checkCache = true, bool $checkForExistingProcess = false) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if($archive->getIdParentArchiveEntity() === NULL) {
            return false;
        }

        if($archive->getStatus() !== ArchiveStatus::IN_BOX) {
            return false;
        }

        return true;
    }

    /**
     * This method checks if an archive box can be moved to an archive
     * 
     * @param Archive $archive Archive entity instance
     * @param null|int $idUser ID of calling user or null
     * @param bool $checkCache True if cache can be used or false if not
     * @param bool $checkForExistingProcess True if the method should check for an existing process
     * @return bool True if the action is allowed or false if not
     */
    public function bulkActionMoveBoxToArchive(Archive $archive, ?int $idUser = null, bool $checkCache = true, bool $checkForExistingProcess = false) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if($archive->getIdParentArchiveEntity() !== NULL) {
            return false;
        }

        if($archive->getStatus() === ArchiveStatus::IN_ARCHIVE) {
            return false;
        }

        return true;
    }

    /**
     * This method checks if an archive box can be moved from an archive
     * 
     * @param Archive $archive Archive entity instance
     * @param null|int $idUser ID of calling user or null
     * @param bool $checkCache True if cache can be used or false if not
     * @param bool $checkForExistingProcess True if the method should check for an existing process
     * @return bool True if the action is allowed or false if not
     */
    public function bulkActionMoveBoxFromArchive(Archive $archive, ?int $idUser = null, bool $checkCache = true, bool $checkForExistingProcess = false) {
        if(!$this->assignUser($idUser)) {
            return false;
        }

        if($archive->getIdParentArchiveEntity() === NULL) {
            return false;
        }

        if($archive->getStatus() !== ArchiveStatus::IN_ARCHIVE) {
            return false;
        }

        return true;
    }

    /**
     * This method checks if an archive can be closed
     * 
     * @param Archive $archive Archive entity instance
     * @param null|int $idUser ID of calling user or null
     * @param bool $checkCache True if cache can be used or false if not
     * @param bool $checkForExistingProcess True if the method should check for an existing process
     * @return bool True if the action is allowed or false if not
     */
    public function bulkActionCloseArchive(Archive $archive, ?int $idUser = null, bool $checkCache = true, bool $checkForExistingProcess = false) {
        /*if($checkForExistingProcess) {
            if($this->processComponent->checkIfArchiveArchiveIsInProcess($archive->getId())) {
                return false;
            }
        }*/
        
        if($archive->getStatus() === ArchiveStatus::CLOSED) {
            return false;
        }

        return true;
    }

    /**
     * This method checks if an archive can be suggested for shredding
     * 
     * @param Archive $archive Archive entity instance
     * @param null|int $idUser ID of calling user or null
     * @param bool $checkCache True if cache can be used or false if not
     * @param bool $checkForExistingProcess True if the method should check for an existing process
     * @return bool True if the action is allowed or false if not
     */
    public function bulkActionSuggestForShredding(Archive $archive, ?int $idUser = null, bool $checkCache = true, bool $checkForExistingProcess = false) {
        /*if($checkForExistingProcess) {
            if($this->processComponent->checkIfArchiveArchiveIsInProcess($archive->getId())) {
                return false;
            }
        }*/

        if($archive->getStatus() !== ArchiveStatus::CLOSED) {
            return false;
        }

        return true;
    }

    /**
     * This method tries to assign user to the $idUser parameter.
     * The user can either be already provided in the method parameter
     * or it can be passed in the class constructor.
     * 
     * @param null|int $idUser ID user
     * @return bool True if user has been assigned or false if not
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