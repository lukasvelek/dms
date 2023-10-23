<?php

namespace DMS\Constants;

class DocumentRank {
    public const PUBLIC = 'public';
    public const PRIVATE = 'private';

    public static $texts = array(
        self::PUBLIC => 'Public',
        self::PRIVATE => 'Private'
    );
}

?>