<?php

namespace DMS\UI\CalendarBuilder;

use DMS\Constants\CacheCategories;
use DMS\Core\CacheManager;
use DMS\Core\ServiceManager;
use DMS\Entities\CalendarEventEntity;
use DMS\UI\IBuildable;
use DMS\UI\TableBuilder\TableBuilder;

class CalendarBuilder {
    private int $month;
    private int $year;

    private array $events;
    private array $allowedEventTags;

    public function __construct() {
        $this->month = 1;
        $this->year = 1970;
        $this->events = [];
        $this->allowedEventTags = [];
    }

    public function allowEventTags(array $tags) {
        $this->allowedEventTags = array_merge($this->allowedEventTags, $tags);
        return $this;
    }

    public function setMonth(int $month) {
        $this->month = $month;
        return $this;
    }

    public function getMonth() {
        return $this->month;
    }

    public function setYear(int $year) {
        $this->year = $year;
        return $this;
    }

    public function getYear() {
        return $this->year;
    }

    public function addEventObjects(array $events) {
        $this->events = array_merge($this->events, $events);
        return $this;
    }

    public function getEventsForDate(int $day, int $month, int $year) {
        $temp = [];

        foreach($this->events as $event) {
            if($event->getDate('Y-m-d') == date('Y-m-d', strtotime($year . '-' . $month . '-' . $day))) {
                $temp[] = $event;
            }
        }

        return $temp;
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
        
        $backLink = $createLink($baseCalendarHandler, '&larr;', $this->getMonth() - 1, $this->getYear());
        $forwardLink = $createLink($baseCalendarHandler, '&rarr;', $this->getMonth() + 1, $this->getYear());
        $currentLink = $createLink($baseCalendarHandler, date('F Y'), date('m'), date('Y'));
        
        if($this->getMonth() == 1) {
            $backLink = $createLink($baseCalendarHandler, '&larr;', 12, $this->getYear() - 1);
        }
        if($this->getMonth() == 12) {
            $forwardLink = $createLink($baseCalendarHandler, '&rarr;s', 1, $this->getYear() + 1);
        }

        $controller = $backLink . '&nbsp;&nbsp;' . $currentLink . '&nbsp;&nbsp;' . $forwardLink;

        return $controller;
    }

    public function build() {
        $tb = TableBuilder::getTemporaryObject();

        $monthAsWord = date('F', strtotime($this->year . '-' . $this->month . '-01'));

        $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText($monthAsWord . ' ' . $this->year)->setColspan('7')->setBold()));

        $dayNameRow = $tb->createRow();
        foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $d) {
            $dayNameRow->addCol($tb->createCol()->setText($d)->setBold());
        }
        $tb->addRow($dayNameRow);

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $this->month, $this->year);
        $weeks = $daysInMonth / 7;
        $firstDayInMonth = $this->getWeekdayNumberByWeekdayName(date('l', strtotime($this->year . '-' . $this->month . '-01')), false);

        $day = 1;
        $daye = 1;
        $realDay = 1;
        $realDayEvents = 1;
        $isDate = true;
        for($i = 0; $i < ($weeks * 2); $i++) {
            $row = $tb->createRow();

            if($isDate === TRUE) {
                for($j = 0; $j < 7; $j++) {
                    $col = $tb->createCol();
                    $col->setId('calendar-table-td-date');
    
                    if($day <= ($daysInMonth - 1 + $firstDayInMonth) && $day >= $firstDayInMonth) {
                        $col->setText($realDay);
                        $realDay++;
                    } else {
                        $col->setText('');
                    }
    
                    if(($realDay - 1) == date('d') && $this->month == (int)date('m') && $this->year == (int)date('Y')) {
                        $col->setBold();
                    }
    
                    $row->addCol($col);
                    $day++;
                }

                $isDate = false;
            } else {
                for($j = 0; $j < 7; $j++) {
                    if($daye > ($daysInMonth - 1 + $firstDayInMonth)) {
                        continue;
                    }
                    
                    $col = $tb->createCol();
                    $col->setId('calendar-table-td-events');

                    $text = '';
    
                    if($daye <= ($daysInMonth - 1 + $firstDayInMonth) && $daye >= $firstDayInMonth) {
                        $events = $this->getEventsForDate($realDayEvents, $this->month, $this->year);

                        foreach($events as $event) {
                            $color = $event->getColor();
                            $cec = new CalendarEventColors();
                            $fgColor = $cec->getColor($color);
                            $bgColor = $cec->getBackgroundColorByForegroundColorKey($color);
                            $text .= '<div id="calendar-table-td-single-event" style="color: ' . $fgColor . '; background-color: ' . $bgColor . '; padding: 2px; border-radius: 4px">';
                            $text .= $event->build();
                            $text .= '</div>';
                        }

                        $realDayEvents++;
                    }

                    $col->setText($text);
                    $row->addCol($col);
                    $daye++;
                }

                $isDate = true;
            }

            $tb->addRow($row);
        }

        return $tb->build();
    }

    private function getWeekdayNumberByWeekdayName(string $name, bool $startAtZero) {
        $num = 0;

        switch($name) {
            case 'Monday': $num = 0; break;
            case 'Tuesday': $num = 1; break;
            case 'Wednesday': $num = 2; break;
            case 'Thursday': $num = 3; break;
            case 'Friday': $num = 4; break;
            case 'Saturday': $num = 5; break;
            case 'Sunday': $num = 6; break;
        }

        if($startAtZero === FALSE) {
            return $num + 1;
        }

        return $num;
    }

    public static function getTemporaryObject() {
        return new self();
    }
}

?>