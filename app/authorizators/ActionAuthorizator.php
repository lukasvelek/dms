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

    public function checkActionRight(string $actionName, ?int $idUser = null) {
        global $app;

        if(is_null($idUser)) {
            $idUser = $app->user->getId();
        }

        $cm = CacheManager::getTemporaryObject(CacheCategories::ACTIONS);

        $valFromCache = $cm->loadActionRight($idUser, $actionName);

        $result = '';

        if(!is_null($valFromCache)) {
            $result = $valFromCache;
        } else {
            $rights = $app->userRightModel->getActionRightsForIdUser($idUser);

            $userGroups = $app->groupUserModel->getGroupsForIdUser($idUser);

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

            $cm->saveActionRight($idUser, $actionName, $finalRights[$actionName]);

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