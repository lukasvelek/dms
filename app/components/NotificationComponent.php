<?php

namespace DMS\Components;

use DMS\Constants\NotificationStatus;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Notification;
use DMS\Models\NotificationModel;
use DMS\UI\LinkBuilder;

class NotificationComponent extends AComponent {
    private NotificationModel $notificationModel;

    public function __construct(Database $db, Logger $logger, NotificationModel $notificationModel) {
        parent::__construct($db, $logger);

        $this->notificationModel = $notificationModel;
    }

    public function createNewNotification(string $type, array $data) {
        if(method_exists($this, '_' . $type)) {
            return $this->{'_' . $type}($data);
        } else {
            return false;
        }
    }

    private function _processAssignedToUser(array $data) {
        $text = 'Process has been assigned to you.';

        //$link = LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleProcess:showProcess', 'id' => $data['id_process']), 'Open');

        $action = '?page=UserModule:SingleProcess:showProcess&id=' . $data['id_process'];

        //$text = str_replace('$LINK$', $link, $text);

        $this->notificationModel->insertNewNotification(array(
            'id_user' => $data['id_user'],
            'text' => $text,
            'status' => NotificationStatus::UNSEEN,
            'action' => $action
        ));
    }
}

?>