<?php

namespace DMS\Constants;

class DocumentAfterShredActions {
    public const DELETE = 'delete';
    public const SHOW_AS_SHREDDED = 'showAsShredded';

    public static $texts = array(
        self::DELETE => 'Delete',
        self::SHOW_AS_SHREDDED => 'Show as shredded'
    );
}

?>