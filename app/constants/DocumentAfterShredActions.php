<?php

namespace DMS\Constants;

/**
 * Document after shredding action constants
 * 
 * @author Lukas Velek
 */
class DocumentAfterShredActions {
    public const DELETE = 'delete';
    public const SHOW_AS_SHREDDED = 'showAsShredded';
    public const TOTAL_DELETE = 'totalDelete';

    public static $texts = array(
        self::SHOW_AS_SHREDDED => 'Show as shredded',
        self::DELETE => 'Delete (keep in the database)',
        self::TOTAL_DELETE => 'Total delete (delete from the application entirely)'
    );
}

?>