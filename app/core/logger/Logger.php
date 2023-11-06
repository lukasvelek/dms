<?php

namespace DMS\Core\Logger;

use DMS\Core\FileManager;
use QueryBuilder\ILoggerCallable;

class Logger implements ILoggerCallable {
    private FileManager $fileManager;
    private array $cfg;

    public function __construct(FileManager $fm, array $cfg) {
        $this->fileManager = $fm;
        $this->cfg = $cfg;
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

        //$result = $this->saveLogEntry($filename, $text);

        /*if($this->cfg['log_level'] == '3') {
            $result = $this->saveLogEntry($filename, $text);
        } else if($this->cfg[''])*/

        switch($category) {
            case LogCategoryEnum::INFO:
                if($this->cfg['log_level'] == 3) {
                    $result = $this->saveLogEntry($filename, $text);
                }

                break;

            case LogCategoryEnum::WARN:
                if($this->cfg['log_level'] >= 2) {
                    $result = $this->saveLogEntry($filename, $text);
                }

                break;

            case LogCategoryEnum::ERROR:
                if($this->cfg['log_level'] >= 1) {
                    $result = $this->saveLogEntry($filename, $text);
                }

                break;

            case LogCategoryEnum::SQL:
                if($this->cfg['sql_log_level'] == 1) {
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
}

?>