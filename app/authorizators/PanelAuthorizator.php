<?php

namespace DMS\Authorizators;

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

        $rights = $app->userRightModel->getPanelRightsForIdUser($app->user->getId());

        return $rights[$panelName] ? true : false;
    }
}

?>