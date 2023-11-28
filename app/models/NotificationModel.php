<?php

namespace DMS\Models;

use DMS\Constants\NotificationQueueStatus;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Notification;

class NotificationModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    

    private function createNotificationObjectFromDbRow($row) {
        $id = $row['id'];
        $name = $row['name'];
        $text = $row['text'];

        return new Notification($id, $name, $text);
    }
}

?>