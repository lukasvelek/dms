<?php

namespace DMS\Authorizators;

use DMS\Constants\CacheCategories;
use DMS\Core\CacheManager;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\User;
use DMS\Models\GroupRightModel;
use DMS\Models\GroupUserModel;
use DMS\Models\UserRightModel;

/**
 * BulkActionAuthorizator checks if an entity is allowed to perform a bulk action.
 * 
 * @author Lukas Velek
 */
class BulkActionAuthorizator extends AAuthorizator {
    private UserRightModel $userRightModel;
    private GroupUserModel $groupUserModel;
    private GroupRightModel $groupRightModel;

    /**
     * BulkActionAuthorizator constructor creates an object
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
     * This method checks if a user (currently login or other) is allowed to perform a bulk action of a name. It can also check cache for faster performance.
     * 
     * @param string $bulkActionName Bulk action name
     * @param null|int $idUser User ID
     * @param bool $checkCache True if cache should be checked and false if not
     * @return bool True if user is allowed to perform the bulk action and false if not
     */
    public function checkBulkActionRight(string $bulkActionName, ?int $idUser = null, bool $checkCache = false, array $cfg = []) {
        if(is_null($idUser)) {
            if(empty($this->idUser)) {
                return false;
            }
            
            $idUser = $this->idUser;
        }

        $result = '';

        if($checkCache) {
            $cm = CacheManager::getTemporaryObject(CacheCategories::BULK_ACTIONS, true, $cfg);

            $valFromCache = $cm->loadBulkActionRight($idUser, $bulkActionName);

            if(!is_null($valFromCache)) {
                $result = $valFromCache;
            } else {
                $rights = $this->userRightModel->getBulkActionRightsForIdUser($idUser);

                $userGroups = $this->groupUserModel->getGroupsForIdUser($idUser);

                $groupRights = [];
                foreach($userGroups as $ug) {
                    $idGroup = $ug->getIdGroup();

                    $dbGroupRights = $this->groupRightModel->getBulkActionRightsForIdGroup($idGroup);
                
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

                if(array_key_exists($bulkActionName, $rights)) {
                    $userRight = $rights[$bulkActionName] ? true : false;
                }

                if(array_key_exists($bulkActionName, $groupRights)) {
                    $groupRight = $groupRights[$bulkActionName] ? true : false;
                }

                if($userRight == true || $groupRight == true) {
                    $result = true;
                } else {
                    $result = false;
                }

                $cm->saveBulkActionRight($idUser, $bulkActionName, $result);
            }
        } else {
            $rights = $this->userRightModel->getBulkActionRightsForIdUser($idUser);

            if(array_key_exists($bulkActionName, $rights)) {
                return $rights[$bulkActionName] ? true : false;
            }

            $userGroups = $this->groupUserModel->getGroupsForIdUser($idUser);

            $groupRights = [];
            foreach($userGroups as $ug) {
                $idGroup = $ug->getIdGroup();

                $dbGroupRights = $this->groupRightModel->getBulkActionRightsForIdGroup($idGroup);
                
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

            if(array_key_exists($bulkActionName, $rights)) {
                $userRight = $rights[$bulkActionName] ? true : false;
            }

            if(array_key_exists($bulkActionName, $groupRights)) {
                $groupRight = $groupRights[$bulkActionName] ? true : false;
            }

            if($userRight == true || $groupRight == true) {
                $result = true;
            } else {
                $result = false;
            }
        }

        return $result ? true : false;

    }
}

?>