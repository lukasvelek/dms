<?php

namespace DMS\Widgets\HomeDashboard;

use DMS\Entities\User;
use DMS\Models\NotificationModel;
use DMS\UI\LinkBuilder;
use DMS\Widgets\AWidget;

class Notifications extends AWidget {
    private NotificationModel $notificationModel;
    private ?int $idUser;

    public function __construct(NotificationModel $notificationModel) {
        parent::__construct();

        $this->notificationModel = $notificationModel;

        if(isset($_SESSION['id_current_user'])) {
            $this->idUser = $_SESSION['id_current_user'];
        } else {
            $this->idUser = null;
        }
    }

    public function render() {
        if($this->idUser === NULL) {
            $this->add('Cannot load current user\'s ID!', '');
            return parent::render();
        }

        $notifications = $this->notificationModel->getNotificationsForUser($this->idUser);

        $maxCount = 5;

        $i = 0;
        foreach($notifications as $notification) {
            if(($i + 1) == $maxCount) {
                break;
            }

            $actionLink = '<a class="general-link" onclick="useNotification(\'' . $notification->getId() . '\', \'' . $notification->getAction() . '\')" style="cursor: pointer">Open</a>';

            $this->add('', $notification->getText() . ' ' . $actionLink, false);

            $i++;
        }

        return parent::render();
    }
}

?>