<?php

namespace DMS\Components;

use DMS\Constants\Notifications;
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

    /**
     * Notification to inform a user that a process they started has been finished.
     * 
     * Data must contain:
     *  'id_user' - the user the notification will pop up
     *  'id_process' - the process that has finished
     * 
     * @param array $data Data array
     * @return bool True
     */
    private function _processFinished(array $data) {
        $text = 'Process you started has finished.';

        $action = '?page=UserModule:SingleProcess:showProcess&id=' . $data['id_process'];

        $this->notificationModel->insertNewNotification(array(
            'id_user' => $data['id_user'],
            'text' => $text,
            'status' => NotificationStatus::UNSEEN,
            'action' => $action
        ));

        return true;
    }

    /**
     * Notification to inform a user that he has been assigned a process.
     * 
     * Data must contain:
     *  'id_user' - the user the notification will pop up
     *  'id_process' - the process the user has been assigned
     * 
     * @param array $data Data array
     * @return bool True
     */
    private function _processAssignedToUser(array $data) {
        $text = 'Process has been assigned to you.';

        $action = '?page=UserModule:SingleProcess:showProcess&id=' . $data['id_process'];

        $this->notificationModel->insertNewNotification(array(
            'id_user' => $data['id_user'],
            'text' => $text,
            'status' => NotificationStatus::UNSEEN,
            'action' => $action
        ));

        return true;
    }
}

?>