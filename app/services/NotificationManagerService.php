<?php

namespace DMS\Services;

use DMS\Core\CacheManager;
use DMS\Core\Logger\Logger;
use DMS\Models\NotificationModel;
use DMS\Models\ServiceModel;

class NotificationManagerService extends AService {
    private NotificationModel $notificationModel;
    private array $cfg;

    public function __construct(Logger $logger, ServiceModel $serviceModel, CacheManager $cacheManager, NotificationModel $notificationModel, array $cfg) {
        parent::__construct('NotificationManagerService', 'Service responsible for deleting old notifications', $logger, $serviceModel, $cacheManager);

        $this->notificationModel = $notificationModel;
        $this->cfg = $cfg;

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

        if($this->scfg['notification_keep_unseen_service_user'] == '1') {
            $serviceUserNotifications = $this->notificationModel->getNotificationsForUser($this->cfg['id_service_user']);
        
            foreach($serviceUserNotifications as $notification) {
                $toDelete[] = $notification->getId();
            }

            $this->log('Found ' . count($serviceUserNotifications) . ' service user notifications to delete', __METHOD__);
        } else {
            $this->log('Keeping unseen service user\'s notifications is enabled -> skipping...', __METHOD__);
        }

        $this->notificationModel->beginTran();

        foreach($toDelete as $id) {
            $this->notificationModel->deleteNotificationById($id);
        }

        $this->notificationModel->commitTran();

        $this->stopService();
    }
}

?>