<?php

namespace DMS\Services;

use DMS\Constants\DocumentReportStatus;
use DMS\Core\AppConfiguration;
use DMS\Core\CacheManager;
use DMS\Core\FileStorageManager;
use DMS\Core\Logger\Logger;
use DMS\Models\DocumentModel;
use DMS\Models\FileStorageModel;
use DMS\Models\ServiceModel;

class FileManagerService extends AService {
    private FileStorageManager $fsm;
    private DocumentModel $documentModel;
    private FileStorageModel $fsModel;

    public function __construct(Logger $logger, ServiceModel $serviceModel, FileStorageManager $fsm, DocumentModel $documentModel, CacheManager $cm, FileStorageModel $fsModel) {
        parent::__construct('FileManagerService', $logger, $serviceModel, $cm);

        $this->fsm = $fsm;
        $this->documentModel = $documentModel;
        $this->fsModel = $fsModel;

        $this->loadCfg();
    }

    public function run() {
        $advancedLogging = AppConfiguration::getIsDebug();
        $this->startService();

        // FILES

        $this->log('Checking document files', __METHOD__);

        $storedFiles = $this->fsm->getStoredFiles('files');
        
        $toDelete = [];
        foreach($storedFiles as $sf) {
            $fullname = $sf->getFullname();

            $documents = $this->documentModel->getDocumentsForFilename($fullname);

            if(empty($documents)) {
                $toDelete[] = $fullname;
            }
        }

        $this->log('Found ' . count($toDelete) . ' not used files from documents to delete', __METHOD__);

        foreach($toDelete as $td) {
            unlink($td);
        }

        $storageDirectories = $this->fsm->getStorageDirectories('files');

        $dirs = [];
        foreach($storageDirectories as $sd) {
            $this->fsm->fm->readFoldersInFolder($sd, $dirs);
        }

        $toDelete = [];
        foreach($dirs as $dir) {
            $files = [];
            $this->fsm->fm->readFilesInFolder($dir, $files);

            if(empty($files)) {
                $this->fsm->fm->deleteDirectory($dir);
            }
        }

        // END OF FILES



        // DOCUMENT REPORTS

        $this->log('Checking document reports', __METHOD__);

        $filesToDelete = [];
        $existingFiles = [];

        $documentReportDbEntries = $this->documentModel->getDocumentReportQueueEntriesForStatus(DocumentReportStatus::FINISHED);

        foreach($documentReportDbEntries as $entry) {
            $idFileStorageLocation = $entry['id_file_storage_location'];
            $realFilename = $entry['file_name'];

            $location = $this->fsModel->getLocationById($idFileStorageLocation);
            $realServerPath = $location->getPath() . $realFilename;
            if(!$this->fsm->fm->fileExists($realServerPath)) {
                $this->log('Deleting entry #' . $entry['id'], __METHOD__);
                $filesToDelete[] = $realServerPath;
                $this->documentModel->deleteDocumentReportQueueEntry($entry['id']);
            } else {
                $existingFiles[] = $entry['file_name'];
            }
        }

        $documentReportStorageDirectories = $this->fsm->getStorageDirectories('document_reports');

        foreach($documentReportStorageDirectories as $drsd) {
            $files = $this->fsm->getStoredFilesInDirectory($drsd);

            foreach($files as $file) {
                if($advancedLogging) $this->log('Found file "' . $file->getFullname() . '"', __METHOD__);
                if(mb_strpos($file->getName(), 'temp_')) {
                    if(!in_array($file->getFullname(), $filesToDelete)) {
                        $filesToDelete[] = $file->getFullname();
                    }
                    $this->documentModel->deleteDocumentReportQueueEntryByFilename($file->getName(), true);
                } else {
                    $timestampCreated = filemtime($file->getFullname());
                    if($advancedLogging === TRUE) $this->log('Found file for generated document report "' . $file->getName() . '" with date: ' . date('Y-m-d H:i:s', $timestampCreated), __METHOD__);
                    if(((AppConfiguration::getDocumentReportKeepLength() * 24 * 60 * 60) + $timestampCreated) < time() && !in_array($file->getFullname(), $existingFiles)) {
                        if($advancedLogging === TRUE) $this->log('File "' . $file->getName() . '" is too old. Deleting...', __METHOD__);
                        if(!in_array($file->getFullname(), $filesToDelete)) {
                            $filesToDelete[] = $file->getFullname();
                        }
                    }
                }
            }
        }

        $this->log('Found ' . count($filesToDelete) . ' generated document report files to be deleted', __METHOD__);

        foreach($filesToDelete as $ftd) {
            unlink($ftd);
        }

        // END OF DOCUMENT REPORTS

        $this->stopService();
    }
}

?>