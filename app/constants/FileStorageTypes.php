<?php

namespace DMS\Constants;

class FileStorageTypes {
    public const FILES = 'files';
    public const DOCUMENT_REPORTS = 'document_reports';

    public static $texts = array(
        self::FILES => 'File storage',
        self::DOCUMENT_REPORTS => 'Document report storage'
    );
}

?>