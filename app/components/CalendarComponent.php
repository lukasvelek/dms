<?php

namespace DMS\Components;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Models\CalendarModel;
use DMS\UI\CalendarBuilder\CalendarBuilder;

class CalendarComponent extends AComponent {
    private CalendarModel $calendarModel;

    public function __construct(Database $db, Logger $logger, CalendarModel $calendarModel) {
        parent::__construct($db, $logger);
        
        $this->calendarModel = $calendarModel;
    }

    public function createCalendarForDate(int $month, int $year, array $tagsAllowed = []) {
        $calendar = $this->getCalendarForDate($month, $year, $tagsAllowed);

        $events = $this->calendarModel->getAllEventsForMonthAndYear((string)$month, (string)$year);
        $calendar->addEventObjects($events);

        return $calendar->build();
    }

    public function createCalendarForNow(array $tagsAllowed = []) {
        return $this->createCalendarForDate((int)date('m'), (int)date('Y'), $tagsAllowed);
    }

    public function createCalendarController(string $handler, int $month, int $year) {
        return $this->getCalendarForDate($month, $year)->getController($handler);
    }

    public function getCalendarForDate(int $month, int $year, array $tagsAllowed = []) {
        $cb = CalendarBuilder::getTemporaryObject();

        $cb ->setMonth($month)
            ->setYear($year)
            ->allowEventTags($tagsAllowed);

        return $cb;
    }
}

?>