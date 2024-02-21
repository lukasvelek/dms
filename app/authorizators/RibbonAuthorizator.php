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

/**
 * RibbonAuthorizator contains methods that perform operations regarded to ribbons (or as deprecated panels)
 * 
 * @author Lukas Velek
 */
class RibbonAuthorizator extends AAuthorizator {
    private RibbonModel $ribbonModel;
    private RibbonRightsModel $ribbonRightsModel;
    private GroupUserModel $groupUserModel;

    /**
     * Class constructor
     * 
     * @param Database $db Database instance
     * @param Logger $logger Logger instance
     * @param null|User $user User database
     * @param RibbonModel $ribbonModel RibbonModel instance
     * @param RibbonRightsModel $ribbonRightsModel RibbonRightsModel instance
     * @param GroupUserModel $groupUserModel GroupUserModel instance
     */
    public function __construct(Database $db, Logger $logger, ?User $user, RibbonModel $ribbonModel, RibbonRightsModel $ribbonRightsModel, GroupUserModel $groupUserModel) {
        parent::__construct($db, $logger, $user);

        $this->ribbonModel = $ribbonModel;
        $this->ribbonRightsModel = $ribbonRightsModel;
        $this->groupUserModel = $groupUserModel;
    }

    /**
     * Returns IDs of ribbons that can be deleted by user
     * 
     * @param null|int $idUser User ID
     * @return array Ribbon IDs
     */
    public function getDeletableRibbonsForIdUser(?int $idUser) {
        if(is_null($idUser)) {
            if(empty($this->idUser)) {
                return false;
            }

            $idUser = $this->idUser;
        }

        return Database::convertMysqliResultToArray($this->ribbonRightsModel->getAllDeletableRibbonsForIdUser($idUser), ['id_ribbon']);
    }

    /**
     * Returns IDs of ribbons that can be edited by user
     * 
     * @param null|int $idUser User ID
     * @return array Ribbon IDs
     */
    public function getEditableRibbonsForIdUser(?int $idUser) {
        if(is_null($idUser)) {
            if(empty($this->idUser)) {
                return false;
            }

            $idUser = $this->idUser;
        }

        return Database::convertMysqliResultToArray($this->ribbonRightsModel->getAllEditableRibbonsForIdUser($idUser), ['id_ribbon']);
    }

    /**
     * Checks if a given ribbon is visible for given user
     * 
     * @param null|int $idUser User ID
     * @param Ribbon $ribbon Ribbon instance
     * @return bool True if ribbon is visible
     */
    public function checkRibbonVisible(?int $idUser, Ribbon $ribbon) {
        if(is_null($idUser)) {
            if(empty($this->idUser)) {
                return false;
            }

            $idUser = $this->idUser;
        }
        
        return $this->checkRight($idUser, $ribbon->getId(), self::VIEW);
    }

    /**
     * Checks if a given ribbon is editable for given user
     * 
     * @param null|int $idUser User ID
     * @param Ribbon $ribbon Ribbon instance
     * @return bool True if ribbon is editable
     */
    public function checkRibbonEditable(?int $idUser, Ribbon $ribbon) {
        if(is_null($idUser)) {
            if(empty($this->idUser)) {
                return false;
            }

            $idUser = $this->idUser;
        }

        return $this->checkRight($idUser, $ribbon->getId(), self::EDIT);
    }

    /**
     * Checks if a given ribbon is deletable for given user
     * 
     * @param null|int $idUser User ID
     * @param Ribbon $ribbon Ribbon instance
     * @return bool True if ribbon is deletable
     */
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

    /**
     * Checks if a given user is able to perform an action on a ribbon
     * 
     * @param int $idUser User ID
     * @param int $idRibbon Ribbon ID
     * @param string $action Action name
     * @return bool True if an action is allowed
     */
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

    /**
     * Returns IDs of groups a given user is member of 
     * 
     * @param int $idUser User ID
     * @return array Group IDs
     */
    private function getUserIdGroups(int $idUser) {
        $userGroups = $this->groupUserModel->getGroupsForIdUser($idUser);

        $idGroups = [];
        foreach($userGroups as $userGroup) {
            $idGroups[] = $userGroup->getIdGroup();
        }

        return $idGroups;
    }

    /**
     * Checks if user is allowed to perform an action on a ribbon
     * 
     * @param int $idUser User ID
     * @param int $idRibbon Ribbon ID
     * @param string $action Action name
     * @return bool True if action is allowed
     */
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

    /**
     * Checks if group is allowed to perform an action on a ribbon
     * 
     * @param int $idGroup Group ID
     * @param int $idRibbon Ribbon ID
     * @param string $action Action name
     * @return bool True if action is allowed
     */
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