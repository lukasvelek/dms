<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\CacheCategories;
use DMS\Core\CacheManager;
use DMS\Entities\CalendarEventEntity;
use DMS\Modules\APresenter;
use DMS\UI\LinkBuilder;

class SystemEventCalendarPresenter extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('SystemEventCalendar', 'System event calendar');

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

        foreach($app->serviceManager->services as $name => $service) {
            $scm = CacheManager::getTemporaryObject(CacheCategories::SERVICE_RUN_DATES);
            $valFromCache = $scm->loadServiceEntry($service->name);

            if($valFromCache !== NULL && !empty($valFromCache)) {
                $date = explode(' ', $valFromCache['next_run_date'])[0];
                $time = explode(' ', $valFromCache['next_run_date'])[1];
                $events[] = new CalendarEventEntity(0, date('Y-m-d'), $service->name, 'RED', 'system', $date, null, $time);
            }
        }

        $calendar = $app->calendarComponent->getCalendarForDate($month, $year, [$tag]);
        $controller = $calendar->getController('UserModule:SystemEventCalendar:showEvents');
        $calendar->addEventObjects($events);

        $data = [
            '$CALENDAR$' => $calendar->build(),
            '$CONTROLLER$' => $controller,
            '$PAGE_TITLE$' => 'System event calendar',
            '$LINKS$' => []
        ];

        $data['$LINKS$'][] = LinkBuilder::createAdvLink(['page' => 'Settings:showSystem'], '&larr;');

        $this->fill($data, $template);

        return $template;
    }
}

?>