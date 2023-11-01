<?php

namespace DMS\Services;

use DMS\Core\FileManager;
use DMS\Core\Logger\Logger;

class LogRotateService extends AService {
    public function __construct(Logger $logger) {
        parent::__construct('LogRotateService', 'Deletes old logs', $logger);
    }

    public function run() {
        $fm = FileManager::getTemporaryObject();

        $this->logger->info('Starting service \'' . $this->name . '\'', __METHOD__);

        $files = [];
        $fm->readFilesInFolder('logs', $files);

        foreach($files as $f) {
            $this->logger->info($f, __METHOD__);
        }

        $this->logger->info('Stopping service \'' . $this->name . '\'', __METHOD__);
    }
}

?>