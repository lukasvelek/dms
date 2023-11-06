<?php

namespace DMS\Core;

use DMS\Core\Logger\Logger;
use DMS\Models\ServiceModel;
use DMS\Services\CacheRotateService;
use DMS\Services\LogRotateService;

class ServiceManager {
    private Logger $logger;
    private ServiceModel $serviceModel;
    private array $cfg;

    public array $services;

    public function __construct(Logger $logger, ServiceModel $serviceModel, array $cfg) {
        $this->logger = $logger;
        $this->cfg = $cfg;
        $this->serviceModel = $serviceModel;
        
        $this->loadServices();
    }

    public function getServiceByName(string $name) {
        foreach($this->services as $k => $v) {
            if($v->name == $name) {
                return $v;
            }
        }

        return null;
    }

    private function loadServices() {
        $this->services['Log Rotate'] = new LogRotateService($this->logger, $this->serviceModel, $this->cfg);
        $this->services['Cache Rotate'] = new CacheRotateService($this->logger, $this->serviceModel, $this->cfg);
    }
}

?>