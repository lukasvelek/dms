<?php

namespace DMS\Authorizators;

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

        $cm = CacheManager::getTemporaryObject();

        $valFromCache = $cm->loadFromCache($actionName);

        $result = '';

        if(!is_null($valFromCache)) {
            $result = $valFromCache;
        } else {
            $rights = $app->userRightModel->getActionRightsForIdUser($app->user->getId());

            $cm->saveToCache($rights);

            if(array_key_exists($actionName, $rights)) {
                $result = $rights[$actionName];
            }
        }

        return $result ? true : false;
    }
}

?>