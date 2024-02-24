<?php

namespace DMS\UI\CalendarBuilder;

use DMS\Entities\CalendarEventEntity;
use DMS\UI\IBuildable;
use DMS\UI\TableBuilder\TableBuilder;

class Calendar implements IBuildable {
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

    public function setMonth(int $month) {
        $this->month = $month;
    }

    public function getMonth() {
        return $this->month;
    }

    public function setYear(int $year) {
        $this->year = $year;
    }

    public function getYear() {
        return $this->year;
    }

    public function allowEventTag(string $tag) {
        $this->allowedEventTags[] = $tag;
    }

    public function allowEventTags(array $tags) {
        $this->allowedEventTags = array_merge($this->allowedEventTags, $tags);
    }

    public function addEvent(CalendarEventEntity $event) {
        $this->events[] = $event;
    }

    public function addEvents(array $events) {
        $this->events = array_merge($this->events, $events);
    }

    public function getEventsForDate(string $date, string $format = 'Y-m-d') {
        $temp = [];

        foreach($this->events as $event) {
            if(strtotime($event->getDate($format)) == strtotime(date($format, strtotime($date)))) {
                if((!empty($this->allowedEventTags) && in_array($event->getTag(), $this->allowedEventTags)) || empty($this->allowedEventTags)) {
                    $temp[] = $event;
                }
            }
        }

        return $temp;
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
                    $col = $tb->createCol();
                    $col->setId('calendar-table-td-event');

                    if($day <= ($daysInMonth - 1 + $firstDayInMonth) && $day >= $firstDayInMonth) {
                        $realDayEvents++;
                    }

                    $events = $this->getEventsForDate($this->year . '-' . $this->month . '-' . ($realDayEvents - 1));
    
                    $x = 0;
                    $text = '';
                    foreach($events as $event) {
                        if(($x + 1) == count($events)) {
                            $text .= $event->build();
                        } else {
                            $text .= $event->build() . '<br>';
                        }
    
                        $x++;
                    }
                    $col->setText($text);

                    $row->addCol($col);
                }
                
                $isDate = true;
            }

            $tb->addRow($row);
        }

        return $tb->build();
    }

    private function getWeekdayNumberByWeekdayName(string $name, bool $startAtZero = true) {
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
}

?>