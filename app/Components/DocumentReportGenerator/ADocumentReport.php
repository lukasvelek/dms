<?php

namespace DMS\Components\DocumentReports;

use DMS\Components\ExternalEnumComponent;
use DMS\Constants\CacheCategories;
use DMS\Constants\DocumentAfterShredActions;
use DMS\Constants\DocumentRank;
use DMS\Constants\DocumentShreddingStatus;
use DMS\Constants\DocumentStatus;
use DMS\Constants\FileStorageTypes;
use DMS\Constants\Metadata\DocumentReportMetadata;
use DMS\Constants\MetadataInputType;
use DMS\Core\CacheManager;
use DMS\Core\FileManager;
use DMS\Core\FileStorageManager;
use DMS\Entities\FileStorageLocation;

abstract class ADocumentReport {
    public const SUPPORTED_EXTENSIONS = [
        'csv' => 'CSV',
        'html' => 'HTML',
        'json' => 'JSON'
    ];

    protected const UPDATE_COUNT_CONST = 1000;
    public const SYNCHRONOUS_COUNT_CONST = 1000;
    
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
    protected array $customValues;
    protected ?int $idReport;

    private ?FileStorageLocation $reportStorageObj;

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

    protected function __construct(ExternalEnumComponent $eec, FileManager $fm, FileStorageManager $fsm, mixed $sqlResult, int $idCallingUser, array $models, ?int $idReport) {
        $this->eec = $eec;
        $this->fm = $fm;
        $this->fsm = $fsm;
        $this->sqlResult = $sqlResult;
        $this->idCallingUser = $idCallingUser;
        $this->models = $models;
        $this->filename = null;
        $this->customValues = [];
        $this->idReport = $idReport;

        $this->reportStorageObj = null;

        $this->cacheManagers = [
            'users' => CacheManager::getTemporaryObject(CacheCategories::USERS),
            'groups' => CacheManager::getTemporaryObject(CacheCategories::GROUPS),
            'folders' => CacheManager::getTemporaryObject(CacheCategories::FOLDERS)
        ];
    }

    protected function getCacheManagerByCat(string $cat) {
        if(array_key_exists($cat, $this->cacheManagers)) {
            return $this->cacheManagers[$cat];
        } else {
            return null;
        }
    }

    protected function generateFilename(int $idUser, string $extension = '') {
        if($extension !== '') {
            return $idUser . '_' . date('Y-m-d_H-i-s') . '_document_report.' . $extension;
        } else {
            return $idUser . '_' . date('Y-m-d_H-i-s') . '_document_report';
        }
    }

    protected function loadCustomMetadata() {
        $customMetadata = $this->models['metadataModel']->getAllMetadataForTableName('documents');

        foreach($customMetadata as $cm) {
            if($cm->getIsSystem()) continue;

            $metadata[] = $cm->getName();

            if(in_array($cm->getInputType(), [MetadataInputType::SELECT, MetadataInputType::SELECT_EXTERNAL])) {
                $this->customMetadataValues[] = $cm->getName();

                if($cm->getInputType() == MetadataInputType::SELECT) {
                    $values = $this->models['metadataModel']->getAllValuesForIdMetadata($cm->getId());
                    $this->customValues[$cm->getName()] = $values;
                } else {
                    $enum = $this->eec->getEnumByName($cm->getSelectExternalEnumName());

                    if($enum !== NULL) {
                        $this->customValues[$cm->getName()] = $enum->getValues();
                    }
                }
            }
        }

        return $customMetadata;
    }

    protected function getCustomMetadataValue(string $name, string $value) {
        $userModel = $this->models['userModel'];
        $folderModel = $this->models['folderModel'];
        $archiveModel = $this->models['archiveModel'];
        $groupModel = $this->models['groupModel'];
        $ucm = $this->getCacheManagerByCat('users');
        $gcm = $this->getCacheManagerByCat('groups');
        $fcm = $this->getCacheManagerByCat('folders');

        $data = [];
        if(array_key_exists($name, $this->customValues)) {
            foreach($this->customValues[$name] as $cv) {
                if($cv->getValue() == $value) {
                    return $cv->getName();
                }
            }
        }
        if($name == 'is_deleted') {
            return $value ? 'Yes' : 'No';
        }
        if($name == 'id_folder') {
            if(array_key_exists('folders', $data) && array_key_exists($value, $data['folders'])) {
                return $data['folders'][$value];
            } else {
                $valFromCache = $fcm->loadFolderByIdFromCache($value);

                if($valFromCache === NULL) {
                    $folder = $folderModel->getFolderById($value);
                    $fcm->saveFolderToCache($folder);
                    $data['folders'][$value] = $folder->getName();
                    return $folder->getName();
                } else {
                    $data['folders'][$value] = $valFromCache->getName();
                    return $valFromCache->getName();
                }
            }
        }
        if(in_array($name, ['id_officer', 'id_manager'])) {
            if(array_key_exists('users', $data) && array_key_exists($value, $data['users'])) {
                return $data['users'][$value];
            } else {
                $valFromCache = $ucm->loadUserByIdFromCache($value);

                if($valFromCache === NULL) {
                    $user = $userModel->getUserById($value);
                    $ucm->saveUserToCache($user);
                    $data['users'][$value] = $user->getFullname();
                    return $user->getFullname();
                } else {
                    $data['users'][$value] = $valFromCache->getFullname();
                    return $valFromCache->getFullname();
                }
            }
        }
        if(in_array($name, ['id_archive_document', 'id_archive_box', 'id_archive_archive'])) {
            if($name == 'id_archive_document') {
                if(array_key_exists('archive_documents', $data) && array_key_exists($value, $data['archive_documents'])) {
                    return $data['archive_documents'][$value];
                } else {
                    $archive = $archiveModel->getDocumentById($value)->getName();
                    $data['archive_documents'][$value] = $archive;
                    return $archive;
                }
            } else if($name == 'id_archive_box') {
                if(array_key_exists('archive_boxes', $data) && array_key_exists($value, $data['archive_boxes'])) {
                    return $data['archive_boxes'][$value];
                } else {
                    $archive = $archiveModel->getBoxById($value)->getName();
                    $data['archive_boxes'][$value] = $archive;
                    return $archive;
                }
            } else if($name == 'id_archive_archive') {
                if(array_key_exists('archive_archives', $data) && array_key_exists($value, $data['archive_boxes'])) {
                    return $data['archive_archives'][$value];
                } else {
                    $archive = $archiveModel->getArchiveById($value)->getName();
                    $data['archive_archives'][$value] = $archive;
                    return $archive;
                }
            }
        }
        if($name == 'status') {
            return DocumentStatus::$texts[$value];
        }
        if($name == 'rank') {
            return DocumentRank::$texts[$value];
        }
        if(in_array($name, ['id_group'])) {
            if(array_key_exists('groups', $data) && array_key_exists($value, $data['groups'])) {
                return $data['groups'][$value];
            } else {
                $valFromCache = $gcm->loadGroupByIdFromCache($value);

                if($valFromCache === NULL) {
                    $group = $groupModel->getGroupById($value);
                    $gcm->saveGroupToCache($group);
                    $data['groups'][$value] = $group->getName();
                    return $group->getName();
                } else {
                    $data['groups'][$value] = $valFromCache->getName();
                    return $valFromCache->getName();
                }
            }
        }
        if($name == 'after_shred_action') {
            return DocumentAfterShredActions::$texts[$value];
        }
        if($name == 'shredding_status') {
            return DocumentShreddingStatus::$texts[$value];
        }
        return $value;
    }

    protected function saveFile(string|array $data, ?string $filename = null, ?string $fileextension = null, bool $append = true) {
        if($filename === NULL) {
            if($this->filename === NULL) {
                $filename = $this->generateFilename($this->idCallingUser, $fileextension);
                $this->filename = $filename;
            } else {
                $filename = $this->filename;
            }
        }

        if($this->reportStorageObj === NULL) {
            $this->reportStorageObj = $this->fsm->getDefaultLocationForStorageType(FileStorageTypes::DOCUMENT_REPORTS);
        }


        if($this->reportStorageObj === NULL) {
            die('Report storage is null (DocumentReportGeneratorComponent::' . __METHOD__ . '())');
            exit;
        } else {
            $reportStorage = $this->reportStorageObj->getPath();
        }

        if($append === FALSE) {
            $defaultFilename = $filename;
            $i = 1;
            while($this->fm->fileExists($reportStorage . $filename)) {
                $filename = explode('.', $defaultFilename)[0];
                $filename = $filename . ' (' . $i . ').' . $fileextension;
                $i++;
            }
        }

        $writeResult = $this->fm->write($reportStorage . $filename, $data, !$append);

        if($writeResult === TRUE) {
            $path = $this->reportStorageObj->getPath();
            $path = str_replace('\\', '\\\\', $path);
            return [
                'file_src' => $this->reportStorageObj->getAbsolutePath() . $filename,
                'file_name' => $filename,
                'id_file_storage_location' => $this->reportStorageObj->getId()
            ];
        } else {
            return false;
        }
    }

    protected function calcFinishedPercent(int $current, int $total) {
        return (int)(($current / $total) * 100);
    }

    protected function updateFinishedProcent(int $procent, int $idReport) {
        return $this->models['documentModel']->updateDocumentReportQueueEntry($idReport, [DocumentReportMetadata::PERCENT_FINISHED => $procent]);
    }
}

?>