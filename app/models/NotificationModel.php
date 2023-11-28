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

    public function insertNotification(array $data) {
        return $this->insertNew($data, 'notifications');
    }

    public function insertToQueue(array $data) {
        return $this->insertNew($data, 'notification_queue');
    }

    public function getAllNotifications() {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('notifications')
                   ->execute()
                   ->fetch();

        $notifications = [];
        foreach($rows as $row) {
            $notifications[] = $this->createNotificationObjectFromDbRow($row);
        }

        return $notifications;
    }

    public function getNotificationQueue() {
        $qb = $this->createNotificationQueueQb(__METHOD__);

        $rows = $qb->execute()->fetch();

        return $rows;
    }

    public function getSentNotificationsFromQueue() {
        $qb = $this->createNotificationQueueQb(__METHOD__);

        $rows = $qb->where('status=:status')
                   ->setParam(':status', NotificationQueueStatus::SENT)
                   ->execute()
                   ->fetch();

        return $rows;
    }

    public function getWaitingNotificationsFromQueue() {
        $qb = $this->createNotificationQueueQb(__METHOD__);

        $rows = $qb->where('status=:status')
                   ->setParam(':status', NotificationQueueStatus::IN_QUEUE)
                   ->execute()
                   ->fetch();

        return $rows;
    }

    public function getErrorNotificationsFromQueue() {
        $qb = $this->createNotificationQueueQb(__METHOD__);

        $rows = $qb->where('status=:status')
                   ->setParam(':status', NotificationQueueStatus::ERROR)
                   ->execute()
                   ->fetch();

        return $rows;
    }

    private function createNotificationQueueQb(?string $method = null) {
        $qb = $this->qb($method ?? __METHOD__);

        $qb->select('*')
           ->from('notification_queue');

        return $qb;
    }

    private function createNotificationObjectFromDbRow($row) {
        $id = $row['id'];
        $name = $row['name'];
        $text = $row['text'];

        return new Notification($id, $name, $text);
    }
}

?>