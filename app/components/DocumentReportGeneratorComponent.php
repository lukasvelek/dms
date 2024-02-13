<?php

namespace DMS\Components;

use DMS\Constants\CacheCategories;
use DMS\Constants\DocumentAfterShredActions;
use DMS\Constants\DocumentRank;
use DMS\Constants\DocumentShreddingStatus;
use DMS\Constants\DocumentStatus;
use DMS\Constants\MetadataInputType;
use DMS\Core\CacheManager;
use DMS\Core\FileManager;

class DocumentReportGeneratorComponent extends AComponent {
    private array $models;
    private FileManager $fm;
    private ExternalEnumComponent $eec;
    private CacheManager $ucm;

    public function __construct(array $models, FileManager $fm, ExternalEnumComponent $eec) {
        $this->models = $models;
        $this->fm = $fm;
        $this->eec = $eec;
        $this->ucm = CacheManager::getTemporaryObject(CacheCategories::USERS);
    }

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

        $metadataModel = $this->models['metadataModel'];
        $documentModel = $this->models['documentModel'];
        $userModel = $this->models['userModel'];
        $folderModel = $this->models['folderModel'];
        $archiveModel = $this->models['archiveModel'];
        $groupModel = $this->models['groupModel'];
        $ucm = $this->ucm;

        $data = [];

        $getCustomValue = function(string $name, string $value) use ($metadataModel, $documentModel, $userModel, $folderModel, $archiveModel, $customValues, $groupModel, $ucm, $data) {
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
                return $folderModel->getFolderById($value)->getName();
            }
            if(in_array($name, ['id_officer', 'id_manager'])) {
                $valFromCache = $ucm->loadUserByIdFromCache($value);

                if($valFromCache === NULL) {
                    $user = $userModel->getUserById($value);
                    $ucm->saveUserToCache($user);
                    return $user->getFullname();
                } else {
                    return $valFromCache->getFullname();
                }
            }
            if(in_array($name, ['id_archive_document', 'id_archive_box', 'id_archive_archive'])) {
                if($name == 'id_archive_document') {
                    return $archiveModel->getDocumentById($value)->getName();
                } else if($name == 'id_archive_box') {
                    return $archiveModel->getBoxById($value)->getName();
                } else if($name == 'id_archive_archive') {
                    return $archiveModel->getArchiveById($value)->getName();
                }
            }
            if($name == 'status') {
                return DocumentStatus::$texts[$value];
            }
            if($name == 'rank') {
                return DocumentRank::$texts[$value];
            }
            if(in_array($name, ['id_group'])) {
                return $groupModel->getGroupById($value)->getName();
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

        $defaultFilename = $filename;
        $i = 1;
        while($this->fm->fileExists('cache/' . $filename)) {
            $filename = explode('.', $defaultFilename)[0];
            $filename = $filename . ' (' . $i . ').csv';
            $i++;
        }

        $writeResult = $this->fm->write('cache/' . $filename, $fileRow, false);

        if($writeResult === TRUE) {
            return 'cache/' . $filename;
        } else {
            return false;
        }
    }

    private function generateFilename(int $idUser) {
        // %ID_USER%_%DATE_OF_CREATION%_document_report.csv

        return $idUser . '_' . date('Y-m-d_H-i-s') . '_document_report.csv';
    }
}

?>