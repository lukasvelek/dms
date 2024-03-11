<?php

namespace DMS\Models;

use DMS\Constants\Metadata\CalendarEventsMetadata;
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
            ->where("`" . CalendarEventsMetadata::DATE_FROM . "` LIKE ?", [$year . '-' . $month . '-%'])
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
            ->where('`' . CalendarEventsMetadata::DATE_FROM . '` BETWEEN ? AND ?', [$dateFrom, $dateTo])
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
        $id = $row[CalendarEventsMetadata::ID];
        $dateCreated = $row[CalendarEventsMetadata::DATE_CREATED];
        $title = $row[CalendarEventsMetadata::TITLE];
        $color = $row[CalendarEventsMetadata::COLOR];
        $dateFrom = $row[CalendarEventsMetadata::DATE_FROM];
        $time = $row[CalendarEventsMetadata::TIME];
        $tag = null;
        $dateTo = null;

        if(isset($row[CalendarEventsMetadata::TAG])) {
            $tag = $row[CalendarEventsMetadata::TAG];
        }

        if(isset($row[CalendarEventsMetadata::DATE_TO])) {
            $dateTo = $row[CalendarEventsMetadata::DATE_TO];
        }

        return new CalendarEventEntity($id, $dateCreated, $title, $color, $tag, $dateFrom, $dateTo, $time);
    }
}

?>