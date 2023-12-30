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

        $result = $qb->delete()
                     ->from('notifications')
                     ->where('id=:id')
                     ->setParam(':id', $id)
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function getSeenNotifications() {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('notifications')
                   ->where('status=:status')
                   ->setParam(':status', NotificationStatus::SEEN)
                   ->execute()
                   ->fetch();

        $notifications = [];
        foreach($rows as $row) {
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

        $result = $qb->update('notifications')
                     ->set(array(
                        'status' => ':status'
                     ))
                     ->where('id=:id')
                     ->setParams(array(
                        ':id' => $id,
                        ':status' => $status
                     ))
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function getNotificationsForUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('notifications')
                   ->where('id_user=:id_user')
                   ->andWhere('status=:status')
                   ->orderBy('id', 'DESC')
                   ->setParam(':id_user', $idUser)
                   ->setParam(':status', NotificationStatus::UNSEEN)
                   ->execute()
                   ->fetch();

        $notifications = [];
        foreach($rows as $row) {
            $notifications[] = $this->createNotificationObjectFromDbRow($row);
        }

        return $notifications;
    }

    private function createNotificationObjectFromDbRow($row) {
        $id = $row['id'];
        $dateCreated = $row['date_created'];
        $idUser = $row['id_user'];
        $text = $row['text'];
        $action = $row['action'];

        return new Notification($id, $dateCreated, $idUser, $text, $action);
    }
}

?>