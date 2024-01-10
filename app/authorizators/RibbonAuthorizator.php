<?php

namespace DMS\Authorizators;

use DMS\Constants\CacheCategories;
use DMS\Core\CacheManager;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Ribbon;
use DMS\Entities\User;
use DMS\Models\GroupUserModel;
use DMS\Models\RibbonModel;
use DMS\Models\RibbonRightsModel;

class RibbonAuthorizator extends AAuthorizator {
    private RibbonModel $ribbonModel;
    private RibbonRightsModel $ribbonRightsModel;
    private GroupUserModel $groupUserModel;

    public function __construct(Database $db, Logger $logger, ?User $user, RibbonModel $ribbonModel, RibbonRightsModel $ribbonRightsModel, GroupUserModel $groupUserModel) {
        parent::__construct($db, $logger, $user);

        $this->ribbonModel = $ribbonModel;
        $this->ribbonRightsModel = $ribbonRightsModel;
        $this->groupUserModel = $groupUserModel;
    }

    public function checkRibbonVisible(?int $idUser, Ribbon $ribbon) {
        if(is_null($idUser)) {
            if(empty($this->idUser)) {
                return false;
            }

            $idUser = $this->idUser;
        }
        
        return $this->checkRight($idUser, $ribbon->getId(), self::VIEW);
    }

    public function checkRibbonEditable(?int $idUser, Ribbon $ribbon) {
        if(is_null($idUser)) {
            if(empty($this->idUser)) {
                return false;
            }

            $idUser = $this->idUser;
        }

        return $this->checkRight($idUser, $ribbon->getId(), self::EDIT);
    }

    public function checkRibbonDeletable(?int $idUser, Ribbon $ribbon) {
        if(is_null($idUser)) {
            if(empty($this->idUser)) {
                return false;
            }

            $idUser = $this->idUser;
        }

        $right = $this->checkRight($idUser, $ribbon->getId(), self::DELETE);

        if($ribbon->isSystem() || !$right) {
            return false;
        } else {
            return true;
        }
    }

    private function checkRight(int $idUser, int $idRibbon, string $action) {
        $userResult = $this->checkUserRibbonRight($idUser, $idRibbon, $action);

        if($userResult === true) {
            return true;
        } else {
            $idGroups = $this->getUserIdGroups($idUser);

            $groupResult = false;

            foreach($idGroups as $idGroup) {
                $result = $this->checkGroupRibbonRight($idGroup, $idRibbon, $action);

                if($result === true) {
                    $groupResult = true;
                    break;
                }
            }

            return $groupResult;
        }
    }

    private function getUserIdGroups(int $idUser) {
        $userGroups = $this->groupUserModel->getGroupsForIdUser($idUser);

        $idGroups = [];
        foreach($userGroups as $userGroup) {
            $idGroups[] = $userGroup->getIdGroup();
        }

        return $idGroups;
    }

    private function checkUserRibbonRight(int $idUser, int $idRibbon, string $action) {
        $cm = CacheManager::getTemporaryObject(CacheCategories::RIBBON_USER_RIGHTS);

        $valFromCache = $cm->loadUserRibbonRight($idRibbon, $idUser, $action);

        if(!is_null($valFromCache)) {
            return $valFromCache;
        } else {
            $result = $this->ribbonRightsModel->getRightValueForIdRibbonAndIdUser($idRibbon, $idUser, $action);

            if($result === FALSE) {
                return false;
            }

            if($result == '1') {
                $result = true;
            } else {
                $result = false;
            }

            $cm->saveUserRibbonRight($idRibbon, $idUser, $action, $result);

            return $result;
        }
    }

    private function checkGroupRibbonRight(int $idGroup, int $idRibbon, string $action) {
        $cm = CacheManager::getTemporaryObject(CacheCategories::RIBBON_GROUP_RIGHTS);

        $valFromCache = $cm->loadGroupRibbonRight($idRibbon, $idGroup, $action);

        if(!is_null($valFromCache)) {
            return $valFromCache;
        } else {
            $result = $this->ribbonRightsModel->getRightValueForIdRibbonAndIdGroup($idRibbon, $idGroup, $action);

            if($result === FALSE) {
                return false;
            }

            if($result == '1') {
                $result = true;
            } else {
                $result = false;
            }

            $cm->saveGroupRibbonRight($idRibbon, $idGroup, $action, $result);

            return $result;
        }
    }
}

?>