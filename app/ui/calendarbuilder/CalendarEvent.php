<?php

namespace DMS\UI\CalendarBuilder;

use DMS\UI\IBuildable;

class CalendarEvent implements IBuildable {
    private string $title;
    private string $color;
    private ?string $tag;
    private int $day;
    private int $month;
    private int $year;
    private int $hour;
    private int $minute;

    public function __construct() {
        $this->title = '';
        $this->color = '';
        $this->tag = null;
        $this->day = 0;
        $this->month = 0;
        $this->year = 0;
        $this->hour = -1;
        $this->minute = -1;
    }

    public function getDate(string $format = 'Y-m-d') {
        return date($format, strtotime($this->year . '-' . $this->month . '-' . $this->day));
    }

    public function setDate(int $year, int $month, int $day) {
        $this->year = $year;
        $this->month = $month;
        $this->day = $day;

        return $this;
    }

    public function setTime(int $hour, int $minute) {
        $this->hour = $hour;
        $this->minute = $minute;

        return $this;
    }

    public function setTitle(string $title) {
        $this->title = $title;

        return $this;
    }

    public function setColor(string $color) {
        $this->color = $color;

        return $this;
    }

    public function setTag(?string $tag) {
        $this->tag = $tag;

        return $this;
    }

    public function getTag() {
        return $this->tag;
    }

    public function build() {
        $code = '<span style="color: ' . $this->color . '">';

        if($this->hour > -1 && $this->minute > -1) {
            $code .= '(' . date('H:i', mktime($this->hour, $this->minute)) . ') ';
        }

        $code .= $this->title;
        $code .= '</span>';

        return $code;
    }
}

?>