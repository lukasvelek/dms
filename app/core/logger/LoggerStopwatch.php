<?php

namespace DMS\Core\Logger;

class LoggerStopwatch {
    private string $stopwatchStart;
    private string $stopwatchEnd;

    public function __construct() {
        if(isset($_SESSION['logger_stopwatch_start'])) {
            $this->stopwatchStart = $_SESSION['logger_stopwatch_start'];
        } else {
            $this->stopwatchStart = '';
        }

        if(isset($_SESSION['logger_stopwatch_end'])) {
            $this->stopwatchEnd = $_SESSION['logger_stopwatch_end'];
        } else {
            $this->stopwatchEnd = '';
        }
    }

    public function startStopwatch() {
        $this->stopwatchStart = time();

        $this->syncWithSession();
    }

    public function stopStopwatch() {
        $this->stopwatchEnd = time();

        $this->syncWithSession();
    }

    public function calculate() {
        $difference = $this->stopwatchEnd - $this->stopwatchStart;

        $text = $difference . 's (' . $this->stopwatchEnd . ' - ' . $this->stopwatchStart . ')';

        $this->clear();

        return $text;
    }

    private function clear() {
        $this->stopwatchStart = '';
        $this->stopwatchEnd = '';

        unset($_SESSION['logger_stopwatch_start']);
        unset($_SESSION['logger_stopwatch_end']);
    }

    private function syncWithSession() {
        if($this->stopwatchStart != '') {
            $_SESSION['logger_stopwatch_start'] = $this->stopwatchStart;
        }

        if($this->stopwatchEnd != '') {
            $_SESSION['logger_stopwatch_end'] = $this->stopwatchEnd;
        }
    }

    public static function getTemporaryObject() {
        return new self();
    }
}

?>