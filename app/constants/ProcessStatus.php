<?php

namespace DMS\Constants;

/**
 * Process status constants
 * 
 * @author Lukas Velek
 */
class ProcessStatus {
    public const IN_PROGRESS = 1;
    public const FINISHED = 2;

    public static $texts = array(
        self::IN_PROGRESS => 'In progress',
        self::FINISHED => 'Finished'
    );
}

?>