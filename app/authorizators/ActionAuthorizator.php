<?php

namespace DMS\Authorizators;

use DMS\Constants\CacheCategories;
use DMS\Core\CacheManager;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class ActionAuthorizator extends AAuthorizator {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function checkActionRight(string $actionName) {
        global $app;

        if(is_null($app->user)) {
            return false;
        }

        //$cm = CacheManager::getTemporaryObject();

        $valFromCache = null;

        //$valFromCache = $cm->loadFromCache(CacheCategories::ACTIONS, $actionName);

        $result = '';

        if(!is_null($valFromCache)) {
            $result = $valFromCache;
        } else {
            $rights = $app->userRightModel->getActionRightsForIdUser($app->user->getId());

            $userGroups = $app->groupUserModel->getGroupsForIdUser($app->user->getId());

            $groupRights = [];
            foreach($userGroups as $ug) {
                $idGroup = $ug->getIdGroup();

                $dbGroupRights = $app->groupRightModel->getPanelRightsForIdGroup($idGroup);
                
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

            //$cm->saveToCache(CacheCategories::ACTIONS, $finalRights);

            if(array_key_exists($actionName, $finalRights)) {
                $result = $rights[$actionName];
            } else {
                $result = 0;
            }
        }

        return $result ? true : false;
    }
}

?>