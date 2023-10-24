<?php

namespace DMS\Constants;

class MetadataInputType {
    public const SELECT = 'select';
    public const TEXT = 'text';
    public const NUMBER = 'number';
    public const BOOLEAN = 'boolean';
    public const DATE = 'date';
    public const DATETIME = 'datetime';

    public static $texts = array(
        self::SELECT => 'Select',
        self::TEXT => 'Text',
        self::NUMBER => 'Number',
        self::BOOLEAN => 'Boolean',
        self::DATE => 'Date',
        self::DATETIME => 'Datetime'
    );
}

?>