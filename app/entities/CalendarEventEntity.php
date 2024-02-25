<?php

namespace DMS\Entities;

use DMS\UI\CalendarBuilder\CalendarEventColors;
use DMS\UI\IBuildable;

class CalendarEventEntity extends AEntity implements IBuildable {
    private string $title;
    private string $color;
    private ?string $tag;
    private string $date;
    private string $time;

    public function __construct(int $id, string $dateCreated, string $title, string $color, ?string $tag, string $date, string $time) {
        parent::__construct($id, $dateCreated, null);

        $this->title = $title;
        $this->color = $color;
        $this->tag = $tag;
        $this->date = $date;
        $this->time = $time;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getColor() {
        return $this->color;
    }

    public function getTag() {
        return $this->tag;
    }

    public function getDate(string $format = 'Y-m-d') {
        return date($format, strtotime($this->date));
    }

    public function getTime() {
        return $this->time;
    }

    public function build() {
        $cec = new CalendarEventColors();
        $color = $cec->getColor($this->color);
        $bgColor = $cec->getBackgroundColorByForegroundColorKey($this->color);

        $code = '';
        //$code .= '<span style="color: ' . $color . '; background-color: ' . $bgColor . '; padding: 2px; border-radius: 4px">';
        $code .= $this->title;
        //$code .= '</span>';

        return $code;
    }
}

?>