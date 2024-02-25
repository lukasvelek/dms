<?php

namespace DMS\UI\CalendarBuilder;

use DMS\Entities\CalendarEventEntity;
use DMS\UI\IBuildable;

class CalendarBuilder {
    private Calendar $calendar;

    public function __construct() {
        $this->calendar = new Calendar();
    }

    public function allowEventTags(array $tags) {
        $this->calendar->allowEventTags($tags);
        return $this;
    }

    public function setMonth(int $month) {
        $this->calendar->setMonth($month);
        return $this;
    }

    public function setYear(int $year) {
        $this->calendar->setYear($year);
        return $this;
    }

    public function addEventObject(CalendarEventEntity $event) {
        $this->calendar->addEvent($event);
        return $this;
    }

    public function addEventObjects(array $events) {
        $this->calendar->addEvents($events);
        return $this;
    }

    public function getController(string $baseCalendarHandler) {
        $createLink = function(string $handler, string $text, string|int $month, string|int $year, string $tag = '') {
            $m = date('m', strtotime($year . '-' . $month . '-01'));
            $y = date('Y', strtotime($year . '-' . $month . '-01'));

            $link = '<a class="general-link" href="?page=' . $handler . '&year=' . $y . '&month=' . $m;

            if($tag != '') {
                $link .= '&tag=' . $tag;
            }

            $link .= '">' . $text . '</a>';

            return $link;
        };

        $controller = '';
        
        $backLink = $createLink($baseCalendarHandler, '&larr;', $this->calendar->getMonth() - 1, $this->calendar->getYear());
        $forwardLink = $createLink($baseCalendarHandler, '&rarr;', $this->calendar->getMonth() + 1, $this->calendar->getYear());
        $currentLink = $createLink($baseCalendarHandler, date('F Y'), date('m'), date('Y'));
        
        if($this->calendar->getMonth() == 1) {
            $backLink = $createLink($baseCalendarHandler, '&larr;', 12, $this->calendar->getYear() - 1);
        }
        if($this->calendar->getMonth() == 12) {
            $forwardLink = $createLink($baseCalendarHandler, '&rarr;s', 1, $this->calendar->getYear() + 1);
        }

        $controller = $backLink . '&nbsp;&nbsp;' . $currentLink . '&nbsp;&nbsp;' . $forwardLink;

        return $controller;
    }

    public function build() {
        return $this->calendar->build();
    }

    public static function getTemporaryObject() {
        return new self();
    }
}

?>