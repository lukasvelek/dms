<?php

namespace DMS\Modules\UserModule;

use DMS\Modules\APresenter;
use DMS\UI\CalendarBuilder\CalendarEvent;

class Calendar extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('Calendar');

        $this->getActionNamesFromClass($this);
    }

    protected function showEvents() {
        global $app;

        $template = $this->loadTemplate(__DIR__ . '/templates/calendar/general.html');

        $month = date('m');
        $year = date('Y');
        $tag = null;

        if(isset($_GET['month'])) {
            $month = $this->get('month');
        }
        if(isset($_GET['year'])) {
            $year = $this->get('year');
        }
        if(isset($_GET['tag'])) {
            $tag = $this->get('tag');
        }

        $events = $app->calendarModel->getAllEventsForMonthAndYear($month, $year);

        $calendar = $app->calendarComponent->getCalendarForDate($month, $year, [$tag]);
        $controller = $calendar->getController('UserModule:Calendar:showEvents');
        $calendar->addEventObjects($events);

        $data = [
            '$CALENDAR$' => $calendar->build(),
            '$CONTROLLER$' => $controller
        ];

        $this->fill($data, $template);

        return $template;
    }
}

?>