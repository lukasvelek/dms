<?php

namespace DMS\Components;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Models\CalendarModel;
use DMS\UI\CalendarBuilder\CalendarBuilder;

/**
 * Component that allows creating calendar
 * 
 * @author Lukas Velek
 */
class CalendarComponent extends AComponent {
    private CalendarModel $calendarModel;

    /**
     * Class constructor
     * 
     * @param Database $db Database instance
     * @param Logger $logger Logger instance
     * @param CalendarModel $calendarModel CalendarModel instance
     */
    public function __construct(Database $db, Logger $logger, CalendarModel $calendarModel) {
        parent::__construct($db, $logger);
        
        $this->calendarModel = $calendarModel;
    }

    /**
     * Returns a CalendarBuilder instance for a given month and a year
     * 
     * @param int $month Month
     * @param int $year Year
     * @param array $tagsAllowed Array of allowed event tags
     * @return CalendarBuilder
     */
    public function getCalendarForDate(int $month, int $year, array $tagsAllowed = []) {
        $cb = CalendarBuilder::getTemporaryObject();

        $cb ->setMonth($month)
            ->setYear($year)
            ->allowEventTags($tagsAllowed);

        return $cb;
    }
}

?>