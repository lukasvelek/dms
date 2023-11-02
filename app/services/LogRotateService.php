<?php

namespace DMS\Services;

use DMS\Core\FileManager;
use DMS\Core\Logger\Logger;

class LogRotateService extends AService {
    private array $cfg;

    public function __construct(Logger $logger, array $cfg) {
        parent::__construct('LogRotateService', 'Deletes old log files', $logger);

        $this->cfg = $cfg;
    }

    public function run() {
        $fm = FileManager::getTemporaryObject();

        $this->startService();

        $files = [];
        $fm->readFilesInFolder($this->cfg['log_dir'], $files);

        $toDelete = [];
        foreach($files as $f) {
            $filename = explode('/', $f)[1];
            $filename = explode('.', $filename)[0];
            $date = explode('_', $filename)[1];

            $days = 2;

            $maxOldDate = time() - (60 * 60 * 24 * $days);

            if(strtotime($date) < $maxOldDate) {
                $toDelete[] = $f;
            }
        }

        $this->log('Found ' . count($toDelete) . ' log files to delete', __METHOD__);

        foreach($toDelete as $td) {
            unlink($td);
        }

        $this->stopService();
    }
}

?>