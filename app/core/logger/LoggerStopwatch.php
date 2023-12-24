<?php

namespace DMS\Core\Logger;

class LoggerStopwatch {
    private const DISPLAY_TIME_FROM_TO = false;

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
        $this->stopwatchStart = hrtime(true);

        $this->syncWithSession();
    }

    public function stopStopwatch() {
        $this->stopwatchEnd = hrtime(true);

        $this->syncWithSession();
    }

    public function calculate() {
        $difference = $this->stopwatchEnd - $this->stopwatchStart; // in nanoseconds

        $difference = round(($difference / 1e+6));

        $text = 'Time taken: ' . $difference . 'ms';

        if(self::DISPLAY_TIME_FROM_TO) {
            $text .= ' (' . $this->stopwatchEnd . ' - ' . $this->stopwatchStart . ')';
        }

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