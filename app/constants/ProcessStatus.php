<?php

namespace DMS\Constants;

class ProcessStatus {
    public const IN_PROGRESS = 1;
    public const FINISHED = 2;

    public static $texts = array(
        self::IN_PROGRESS => 'In progress',
        self::FINISHED => 'Finished'
    );
}

?>