<?php

namespace DMS\Core\Logger;

use DMS\Core\CypherManager;

/**
 * Logger stopwatch addon. It is used to measure time taken between operations.
 * 
 * @author Lukas Velek
 * @version 1.1
 */
class LoggerStopwatch {
    /**
     * Constant defining if the timestamp from and timestamp to should be displayed
     */
    private const DISPLAY_TIME_FROM_TO = false;

    /**
     * Constant defining if the current stopwatch hash should be displayed
     */
    private const DISPLAY_HASH = false;

    /**
     * Stopwatch start timestamp
     */
    private string $stopwatchStart;

    /**
     * Stopwatch stop timestamp
     */
    private string $stopwatchEnd;

    /**
     * Special hash used to distinct between several stopwatches
     */
    private string $hash;

    /**
     * Stopwatch constructor. It either gets values from the session or sets them to empty strings.
     */
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

        $this->hash = $this->createInnerHash();
    }

    /**
     * Method used to start the stopwatch. It gets the current timestamp and synchronizes it with the session values.
     */
    public function startStopwatch() {
        $this->stopwatchStart = hrtime(true);

        $this->syncWithSession();
    }

    /**
     * Method used to stop the stopwatch. It gets the current timestamp and synchronizes it with the session values.
     */
    public function stopStopwatch() {
        $this->stopwatchEnd = hrtime(true);

        $this->syncWithSession();
    }

    /**
     * Calculates the difference between the two takings. It also created a string that is displayed in the log.
     * 
     * @return string $text Text with measurement values
     */
    public function calculate() {
        $difference = $this->stopwatchEnd - $this->stopwatchStart; // in nanoseconds

        $difference = round(($difference / 1e+6));

        $text = 'Time taken: ' . $difference . 'ms';

        if($difference >= 1000) {
            $text .= ' (' . ($difference / 1000) . ' s)';
        }

        if(self::DISPLAY_TIME_FROM_TO) {
            $text .= ' (' . $this->stopwatchEnd . ' - ' . $this->stopwatchStart . ')';
        }

        if(self::DISPLAY_HASH) {
            $text .= ' [' . $this->hash . ']';
        }

        $this->clear();

        return $text;
    }

    /**
     * Clears class values and session values
     */
    private function clear() {
        $this->stopwatchStart = '';
        $this->stopwatchEnd = '';

        unset($_SESSION['logger_stopwatch_' . $this->hash . '_start']);
        unset($_SESSION['logger_stopwatch_' . $this->hash . '_end']);
    }

    /**
     * Synchronizes the local values with session values
     */
    private function syncWithSession() {
        if($this->stopwatchStart != '') {
            $_SESSION['logger_stopwatch_' . $this->hash . '_start'] = $this->stopwatchStart;
        }

        if($this->stopwatchEnd != '') {
            $_SESSION['logger_stopwatch_' . $this->hash . '_end'] = $this->stopwatchEnd;
        }
    }

    private function createInnerHash() {
        return CypherManager::createCypher(64);
    }

    /**
     * Returns temporary object
     */
    public static function getTemporaryObject() {
        return new self();
    }
}

?>