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
            //die('User is not set');
            return false;
        }

        //$rights = $app->userRightModel->getPanelRightsForIdUser($app->user->getId());

        $cm = CacheManager::getTemporaryObject();

        //$cm->saveToCache($rights);

        $valFromCache = $cm->loadFromCache($panelName);

        $result = '';

        if(!is_null($valFromCache)) {
            $result = $valFromCache;
        } else {
            $rights = $app->userRightModel->getPanelRightsForIdUser($app->user->getId());

            $cm->saveToCache($rights);

            if(array_key_exists($panelName, $rights)) {
                $result = $rights[$panelName];
            } else {
                $result = 0;
            }
        }

        return /*$rights[$panelName]*/ $result ? true : false;
    }
}

?>