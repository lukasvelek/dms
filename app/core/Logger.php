<?php

namespace App\Core;

use App\Core\Managers\FileManager;
use App\Core\VarTypes\DateTime;
use Exception;
use QueryBuilder\ILoggerCallable;

class Logger implements ILoggerCallable {
    public const INFO = 'INFO';
    public const WARNING = 'WARNING';
    public const ERROR = 'ERROR';
    public const SQL = 'SQL';
    public const SERVICE = 'SERVICE';
    public const EXCEPTION = 'EXCEPTION';

    private array $cfg;
    private FileManager $fileManager;

    public function __construct(array $cfg, FileManager $fileManager) {
        $this->cfg = $cfg;
        $this->fileManager = $fileManager;
    }

    public function info(string $text, string $method) {
        if(!$this->checkCanLog(self::INFO)) {
            return false;
        }

        return $this->log($text, $method, self::INFO);
    }

    public function warning(string $text, string $method) {
        if(!$this->checkCanLog(self::WARNING)) {
            return false;
        }

        return $this->log($text, $method, self::WARNING);
    }

    public function error(string $text, string $method) {
        if(!$this->checkCanLog(self::ERROR)) {
            return false;
        }

        return $this->log($text, $method, self::ERROR);
    }

    public function sql(string $sql, string $method, ?float $msTaken) {
        if(!$this->checkCanLogSql()) {
            return false;
        }

        return $this->log($sql, $method, self::SQL);
    }

    public function exception(Exception $e, string $method) {
        return $this->log($e->getMessage(), $method, self::EXCEPTION);
    }

    private function log(string $text, string $method, string $type) {
        $text = $this->createLogEntry($text, $method, $type);

        return $this->writeToLog($text, $type);
    }

    private function writeToLog(string $text, string $type) {
        $path = $this->cfg['APP_REAL_DIR'] . $this->cfg['LOG_DIR'] . $this->generateFilename($type);

        return $this->fileManager->saveFile($path, $text);
    }

    private function generateFilename(string $type) {
        $now = new DateTime();
        $now->format('Y-m-d');

        $filename = 'log_' . $now->getResult() . '.log';

        if($type == self::SQL || $type == self::SERVICE) {
            $filename = strtolower($type) . '_' . $filename;
        }

        return $filename;
    }

    private function createLogEntry(string $text, string $method, string $type) {
        $now = new DateTime();

        $entry = '[' . $now->getResult() . '] [' . $type . '] ' . $method . '(): ' . $text;

        return $entry;
    }

    private function checkCanLog(string $type) {
        $logLevel = $this->cfg['LOG_LEVEL'];

        if($logLevel == 3) {
            return true;
        } else if($logLevel == 2) {
            if($type == self::WARNING || $type == self::ERROR) {
                return true;
            }
        } else if($logLevel == 1) {
            if($type == self::ERROR) {
                return true;
            }
        }

        return false;
    }

    private function checkCanLogSql() {
        $sqlLogLevel = $this->cfg['SQL_LOG_LEVEL'];

        if($sqlLogLevel > 0) {
            return true;
        }

        return false;
    }

    private function checkCanLogService() {
        $serviceLogLevel = $this->cfg['SERVICE_LOG_LEVEL'];

        if($serviceLogLevel > 0) {
            return true;
        }

        return false;
    }
}

?>