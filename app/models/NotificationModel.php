<?php

namespace DMS\Models;

use DMS\Constants\Metadata\NotificationMetadata;
use DMS\Constants\NotificationStatus;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Notification;

class NotificationModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function deleteNotificationById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('notifications')
            ->where(NotificationMetadata::ID . ' = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function getSeenNotifications() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('notifications')
            ->where(NotificationMetadata::STATUS . ' = ?', [NotificationStatus::SEEN])
            ->execute();

        $notifications = [];
        while($row = $qb->fetchAssoc()) {
            $notifications[] = $this->createNotificationObjectFromDbRow($row);
        }

        return $notifications;
    }

    public function setSeen(int $id) {
        return $this->updateStatus($id, NotificationStatus::SEEN);
    }

    public function insertNewNotification(array $data) {
        return $this->insertNew($data, 'notifications');
    }

    public function updateStatus(int $id, int $status) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('notifications')
            ->set([NotificationMetadata::STATUS => $status])
            ->where(NotificationMetadata::ID . ' = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function getNotificationsForUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('notifications')
            ->where(NotificationMetadata::ID_USER . ' = ?', [$idUser])
            ->andWhere(NotificationMetadata::STATUS . ' = ?', [NotificationStatus::UNSEEN])
            ->orderBy(NotificationMetadata::ID, 'DESC')
            ->execute();

        $notifications = [];
        while($row = $qb->fetchAssoc()) {
            $notifications[] = $this->createNotificationObjectFromDbRow($row);
        }

        return $notifications;
    }

    private function createNotificationObjectFromDbRow($row) {
        if($row === NULL) {
            return null;
        }
        
        $id = $row[NotificationMetadata::ID];
        $dateCreated = $row[NotificationMetadata::DATE_CREATED];
        $idUser = $row[NotificationMetadata::ID_USER];
        $text = $row[NotificationMetadata::TEXT];
        $action = $row[NotificationMetadata::ACTION];

        return new Notification($id, $dateCreated, $idUser, $text, $action);
    }
}

?>