<?php

namespace DMS\Core;

use DMS\Core\DB\Database;

class Datetime {
    private int $timestamp;

    public function __construct() {
        $this->timestamp = 0;
    }

    public function setTimestamp(int $timestamp) {
        $this->timestamp = $timestamp;
    }

    public function addSeconds(int $seconds) {
        $this->timestamp += $seconds;
    }

    public function getDate(string $format = Database::DB_DATE_FORMAT) {
        return date($format, $this->timestamp);
    }

    public function convertDaysToSeconds(int $days) {
        return $this->convertHoursToSeconds($days * 24);
    }

    public function convertHoursToSeconds(int $hours) {
        return $this->convertMinutesToSeconds($hours * 60);
    }

    public function convertMinutesToSeconds(int $minutes) {
        return $minutes * 60;
    }
}

?>