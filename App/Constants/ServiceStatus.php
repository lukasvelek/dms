<?php

namespace DMS\Constants;

/**
 * Service statuses
 * 
 * @author Lukas Velek
 */
class ServiceStatus {
    public const STOPPED = 0;
    public const RUNNING = 1;

    public static $texts = [
        self::STOPPED => 'Stopped',
        self::RUNNING => 'Running'
    ];
}

?>