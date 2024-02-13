<?php

namespace DMS\Services;

use DMS\Core\AppConfiguration;
use DMS\Core\CacheManager;
use DMS\Core\FileManager;
use DMS\Core\Logger\Logger;
use DMS\Models\ServiceModel;

class CacheRotateService extends AService {
    public function __construct(Logger $logger, ServiceModel $serviceModel, CacheManager $cm) {
        parent::__construct('CacheRotateService', 'Deletes old cache files', $logger, $serviceModel, $cm);

        $this->loadCfg();
    }

    public function run() {
        $fm = FileManager::getTemporaryObject();

        $this->startService();

        $files = [];
        $fm->readFilesInFolder(AppConfiguration::getCacheDir(), $files);

        $this->log('Found ' . count($files) . ' cache files to delete', __METHOD__);

        foreach($files as $f) {
            unlink($f);
        }

        $dirs = [];
        $fm->readFoldersInFolder(AppConfiguration::getCacheDir(), $dirs);
        
        for($i = (count($dirs) - 1); $i >= 0; $i--) {
            rmdir($dirs[$i]);
        }

        $this->stopService();
    }
}

?>