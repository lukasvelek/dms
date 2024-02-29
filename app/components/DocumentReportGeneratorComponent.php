<?php

namespace DMS\Components;

use DMS\Constants\CacheCategories;
use DMS\Constants\DocumentAfterShredActions;
use DMS\Constants\DocumentRank;
use DMS\Constants\DocumentShreddingStatus;
use DMS\Constants\DocumentStatus;
use DMS\Constants\FileStorageTypes;
use DMS\Constants\MetadataInputType;
use DMS\Core\CacheManager;
use DMS\Core\FileManager;
use DMS\Core\FileStorageManager;

/**
 * Component that generates document reports.
 * 
 * @author Lukas Velek
 */
class DocumentReportGeneratorComponent extends AComponent {
    private array $models;
    private FileManager $fm;
    private ExternalEnumComponent $eec;
    private CacheManager $ucm;
    private FileStorageManager $fsm;

    /**
     * Class constructor
     * 
     * @param array $models Database Models array
     * @param FileManager $fm FileManager instance
     * @param ExternalEnumComponent $eec ExternalEnumComponent instance
     */
    public function __construct(array $models, FileManager $fm, ExternalEnumComponent $eec, FileStorageManager $fsm) {
        $this->models = $models;
        $this->fm = $fm;
        $this->eec = $eec;
        $this->ucm = CacheManager::getTemporaryObject(CacheCategories::USERS);
        $this->fsm = $fsm;
    }

    /**
     * Method generates the document report and saves it to cache and if successful it returns the result file path
     * 
     * @param mixed $sqlResult The result of calling a SQL string
     * @param int $idUser ID of the user for whom the document report is generated
     * @param null|string $filename Explicit filename
     */
    public function generateReport($sqlResult, int $idUser, ?string $filename = null) {
        $fileRow = [];

        $metadata = [
            'id', 'id_folder', 'name', 'date_created', 'date_updated', 'id_officer', 'id_manager', 'status', 'id_group', 'is_deleted', 'rank', 'file', 'shred_year', 'after_shred_action', 'shredding_status', 'id_archive_document', 'id_archive_box', 'id_archive_archive'
        ];

        $metadataCustomValues = [
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

        $customValues = [];

        // CUSTOM METADATA

        $customMetadata = $this->models['metadataModel']->getAllMetadataForTableName('documents');

        foreach($customMetadata as $cm) {
            if($cm->getIsSystem()) continue;

            $metadata[] = $cm->getName();

            if(in_array($cm->getInputType(), [MetadataInputType::SELECT, MetadataInputType::SELECT_EXTERNAL])) {
                $metadataCustomValues[] = $cm->getName();

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
        $ucm = $this->ucm;

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

        foreach($sqlResult as $row) {
            $dataRow = '';

            $i = 0;
            foreach($metadata as $m) {
                $text = '-';
                if(isset($row[$m]) && ($row[$m] !== NULL)) {
                    if(!in_array($m, $metadataCustomValues)) {
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
            $filename = $this->generateFilename($idUser);
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
            return $reportStorageObj->getAbsolutePath() . $filename;
        } else {
            return false;
        }
    }

    /**
     * Generates the filename using default macros
     * 
     * @param int $idUser User ID
     * @return string Generated filename
     */
    private function generateFilename(int $idUser) {
        // %ID_USER%_%DATE_OF_CREATION%_document_report.csv

        return $idUser . '_' . date('Y-m-d_H-i-s') . '_document_report.csv';
    }
}

?>