<?php

namespace DMS\Services;

use DMS\Constants\DocumentReportStatus;
use DMS\Core\AppConfiguration;
use DMS\Core\CacheManager;
use DMS\Core\FileStorageManager;
use DMS\Core\Logger\Logger;
use DMS\Models\DocumentModel;
use DMS\Models\ServiceModel;

class FileManagerService extends AService {
    private FileStorageManager $fsm;
    private DocumentModel $documentModel;

    public function __construct(Logger $logger, ServiceModel $serviceModel, FileStorageManager $fsm, DocumentModel $documentModel, CacheManager $cm) {
        parent::__construct('FileManagerService', 'Deletes old unused files', $logger, $serviceModel, $cm);

        $this->fsm = $fsm;
        $this->documentModel = $documentModel;

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

        $documentReportStorageDirectories = $this->fsm->getStorageDirectories('document_reports');

        $filesToDelete = [];
        foreach($documentReportStorageDirectories as $drsd) {
            $files = $this->fsm->getStoredFilesInDirectory($drsd);

            foreach($files as $file) {
                if($advancedLogging) $this->log('Found file "' . $file->getFullname() . '"', __METHOD__);
                if(mb_strpos($file->getName(), 'temp_')) {
                    $filesToDelete[] = $file->getFullname();
                    $this->documentModel->deleteDocumentReportQueueEntryByFilename($file->getName(), true);
                } else {
                    $timestampCreated = filemtime($file->getFullname());
                    if($advancedLogging === TRUE) $this->log('Found file for generated document report "' . $file->getName() . '" with date: ' . date('Y-m-d H:i:s', $timestampCreated), __METHOD__);
                    if(((AppConfiguration::getDocumentReportKeepLength() * 24 * 60 * 60) + $timestampCreated) < time()) {
                        if($advancedLogging === TRUE) $this->log('File "' . $file->getName() . '" is too old. Deleting...', __METHOD__);
                        $filesToDelete[] = $file->getFullname();
                    }
                }
            }
        }

        foreach($filesToDelete as $ftd) {
            unlink($ftd);
        }

        $this->log('Checking generated document report entries in the database', __METHOD__);

        $documentReportDbEntries = $this->documentModel->getDocumentReportQueueEntriesForStatus(DocumentReportStatus::FINISHED);

        foreach($documentReportDbEntries as $entry) {
            if(!$this->fsm->fm->fileExists($entry['file_src'])) {
                $this->log('Deleting entry #' . $entry['id'], __METHOD__);
                $this->documentModel->deleteDocumentReportQueueEntry($entry['id']);
            }
        }

        // END OF DOCUMENT REPORTS

        $this->stopService();
    }
}

?>