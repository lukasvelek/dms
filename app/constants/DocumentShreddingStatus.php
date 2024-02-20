<?php

namespace DMS\Constants;

/**
 * Document shredding status constants
 * 
 * @author Lukas Velek
 */
class DocumentShreddingStatus {
    public const SHREDDED = 1;
    public const IN_APPROVAL = 2;
    public const APPROVED = 3;
    public const DECLINED = 4;
    public const NO_STATUS = 5;

    public static $texts = array(
        self::SHREDDED => 'Shredded',
        self::IN_APPROVAL => 'In approval',
        self::APPROVED => 'Approved',
        self::DECLINED => 'Declined',
        self::NO_STATUS => '-'
    );
}

?>