<?php

namespace DMS\Core\Logger;

use DMS\Core\AppConfiguration;
use DMS\Core\FileManager;
use QueryBuilder\ILoggerCallable;

class Logger implements ILoggerCallable {
    private FileManager $fileManager;

    public function __construct(FileManager $fm) {
        $this->fileManager = $fm;
    }

    public function logFunction(callable $func, ?string $originMethod = null) {
        $sw = self::getStopwatch();

        $sw->startStopwatch();
        $func();
        $sw->stopStopwatch();
        
        $diff = $sw->calculate();

        $this->logTime($diff, $originMethod);
    }

    public function logTime(string $time, ?string $method = null) {
        return $this->log($time, LogCategoryEnum::STOPWATCH, $method);
    }

    public function error(string $text, ?string $method = null) {
        return $this->log($text, LogCategoryEnum::ERROR, $method);
    }

    public function info(string $text, ?string $method = null) {
        return $this->log($text, LogCategoryEnum::INFO, $method);
    }

    public function warn(string $text, ?string $method = null) {
        return $this->log($text, LogCategoryEnum::WARN, $method);
    }

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

    public function sql(string $sql, string $method) {
        $text = $method . '(): ' . $sql;

        return $this->log($text, LogCategoryEnum::SQL);
    }

    private function saveLogEntry(?string $filename, string $text) {
        if(is_null($filename)) {
            $filename = 'log_' . date('Y-m-d') . '.log';
        }

        return $this->fileManager->writeLog($filename, $text);
    }

    public static function getStopwatch() {
        return LoggerStopwatch::getTemporaryObject();
    }
}

?>