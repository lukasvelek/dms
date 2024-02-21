<?php

namespace DMS\Constants;

/**
 * Document report status constants
 * 
 * @author Lukas Velek
 */
class DocumentReportStatus {
    public const NEW = 1;
    public const IN_PROGRESS = 2;
    public const FINISHED = 3;
    public const ERROR = 4;

    public static $texts = array(
        self::NEW => 'New',
        self::IN_PROGRESS => 'In progress',
        self::FINISHED => 'Finished',
        self::ERROR => 'Error'
    );
}

?>