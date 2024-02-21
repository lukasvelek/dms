<?php

namespace DMS\Models;

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
            ->where('id = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function getSeenNotifications() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('notifications')
            ->where('status = ?', [NotificationStatus::SEEN])
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
            ->set(['status' => $status])
            ->where('id = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function getNotificationsForUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('notifications')
            ->where('id_user = ?', [$idUser])
            ->andWhere('status = ?', [NotificationStatus::UNSEEN])
            ->orderBy('id', 'DESC')
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
        
        $id = $row['id'];
        $dateCreated = $row['date_created'];
        $idUser = $row['id_user'];
        $text = $row['text'];
        $action = $row['action'];

        return new Notification($id, $dateCreated, $idUser, $text, $action);
    }
}

?>