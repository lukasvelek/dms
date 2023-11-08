<?php

namespace DMS\Services;

use DMS\Constants\CacheCategories;
use DMS\Core\CacheManager;
use DMS\Core\Logger\Logger;
use DMS\Models\ServiceModel;

abstract class AService implements IServiceRunnable {
    public string $name;
    public string $description;
    
    protected Logger $logger;
    protected ServiceModel $serviceModel;
    protected CacheManager $cm;

    protected array $scfg;

    protected function __construct(string $name, string $description, Logger $logger, ServiceModel $serviceModel, CacheManager $cm) {
        $this->name = $name;
        $this->description = $description;
        $this->logger = $logger;
        $this->serviceModel = $serviceModel;
        $this->cm = $cm;
    }

    protected function loadCfg() {
        $valsFromCache = $this->cm->loadServiceConfigForService($this->name);

        if(!is_null($valsFromCache)) {
            $this->scfg = $valsFromCache;
        } else {
            $this->scfg = $this->serviceModel->getConfigForServiceName($this->name);

            $this->cm->saveServiceConfig($this->name, $this->scfg);
        }
    }

    protected function startService() {
        $this->logger->info('Starting service \'' . $this->name . '\'', __METHOD__);
    }

    protected function stopService() {
        $this->logger->info('Stopping service \'' . $this->name . '\'', __METHOD__);
    }

    protected function log(string $text, string $method) {
        $this->logger->info($text, $method);
    }
}

?>