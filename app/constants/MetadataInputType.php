<?php

namespace DMS\Constants;

/**
 * Metadata input type constants
 * 
 * @author Lukas Velek
 */
class MetadataInputType {
    public const SELECT = 'select';
    public const TEXT = 'text';
    public const NUMBER = 'number';
    public const BOOLEAN = 'boolean';
    public const DATE = 'date';
    public const DATETIME = 'datetime';
    public const SELECT_EXTERNAL = 'select_external';

    public static $texts = array(
        self::SELECT => 'Select',
        self::TEXT => 'Text',
        self::NUMBER => 'Number',
        self::BOOLEAN => 'Boolean',
        self::DATE => 'Date',
        self::DATETIME => 'Datetime',
        self::SELECT_EXTERNAL => 'External Select'
    );
}

?>