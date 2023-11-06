<?php

namespace DMS\Services;

use DMS\Core\FileManager;
use DMS\Core\Logger\Logger;
use DMS\Models\ServiceModel;

class CacheRotateService extends AService {
    private array $cfg;

    public function __construct(Logger $logger, ServiceModel $serviceModel, array $cfg) {
        parent::__construct('CacheRotateService', 'Deletes old cache files', $logger, $serviceModel);

        $this->cfg = $cfg;

        $this->loadCfg();
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