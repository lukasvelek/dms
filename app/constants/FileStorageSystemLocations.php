<?php

namespace DMS\Constants;

class FileStorageSystemLocations {
    public static $texts = array(
        'FS_Main' => ['path' => '\\files\\', 'absolute_path' => '/dms/files/', 'type' => FileStorageTypes::FILES],
        'DocReports_Main' => ['path' => '\\document_reports_storage\\', 'absolute_path' => '/dms/document_reports_storage/', 'type' => FileStorageTypes::DOCUMENT_REPORTS]
    );
}

?>