<?php

namespace DMS\Constants;

class MetadataInputType {
    public const SELECT = 'select';
    public const TEXT = 'text';
    public const NUMBER = 'number';

    public static $texts = array(
        self::SELECT => 'Select',
        self::TEXT => 'Text',
        self::NUMBER => 'Number'
    );
}

?>