<?php

namespace DMS\Components\DocumentReports;

use DMS\Components\ExternalEnumComponent;
use DMS\Constants\CacheCategories;
use DMS\Core\CacheManager;
use DMS\Core\FileManager;
use DMS\Core\FileStorageManager;

abstract class ADocumentReport {
    public const SUPPORTED_EXTENSIONS = [
        'csv' => 'CSV',
        'html' => 'HTML'
    ];

    protected static $defaultMetadata = [
        'id', 'id_folder', 'name', 'date_created', 'date_updated', 'id_officer', 'id_manager', 'status', 'id_group', 'is_deleted', 'rank', 'file', 'shred_year', 'after_shred_action', 'shredding_status', 'id_archive_document', 'id_archive_box', 'id_archive_archive'
    ];

    protected ExternalEnumComponent $eec;
    protected FileManager $fm;
    protected FileStorageManager $fsm;
    protected array $cacheManagers;
    protected array $models;
    protected mixed $sqlResult;
    protected int $idCallingUser;
    protected ?string $filename;

    protected array $customMetadataValues = [
        'id_folder',
        'id_officer',
        'id_manager',
        'status',
        'id_group',
        'is_deleted',
        'rank',
        'id_archive_document',
        'id_archive_box',
        'id_archive_archive',
        'after_shred_action',
        'shredding_status'
    ];

    protected function __construct(ExternalEnumComponent $eec, FileManager $fm, FileStorageManager $fsm, mixed $sqlResult, int $idCallingUser, array $models) {
        $this->eec = $eec;
        $this->fm = $fm;
        $this->fsm = $fsm;
        $this->sqlResult = $sqlResult;
        $this->idCallingUser = $idCallingUser;
        $this->models = $models;
        $this->filename = null;

        $this->cacheManagers = [
            'users' => CacheManager::getTemporaryObject(CacheCategories::USERS)
        ];
    }

    protected function getCacheManagerByCat(string $cat) {
        if(array_key_exists($cat, $this->cacheManagers)) {
            return $this->cacheManagers[$cat];
        } else {
            return null;
        }
    }

    protected function generateFilename(int $idUser, string $extension) {
        return $idUser . '_' . date('Y-m-d_H-i-s') . '_document_report.' . $extension;
    }
}

?>