<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\CalendarEventEntity;

class CalendarModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getAllEventsForMonthAndYear(string $month, string $year) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('calendar_events')
            ->where("`date_from` LIKE ?", [$year . '-' . $month . '-%'])
            ->execute();

        $events = [];
        while($row = $qb->fetchAssoc()) {
            $events[] = $this->createCalendarEventObjectFromDbRow($row);
        }

        return $events;
    }

    public function getAllEventsBetweenDates(string $dateFrom, string $dateTo) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('calendar_events')
            ->where('`date_from` BETWEEN ? AND ?', [$dateFrom, $dateTo])
            ->execute();

        $events = [];
        while($row = $qb->fetchAssoc()) {
            $events[] = $this->createCalendarEventObjectFromDbRow($row);
        }

        return $events;
    }

    public function insertNewEvent(array $data) {
        return $this->insertNew($data, 'calendar_events');
    }

    public function updateEvent(int $id, array $data) {
        return $this->updateExisting('calendar_events', $id, $data);
    }

    private function createCalendarEventObjectFromDbRow($row) {
        $id = $row['id'];
        $dateCreated = $row['date_created'];
        $title = $row['title'];
        $color = $row['color'];
        $dateFrom = $row['date_from'];
        $time = $row['time'];
        $tag = null;
        $dateTo = null;

        if(isset($row['tag'])) {
            $tag = $row['tag'];
        }

        if(isset($row['date_to'])) {
            $dateTo = $row['date_to'];
        }

        return new CalendarEventEntity($id, $dateCreated, $title, $color, $tag, $dateFrom, $dateTo, $time);
    }
}

?>