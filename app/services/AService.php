<?php

namespace DMS\Services;

use DMS\Core\Logger\Logger;
use DMS\Models\ServiceModel;

abstract class AService implements IServiceRunnable {
    public string $name;
    public string $description;
    
    protected Logger $logger;
    protected ServiceModel $serviceModel;

    protected array $scfg;

    protected function __construct(string $name, string $description, Logger $logger, ServiceModel $serviceModel) {
        $this->name = $name;
        $this->description = $description;
        $this->logger = $logger;
        $this->serviceModel = $serviceModel;
    }

    protected function loadCfg() {
        $this->scfg = $this->serviceModel->getConfigForServiceName($this->name);
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