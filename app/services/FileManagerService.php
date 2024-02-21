<?php

namespace DMS\Services;

use DMS\Core\CacheManager;
use DMS\Core\FileManager;
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
        $this->startService();

        $storedFiles = $this->fsm->getStoredFiles();
        
        $toDelete = [];
        foreach($storedFiles as $sf) {
            $fullname = $sf->getFullname();

            $documents = $this->documentModel->getDocumentsForFilename($fullname);

            if(empty($documents)) {
                $toDelete[] = $fullname;
            }
        }

        $this->log('Found ' . count($toDelete) . ' files, that are not used, to delete', __METHOD__);

        foreach($toDelete as $td) {
            unlink($td);
        }

        $dirs = $this->fsm->getDirectories();

        for($i = (count($dirs) - 1); $i >= 0; $i--) {
            rmdir($dirs[$i]);
        }

        $this->stopService();
    }
}

?>