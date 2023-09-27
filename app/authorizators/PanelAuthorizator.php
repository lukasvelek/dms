<?php

namespace DMS\Authorizators;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class PanelAuthorizator extends AAuthorizator {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function checkPanelRight(string $panelName) {
        if(is_null($this->currentUser)) {
            die('User is not set');
        }

        global $app;

        $rights = $app->userRightModel->getPanelRightsForIdUser($this->currentUser->getId());

        return $rights[$panelName] ? true : false;
    }
}

?>