<?php

namespace DMS\Constants;

class BulkActionRights {
    public const DELETE_DOCUMENTS = 'delete_documents';
    public const APPROVE_ARCHIVATION = 'approve_archivation';
    public const DECLINE_ARCHIVATION = 'decline_archivation';

    public static $all = array(
        self::DELETE_DOCUMENTS,
        self::APPROVE_ARCHIVATION,
        self::DECLINE_ARCHIVATION
    );
}

?>