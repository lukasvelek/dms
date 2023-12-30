<?php

namespace DMS\Services;

use DMS\Core\CacheManager;
use DMS\Core\Logger\Logger;
use DMS\Models\NotificationModel;
use DMS\Models\ServiceModel;

class NotificationManagerService extends AService {
    private NotificationModel $notificationModel;

    public function __construct(Logger $logger, ServiceModel $serviceModel, CacheManager $cacheManager, NotificationModel $notificationModel) {
        parent::__construct('NotificationManagerService', 'Service responsible for deleting old notifications', $logger, $serviceModel, $cacheManager);

        $this->notificationModel = $notificationModel;

        $this->loadCfg();
    }

    public function run() {
        $this->startService();

        $notifications = $this->notificationModel->getSeenNotifications();

        $toDelete = [];
        foreach($notifications as $notification) {
            $dateCreated = $notification->getDateCreated();

            if(time() > (strtotime($dateCreated) + ($this->scfg['notification_keep_length'] * 24 * 60 * 60))) {
                $toDelete[] = $notification->getId();
            }
        }

        $this->log('Found ' . count($toDelete) . ' notifications to delete', __METHOD__);

        $this->notificationModel->beginTran();

        foreach($toDelete as $id) {
            $this->notificationModel->deleteNotificationById($id);
        }

        $this->notificationModel->commitTran();

        $this->stopService();
    }
}

?>