<?php

namespace DMS\Services;

use DMS\Core\FileManager;
use DMS\Core\Logger\Logger;

class CacheRotateService extends AService {
    private array $cfg;

    public function __construct(Logger $logger, array $cfg) {
        parent::__construct('CacheRotateService', 'Deletes old cache files', $logger);

        $this->cfg = $cfg;
    }

    public function run() {
        $fm = FileManager::getTemporaryObject();

        $this->startService();

        $files = [];
        $fm->readFilesInFolder($this->cfg['cache_dir'], $files);

        $this->log('Found ' . count($files) . ' cache files to delete', __METHOD__);

        foreach($files as $f) {
            unlink($f);
        }

        $this->stopService();
    }
}

?>