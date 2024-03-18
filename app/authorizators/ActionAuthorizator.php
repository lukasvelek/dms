<?php

namespace DMS\Authorizators;

use DMS\Constants\CacheCategories;
use DMS\Constants\UserActionRights;
use DMS\Core\CacheManager;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\User;
use DMS\Models\GroupRightModel;
use DMS\Models\GroupUserModel;
use DMS\Models\UserRightModel;

/**
 * ActionAuthorizator checks if an entity is allowed to perform an action.
 * 
 * @author Lukas Velek
 */
class ActionAuthorizator extends AAuthorizator {
    private UserRightModel $userRightModel;
    private GroupUserModel $groupUserModel;
    private GroupRightModel $groupRightModel;

    /**
     * ActionAuthorizator constructor creates an object
     * 
     * @param Database $db Database instance
     * @param Logger $logger Logger instance
     * @param UserRightModel $userRightModel UserRightModel instance
     * @param GroupUserModel $groupUserModel GroupUserModel instance
     * @param GroupRightModel $groupRightModel GropuRightModel instance
     * @param null|User $user User instance or null
     */
    public function __construct(Database $db, Logger $logger, UserRightModel $userRightModel, GroupUserModel $groupUserModel, GroupRightModel $groupRightModel, ?User $user) {
        parent::__construct($db, $logger, $user);

        $this->userRightModel = $userRightModel;
        $this->groupUserModel = $groupUserModel;
        $this->groupRightModel = $groupRightModel;
    }

    /**
     * This method checks if a user (currently login or other) is allowed to perform an action of a name. It can also check cache for faster performance.
     * 
     * @param string $actionName Action name
     * @param null|int $idUser User ID
     * @param bool $checkCache True if cache should be checked and false if not
     * @return bool True if user is allowed to perform the action and false if not
     */
    public function checkActionRight(string $actionName, ?int $idUser = null, bool $checkCache = true) {
        if(is_null($idUser)) {
            if(empty($this->idUser)) {
                return false;
            }
            
            $idUser = $this->idUser;
        }

        $result = '';

        if($checkCache) {
            $cm = CacheManager::getTemporaryObject(CacheCategories::ACTIONS);

            $valFromCache = $cm->loadActionRight($idUser, $actionName);

            if(!is_null($valFromCache)) {
                $result = $valFromCache;
            } else {
                $rights = $this->userRightModel->getActionRightsForIdUser($idUser);

                $userGroups = $this->groupUserModel->getGroupsForIdUser($idUser);

                $groupRights = [];
                foreach($userGroups as $ug) {
                    $idGroup = $ug->getIdGroup();

                    $dbGroupRights = $this->groupRightModel->getActionRightsForIdGroup($idGroup);
                
                    foreach($dbGroupRights as $k => $v) {
                        if(array_key_exists($k, $groupRights)) {
                            if($groupRights[$k] != $v && $v == '1') {
                                $groupRights[$k] = $v;
                            }
                        } else {
                            $groupRights[$k] = $v;
                        }
                    }
                }

                $userRight = false;
                $groupRight = false;

                if(array_key_exists($actionName, $rights)) {
                    $userRight = $rights[$actionName] ? true : false;
                }

                if(array_key_exists($actionName, $groupRights)) {
                    $groupRight = $groupRights[$actionName] ? true : false;
                }

                if($userRight == true || $groupRight == true) {
                    $result = true;
                } else {
                    $result = false;
                }

                $cm->saveActionRight($idUser, $actionName, $result);
            }
        } else {
            $rights = $this->userRightModel->getActionRightsForIdUser($idUser);

            $userGroups = $this->groupUserModel->getGroupsForIdUser($idUser);

            $groupRights = [];
            foreach($userGroups as $ug) {
                $idGroup = $ug->getIdGroup();

                $dbGroupRights = $this->groupRightModel->getActionRightsForIdGroup($idGroup);
                
                foreach($dbGroupRights as $k => $v) {
                    if(array_key_exists($k, $groupRights)) {
                        if($groupRights[$k] != $v && $v == '1') {
                            $groupRights[$k] = $v;
                        }
                    } else {
                        $groupRights[$k] = $v;
                    }
                }
            }

            $userRight = false;
            $groupRight = false;

            if(array_key_exists($actionName, $rights)) {
                $userRight = $rights[$actionName] ? true : false;
            }

            if(array_key_exists($actionName, $groupRights)) {
                $groupRight = $groupRights[$actionName] ? true : false;
            }

            if($userRight == true || $groupRight == true) {
                $result = true;
            } else {
                $result = false;
            }
        }

        return $result ? true : false;
    }

    public function canEditUser(?int $idCallingUser = null, bool $checkCache = true) {
        return $this->checkActionRight(UserActionRights::EDIT_USER, $idCallingUser, $checkCache);
    }

    public function canUseDocumentGenerator(?int $idCallingUser = null, bool $checkCache = true) {
        return $this->checkActionRight(UserActionRights::USE_DOCUMENT_GENERATOR, $idCallingUser, $checkCache);
    }

    public function canDeleteDocumentReports(?int $idCallingUser = null, bool $checkCache = true) {
        return $this->checkActionRight(UserActionRights::DELETE_DOCUMENT_REPORT_QUEUE_ENTRY, $idCallingUser, $checkCache);
    }

    public function canMoveEntitiesFromToArchive(?int $idCallingUser = null, bool $checkCache = true) {
        return $this->checkActionRight(UserActionRights::MOVE_ENTITIES_FROM_TO_ARCHIVE, $idCallingUser, $checkCache);
    }

    public function canMoveEntitiesWithinArchive(?int $idCallingUser = null, bool $checkCache = true) {
        return $this->checkActionRight(UserActionRights::MOVE_ENTITIES_WITHIN_ARCHIVE, $idCallingUser, $checkCache);
    }

    public function canGenerateDocumentReports(?int $idCallingUser = null, bool $checkCache = true) {
        return $this->checkActionRight(UserActionRights::GENERATE_DOCUMENT_REPORT, $idCallingUser, $checkCache);
    }

    public function canCreateDocument(?int $idCallingUser = null, bool $checkCache = true) {
        return $this->checkActionRight(UserActionRights::CREATE_DOCUMENT, $idCallingUser, $checkCache);
    }
}

?>