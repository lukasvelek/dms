<?php

namespace DMS\Constants;

class BulkActionRights {
    public const DELETE_DOCUMENTS = 'delete_documents';
    public const APPROVE_ARCHIVATION = 'approve_archivation';
    public const DECLINE_ARCHIVATION = 'decline_archivation';
    public const ARCHIVE = 'archive';
    public const SHRED = 'shred';
    public const APPROVE_SHREDDING = 'approve_shredding';
    public const DECLINE_SHREDDING = 'decline_shredding';
    public const SUGGEST_SHREDDING = 'suggest_shredding';

    public static $all = array(
        self::DELETE_DOCUMENTS,
        self::APPROVE_ARCHIVATION,
        self::DECLINE_ARCHIVATION,
        self::ARCHIVE,
        self::SHRED,
        self::APPROVE_SHREDDING,
        self::DECLINE_SHREDDING,
        self::SUGGEST_SHREDDING
    );
}

?>