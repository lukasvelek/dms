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
 * PanelAuthorizator checks if a panel is visible to a user.
 * 
 * @author Lukas Velek
 */
class PanelAuthorizator extends AAuthorizator {
    private UserRightModel $userRightModel;
    private GroupUserModel $groupUserModel;
    private GroupRightModel $groupRightModel;

    /**
     * The PanelAuthorizator constructor creates an object
     * 
     * @param UserRightModel $userRightModel UserRightModel instance
     * @param GroupUserModel $groupUserModel GroupUserModel instance
     * @param GroupRightModel $groupRightModel GroupRightModel instance
     */
    public function __construct(Database $db, Logger $logger, UserRightModel $userRightModel, GroupUserModel $groupUserModel, GroupRightModel $groupRightModel, ?User $user) {
        parent::__construct($db, $logger, $user);

        $this->userRightModel = $userRightModel;
        $this->groupUserModel = $groupUserModel;
        $this->groupRightModel = $groupRightModel;
    }

    /**
     * Checks if a panel is visible to a user.
     * 
     * @param string $panelName Panel name
     * @param null|int $idUser User ID
     * @param bool $checkCache True if cache should be checked and false if not
     * @return bool True if panel is visible and false if not
     */
    public function checkPanelRight(string $panelName, ?int $idUser = null, bool $checkCache = true) {
        if(is_null($idUser)) {
            if(empty($this->idUser)) {
                return false;
            }

            $idUser = $this->idUser;
        }

        $result = '';

        if($checkCache) {
            $cm = CacheManager::getTemporaryObject(CacheCategories::PANELS);

            $valFromCache = $cm->loadPanelRight($idUser, $panelName);

            if(!is_null($valFromCache)) {
                $result = $valFromCache;
            } else {
                $rights = $this->userRightModel->getPanelRightsForIdUser($idUser);

                $userGroups = $this->groupUserModel->getGroupsForIdUser($idUser);

                $groupRights = [];
                foreach($userGroups as $ug) {
                    $idGroup = $ug->getIdGroup();

                    $dbGroupRights = $this->groupRightModel->getPanelRightsForIdGroup($idGroup);
                
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

                if(array_key_exists($panelName, $rights)) {
                    $userRight = $rights[$panelName] ? true : false;
                }

                if(array_key_exists($panelName, $groupRights)) {
                    $groupRight = $groupRights[$panelName] ? true : false;
                }

                if($userRight == true || $groupRight == true) {
                    $result = true;
                } else {
                    $result = false;
                }

                $cm->savePanelRight($idUser, $panelName, $result);
            }
        } else {
            $rights = $this->userRightModel->getPanelRightsForIdUser($idUser);

            $userGroups = $this->groupUserModel->getGroupsForIdUser($idUser);

            $groupRights = [];
            foreach($userGroups as $ug) {
                $idGroup = $ug->getIdGroup();

                $dbGroupRights = $this->groupRightModel->getPanelRightsForIdGroup($idGroup);
                
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

            if(array_key_exists($panelName, $rights)) {
                $userRight = $rights[$panelName] ? true : false;
            }

            if(array_key_exists($panelName, $groupRights)) {
                $groupRight = $groupRights[$panelName] ? true : false;
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