<?php

namespace DMS\Core;

use DMS\Core\Logger\Logger;
use DMS\Services\LogRotateService;

class ServiceManager {
    private Logger $logger;

    public array $services;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
        
        $this->loadServices();
    }

    private function loadServices() {
        global $app;

        $this->services['Log Rotate'] = new LogRotateService($this->logger);
    }
}

?>