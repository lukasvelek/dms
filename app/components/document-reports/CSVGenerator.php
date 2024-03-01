<?php

namespace DMS\Components\DocumentReports;

use DMS\Components\ExternalEnumComponent;
use DMS\Constants\DocumentAfterShredActions;
use DMS\Constants\DocumentRank;
use DMS\Constants\DocumentShreddingStatus;
use DMS\Constants\DocumentStatus;
use DMS\Constants\FileStorageTypes;
use DMS\Constants\MetadataInputType;
use DMS\Core\FileManager;
use DMS\Core\FileStorageManager;

class CSVGenerator extends ADocumentReport implements IGeneratable {
    public function __construct(ExternalEnumComponent $eec, FileManager $fm, FileStorageManager $fsm, mixed $sqlResult, int $idCallingUser, array $models) {
        parent::__construct($eec, $fm, $fsm, $sqlResult, $idCallingUser, $models);
    }

    public function generate(?string $filename = null): array|bool {
        $metadata = parent::$defaultMetadata;
        $fileRow = [];

        $customValues = [];

        // CUSTOM METADATA

        $customMetadata = $this->models['metadataModel']->getAllMetadataForTableName('documents');

        foreach($customMetadata as $cm) {
            if($cm->getIsSystem()) continue;

            $metadata[] = $cm->getName();

            if(in_array($cm->getInputType(), [MetadataInputType::SELECT, MetadataInputType::SELECT_EXTERNAL])) {
                $this->customMetadataValues[] = $cm->getName();

                if($cm->getInputType() == MetadataInputType::SELECT) {
                    $values = $this->models['metadataModel']->getAllValuesForIdMetadata($cm->getId());
                    $customValues[$cm->getName()] = $values;
                } else {
                    $enum = $this->eec->getEnumByName($cm->getSelectExternalEnumName());

                    if($enum !== NULL) {
                        $customValues[$cm->getName()] = $enum->getValues();
                    }
                }
            }
        }

        // END OF CUSTOM METADATA

        $userModel = $this->models['userModel'];
        $folderModel = $this->models['folderModel'];
        $archiveModel = $this->models['archiveModel'];
        $groupModel = $this->models['groupModel'];
        $ucm = $this->getCacheManagerByCat('users');

        $data = [];

        $getCustomValue = function(string $name, string $value) use ($userModel, $folderModel, $archiveModel, $customValues, $groupModel, $ucm, &$data) {
            if(array_key_exists($name, $customValues)) {
                foreach($customValues[$name] as $cv) {
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
                    $folder = $folderModel->getFolderById($value)->getName();
                    $data['folders'][$value] = $folder;
                    return $folder;
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
                    $group = $groupModel->getGroupById($value)->getName();
                    $data['groups'][$value] = $group;
                    return $group;
                }
            }
            if($name == 'after_shred_action') {
                return DocumentAfterShredActions::$texts[$value];
            }
            if($name == 'shredding_status') {
                return DocumentShreddingStatus::$texts[$value];
            }
            return $value;
        };

        $headerRow = '';

        $i = 0;
        foreach($metadata as $m) {
            if(($i + 1) == count($metadata)) {
                $headerRow .= $m;
            } else {
                $headerRow .= $m . ';';
            }
            $i++;
        }

        $fileRow[] = $headerRow . "\r\n";

        foreach($this->sqlResult as $row) {
            $dataRow = '';

            $i = 0;
            foreach($metadata as $m) {
                $text = '-';
                if(isset($row[$m]) && ($row[$m] !== NULL)) {
                    if(!in_array($m, $this->customMetadataValues)) {
                        $text = $row[$m];
                    } else {
                        $text = $getCustomValue($m, $row[$m]);
                    }
                }

                if(($i + 1) == count($metadata)) {
                    $dataRow .= $text;
                } else {
                    $dataRow .= $text . ';';
                }

                $i++;
            }

            $fileRow[] = $dataRow . "\r\n";
        }

        if($filename === NULL) {
            $filename = $this->generateFilename($this->idCallingUser, 'csv');
        }

        $reportStorageObj = $this->fsm->getDefaultLocationForStorageType(FileStorageTypes::DOCUMENT_REPORTS);

        if($reportStorageObj === NULL) {
            die('Report storage is null (DocumentReportGeneratorComponent::' . __METHOD__ . '())');
            exit;
        } else {
            $reportStorage = $reportStorageObj->getPath();
        }

        $defaultFilename = $filename;
        $i = 1;
        while($this->fm->fileExists($reportStorage . $filename)) {
            $filename = explode('.', $defaultFilename)[0];
            $filename = $filename . ' (' . $i . ').csv';
            $i++;
        }

        $writeResult = $this->fm->write($reportStorage . $filename, $fileRow, false);

        if($writeResult === TRUE) {
            $path = $reportStorageObj->getPath();
            $path = str_replace('\\', '\\\\', $path);
            return [
                'file_src' => $reportStorageObj->getAbsolutePath() . $filename,
                'file_name' => $filename,
                'id_file_storage_location' => $reportStorageObj->getId()
            ];
        } else {
            return false;
        }
    }
}

?>