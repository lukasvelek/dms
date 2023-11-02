<?php

namespace DMS\Core;

use DMS\Core\Logger\Logger;
use DMS\Services\CacheRotateService;
use DMS\Services\LogRotateService;

class ServiceManager {
    private Logger $logger;
    private array $cfg;

    public array $services;

    public function __construct(Logger $logger, array $cfg) {
        $this->logger = $logger;
        $this->cfg = $cfg;
        
        $this->loadServices();
    }

    private function loadServices() {
        $this->services['Log Rotate'] = new LogRotateService($this->logger, $this->cfg);
        $this->services['Cache Rotate'] = new CacheRotateService($this->logger, $this->cfg);
    }
}

?>