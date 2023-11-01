<?php

namespace DMS\Services;

use DMS\Core\Logger\Logger;

abstract class AService implements IServiceRunnable {
    public string $name;
    public string $description;
    
    protected Logger $logger;

    protected function __construct(string $name, string $description, Logger $logger) {
        $this->name = $name;
        $this->description = $description;
        $this->logger = $logger;
    }
}

?>