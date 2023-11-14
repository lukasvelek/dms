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

class ActionAuthorizator extends AAuthorizator {
    private UserRightModel $userRightModel;
    private GroupUserModel $groupUserModel;
    private GroupRightModel $groupRightModel;

    public function __construct(Database $db, Logger $logger, UserRightModel $userRightModel, GroupUserModel $groupUserModel, GroupRightModel $groupRightModel, ?User $user) {
        parent::__construct($db, $logger, $user);

        $this->userRightModel = $userRightModel;
        $this->groupUserModel = $groupUserModel;
        $this->groupRightModel = $groupRightModel;
    }

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

                $finalRights = [];

                foreach($rights as $k => $v) {
                    if(array_key_exists($k, $groupRights)) {
                        if($groupRights[$k] != $v && $v == '1') {
                            $finalRights[$k] = $v;
                        }
                    } else {
                        $finalRights[$k] = $v;
                    }
                }

                $cm->saveActionRight($idUser, $actionName, $finalRights[$actionName]);

                if(array_key_exists($actionName, $finalRights)) {
                    $result = $finalRights[$actionName];
                } else {
                    $result = 0;
                }
            }
        } else {
            $rights = $this->userRightModel->getActionRightsForIdUser($idUser);

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

            $finalRights = [];

            foreach($rights as $k => $v) {
                if(array_key_exists($k, $groupRights)) {
                    if($groupRights[$k] != $v && $v == '1') {
                        $finalRights[$k] = $v;
                    }
                } else {
                    $finalRights[$k] = $v;
                }
            }

            if(array_key_exists($actionName, $finalRights)) {
                $result = $finalRights[$actionName];
            } else {
                $result = 0;
            }
        }

        return $result ? true : false;
    }
}

?>