<?php

namespace DMS\Authorizators;

use DMS\Core\CacheManager;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class BulkActionAuthorizator extends AAuthorizator {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function checkBulkActionRight(string $bulkActionName) {
        global $app;

        if(is_null($app->user)) {
            return false;
        }

        $cm = CacheManager::getTemporaryObject();

        $valFromCache = $cm->loadFromCache($bulkActionName);

        $result = '';

        if(!is_null($valFromCache)) {
            $result = $valFromCache;
        } else {
            $rights = $app->userRightModel->getBulkActionRightsForIdUser($app->user->getId());

            $cm->saveToCache($rights);

            if(array_key_exists($bulkActionName, $rights)) {
                $result = $rights[$bulkActionName];
            }
        }

        return $result ? true : false;
    }
}

?>