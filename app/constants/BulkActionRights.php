<?php

namespace DMS\Constants;

/**
 * Bulk action right contants
 * 
 * @author Lukas Velek
 */
class BulkActionRights {
    public const DELETE_DOCUMENTS = 'delete_documents';
    public const APPROVE_ARCHIVATION = 'approve_archivation';
    public const DECLINE_ARCHIVATION = 'decline_archivation';
    public const ARCHIVE = 'archive';
    public const SHRED = 'shred';
    public const APPROVE_SHREDDING = 'approve_shredding';
    public const DECLINE_SHREDDING = 'decline_shredding';
    public const SUGGEST_SHREDDING = 'suggest_shredding';
    public const MOVE_DOCUMENT_TO_ARCHIVE_DOCUMENT = 'move_document_to_archive_document';
    public const MOVE_DOCUMENT_FROM_ARCHIVE_DOCUMENT = 'move_document_from_archive_document';

    public static $all = array(
        self::DELETE_DOCUMENTS,
        self::APPROVE_ARCHIVATION,
        self::DECLINE_ARCHIVATION,
        self::ARCHIVE,
        self::SHRED,
        self::APPROVE_SHREDDING,
        self::DECLINE_SHREDDING,
        self::SUGGEST_SHREDDING,
        self::MOVE_DOCUMENT_TO_ARCHIVE_DOCUMENT,
        self::MOVE_DOCUMENT_FROM_ARCHIVE_DOCUMENT
    );
}

?>