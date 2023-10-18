<?php

namespace DMS\Core\Logger;

use DMS\Core\FileManager;
use QueryBuilder\ILoggerCallable;

class Logger implements ILoggerCallable {
    private FileManager $fileManager;

    public function __construct(FileManager $fm) {
        $this->fileManager = $fm;
    }

    public function log(string $text, string $category, ?string $method = null, ?string $filename = null) {
        if(!is_null($method)) {
            $text = $category . ': ' . $method . '(): ' . $text;
        } else {
            $text = $category . ': ' . $text;
        }

        $text = '[' . date('Y-m-d H:i:s') . '] ' . $text . "\r\n";

        if(is_null($filename)) {
            return $this->saveLogEntry(null, $text);
        } else {
            return $this->saveLogEntry($filename, $text);
        }
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