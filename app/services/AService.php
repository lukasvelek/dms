<?php

namespace DMS\Services;

use DMS\Core\CacheManager;
use DMS\Core\Logger\Logger;
use DMS\Models\ServiceModel;

/**
 * Abstract class that has to extend every service class.
 * It contains useful methods as well as required methods that are defined in the implemented interfaces.
 * 
 * @author Lukas Velek
 * @version 1.0
 */
abstract class AService implements IServiceRunnable {
    public string $name;
    public string $description;
    
    protected Logger $logger;
    protected ServiceModel $serviceModel;
    protected CacheManager $cm;
    protected array $scfg;

    /**
     * The AService constructor is used to define common instances and values.
     * 
     * @param string $name Service name (non-user-friendly, recommended to be same as the class name)
     * @param string $description Short description of the service
     * @param Logger $logger Logger instance
     * @param ServiceModel $serviceModel ServiceModel instance
     * @param CacheManager $cm CacheManager instance
     */
    protected function __construct(string $name, string $description, Logger $logger, ServiceModel $serviceModel, CacheManager $cm) {
        $this->name = $name;
        $this->description = $description;
        $this->logger = $logger;
        $this->serviceModel = $serviceModel;
        $this->cm = $cm;

        $this->scfg = [];
    }

    /**
     * Loads configuration from the database for current service.
     * If configuration exists then it is saved in the $scfg array. Otherwise it's an empty array.
     * 
     * @return void
     */
    protected function loadCfg() : void {
        $valsFromCache = $this->cm->loadServiceConfigForService($this->name);

        if(!is_null($valsFromCache)) {
            $this->scfg = $valsFromCache;
        } else {
            $this->scfg = $this->serviceModel->getConfigForServiceName($this->name);

            $this->cm->saveServiceConfig($this->name, $this->scfg);
        }
    }

    /**
     * Logs the start of the service
     * 
     * @return void
     */
    protected function startService() : void {
        $this->logger->info('Starting service \'' . $this->name . '\'', __METHOD__);
    }

    /**
     * Logs the end of the service
     * 
     * @return void
     */
    protected function stopService() : void {
        $this->logger->info('Stopping service \'' . $this->name . '\'', __METHOD__);
    }

    /**
     * Shortcut to log a message
     * 
     * @param string $text Log message
     * @param string $method Name of the calling method (usually used: __METHOD__)
     * @return void
     */
    protected function log(string $text, string $method) : void {
        $this->logger->info($text, $method);
    }
}

?>