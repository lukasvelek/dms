<?php

namespace DMS\Entities;

use DMS\UI\IBuildable;

class CalendarEventEntity extends AEntity implements IBuildable {
    private string $title;
    private string $color;
    private ?string $tag;
    private string $dateFrom;
    private ?string $dateTo;
    private string $time;

    public function __construct(int $id, string $dateCreated, string $title, string $color, ?string $tag, string $dateFrom, ?string $dateTo, string $time) {
        parent::__construct($id, $dateCreated, null);

        $this->title = $title;
        $this->color = $color;
        $this->tag = $tag;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
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

    public function getDateFrom(string $format = 'Y-m-d') {
        return date($format, strtotime($this->dateFrom));
    }

    public function getDateTo(string $format = 'Y-m-d') {
        if($this->dateTo === NULL) return null;
        return date($format, strtotime($this->dateTo));
    }

    public function getTime() {
        return $this->time;
    }

    public function build() {
        return $this->title;
    }
}

?>