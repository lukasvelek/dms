<?php

namespace DMS\Authorizators;

use DMS\Core\CacheManager;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class PanelAuthorizator extends AAuthorizator {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function checkPanelRight(string $panelName) {
        global $app;

        if(is_null($app->user)) {
            return false;
        }

        $cm = CacheManager::getTemporaryObject();

        $valFromCache = $cm->loadFromCache($panelName);

        $result = '';

        if(!is_null($valFromCache)) {
            $result = $valFromCache;
        } else {
            $rights = $app->userRightModel->getPanelRightsForIdUser($app->user->getId());

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

            $cm->saveToCache($finalRights);

            if(array_key_exists($panelName, $finalRights)) {
                $result = $rights[$panelName];
            } else {
                $result = 0;
            }
        }

        return $result ? true : false;
    }
}

?>