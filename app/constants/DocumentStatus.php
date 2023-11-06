<?php

namespace DMS\Constants;

class DocumentStatus {
    public const NEW = 1;
    public const DELETED = 2;
    public const ARCHIVATION_DECLINED = 3;
    public const ARCHIVATION_APPROVED = 4;
    public const SHREDDED = 5;
    public const ARCHIVED = 6;

    public static $texts = array(
        self::NEW => 'New',
        self::DELETED => 'Deleted',
        self::ARCHIVATION_APPROVED => 'Archivation approved',
        self::ARCHIVATION_DECLINED => 'Archivation declined',
        self::SHREDDED => 'Shredded',
        self::ARCHIVED => 'Archived'
    );
}

?>