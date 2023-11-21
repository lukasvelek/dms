<?php

namespace DMS\Services;

/**
 * Interface IServiceRunnable is implemented by AService and thus implemented in every class that extends the AService class.
 * It contains definition of methods that must be implemented in every service, otherwise it will fail.
 * 
 * @author Lukas Velek
 * @version 1.0
 */
interface IServiceRunnable {
    /**
     * This method is called when user explicitly runs the service.
     * 
     * @return void
     */
    function run();
}

?>