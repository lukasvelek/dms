<?php

namespace DMS\Authorizators;

use DMS\Constants\CacheCategories;
use DMS\Core\CacheManager;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Models\GroupRightModel;
use DMS\Models\GroupUserModel;
use DMS\Models\UserRightModel;

class BulkActionAuthorizator extends AAuthorizator {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function ajaxCheckRight(string $bulkActionName, int $idUser, UserRightModel $userRightModel, GroupRightModel $groupRightModel, GroupUserModel $groupUserModel) {
        $rights = $userRightModel->getBulkActionRightsForIdUser($idUser);
        $userGroups = $groupUserModel->getGroupsForIdUser($idUser);

        $groupRights = [];
        foreach($userGroups as $ug) {
            $idGroup = $ug->getIdGroup();

            $dbGroupRights = $groupRightModel->getBulkActionRightsForIdGroup($idGroup);
                
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

        if(array_key_exists($bulkActionName, $finalRights)) {
            $result = $finalRights[$bulkActionName];
        } else {
            $result = 0;
        }

        return $result ? true : false;
    }

    public function checkBulkActionRight(string $bulkActionName, int $idUser = null) {
        global $app;

        if(is_null($app->user)) {
            return false;
        }

        $cm = CacheManager::getTemporaryObject(CacheCategories::BULK_ACTIONS);

        $valFromCache = $cm->loadBulkActionRight($app->user->getId(), $bulkActionName);

        $result = '';

        if(!is_null($valFromCache)) {
            $result = $valFromCache;
        } else {
            $rights = $app->userRightModel->getBulkActionRightsForIdUser($app->user->getId());

            $userGroups = $app->groupUserModel->getGroupsForIdUser($app->user->getId());

            $groupRights = [];
            foreach($userGroups as $ug) {
                $idGroup = $ug->getIdGroup();

                $dbGroupRights = $app->groupRightModel->getBulkActionRightsForIdGroup($idGroup);
                
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

            $cm->saveBulkActionRight($app->user->getId(), $bulkActionName, $finalRights[$bulkActionName]);

            if(array_key_exists($bulkActionName, $finalRights)) {
                $result = $finalRights[$bulkActionName];
            } else {
                $result = 0;
            }
        }

        return $result ? true : false;
    }
}

?>