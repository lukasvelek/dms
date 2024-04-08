<?php

namespace DMS\Core\Logger;

use DMS\Core\AppConfiguration;
use DMS\Core\FileManager;
use QueryBuilder\ILoggerCallable;

/**
 * Logger class that allows logging
 * 
 * @author Lukas Velek
 */
class Logger implements ILoggerCallable {
    private FileManager $fileManager;

    private string $type;

    /**
     * Class constructor
     * 
     * @param FileManager $fm FileManager instance
     */
    public function __construct(FileManager $fm) {
        $this->fileManager = $fm;
        $this->type = 'default';
    }

    public function setType(string $type) {
        $this->type = $type;
    }

    /**
     * Methods logs how long a given function takes to process and returns its value.
     * 
     * @param callable $func Callback function
     * @param null|string $originMethod Original calling method (for logging purpose)
     * @return mixed Callback function result
     */
    public function logFunction(callable $func, ?string $originMethod = null, array $params = []) {
        $sw = self::getStopwatch();

        $sw->startStopwatch();
        if(!empty($params)) {
            $result = $func(...$params);
        } else {
            $result = $func();
        }
        $sw->stopStopwatch();
        
        $diff = $sw->calculate();

        $this->logTime($diff, $originMethod);

        return $result;
    }

    /**
     * Logs stopwatch time
     * 
     * @param string $time Measured time
     * @param null|string $method Calling method name or null
     * @return bool True if log saving was successful or false if not
     */
    public function logTime(string $time, ?string $method = null) {
        return $this->log($time, LogCategoryEnum::STOPWATCH, $method);
    }

    /**
     * Logs error
     * 
     * @param string $text Log text
     * @param null|string $method Calling method name or null
     * @return bool True if log saving was successful or false if not
     */
    public function error(string $text, ?string $method = null) {
        return $this->log($text, LogCategoryEnum::ERROR, $method);
    }

    /**
     * Logs information
     * 
     * @param string $text Log text
     * @param null|string $method Calling method name or null
     * @return bool True if log saving was successful or false if not
     */
    public function info(string $text, ?string $method = null) {
        return $this->log($text, LogCategoryEnum::INFO, $method);
    }

    /**
     * Logs warning
     * 
     * @param string $text Log text
     * @param null|string $method Calling method name or null
     * @return bool True if log saving was successful or false if not
     */
    public function warn(string $text, ?string $method = null) {
        return $this->log($text, LogCategoryEnum::WARN, $method);
    }

    /**
     * Logs a message with a given type
     * 
     * @param string $text Log text
     * @param string $category Log category
     * @param null|string $method Calling method name or null
     * @param null|string $filename Filename or null
     * @return bool True if log saving was successful or false if not
     */
    public function log(string $text, string $category, ?string $method = null, ?string $filename = null) {
        if(!is_null($method)) {
            $text = $category . ': ' . $method . '(): ' . $text;
        } else {
            $text = $category . ': ' . $text;
        }

        $text = '[' . date('Y-m-d H:i:s') . '] ' . $text . "\r\n";

        $result = true;

        switch($category) {
            case LogCategoryEnum::INFO:
                if(AppConfiguration::getLogLevel() == 3) {
                    $result = $this->saveLogEntry($filename, $text);
                }

                break;

            case LogCategoryEnum::WARN:
                if(AppConfiguration::getLogLevel() >= 2) {
                    $result = $this->saveLogEntry($filename, $text);
                }

                break;

            case LogCategoryEnum::ERROR:
                if(AppConfiguration::getLogLevel() >= 1) {
                    $result = $this->saveLogEntry($filename, $text);
                }

                break;

            case LogCategoryEnum::SQL:
                if(AppConfiguration::getSqlLogLevel() == 1) {
                    $result = $this->saveLogEntry($filename, $text);
                }

                break;

            case LogCategoryEnum::STOPWATCH:
                if(AppConfiguration::getLogStopwatch() == 1) {
                    $result = $this->saveLogEntry($filename, $text);
                }

                break;
        }

        return $result;
    }

    /**
     * Logs SQL query
     * 
     * @param string $sql SQL query
     * @param string $method Calling metod name
     * @return bool True if log saving was successful or false if not
     */
    public function sql(string $sql, string $method) {
        $text = $method . '(): ' . $sql;

        return $this->log($text, LogCategoryEnum::SQL);
    }

    /**
     * Saves log entry to a log file
     * 
     * @param null|string $filename Filename or null
     * @param string $text Log entry text
     * @return bool True if file was saved successfully or false if not
     */
    private function saveLogEntry(?string $filename, string $text) {
        if(is_null($filename)) {
            if($this->type == 'service') {
                $filename = 'service_log_' . date('Y-m-d') . '.log';
            } else {
                $filename = 'log_' . date('Y-m-d') . '.log';
            }
        }

        return $this->fileManager->writeLog($filename, $text);
    }

    /**
     * Returns a newly created stopwatch instance
     * 
     * @return LoggerStopwatch LoggerStopwatch instance
     */
    public static function getStopwatch() {
        return LoggerStopwatch::getTemporaryObject();
    }
}

?>