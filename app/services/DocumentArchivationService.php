<?php

namespace DMS\Services;

use DMS\Authorizators\DocumentAuthorizator;
use DMS\Constants\DocumentStatus;
use DMS\Core\CacheManager;
use DMS\Core\Logger\Logger;
use DMS\Models\DocumentModel;
use DMS\Models\ServiceModel;

class DocumentArchivationService extends AService {
    private DocumentModel $documentModel;
    private DocumentAuthorizator $documentAuthorizator;

    public function __construct(Logger $logger, ServiceModel $serviceModel, CacheManager $cm, DocumentModel $documentModel, DocumentAuthorizator $documentAuthorizator) {
        parent::__construct('DocumentArchivationService', 'Archives documents waiting for archivation', $logger, $serviceModel, $cm);

        $this->documentModel = $documentModel;
        $this->documentAuthorizator = $documentAuthorizator;
    }

    public function run() {
        $this->startService();

        $documents = $this->documentModel->getStandardFilteredDocuments('waitingForArchivation');

        $this->log('Found ' . count($documents) . ' documents waiting for archivation', __METHOD__);

        $archived = 0;
        if(count($documents) > 0) {
            foreach($documents as $document) {
                if($this->documentAuthorizator->canArchive($document, true)) {
                    $this->documentModel->updateStatus($document->getId(), DocumentStatus::ARCHIVED);
                    $archived++;
                }
            }
        }

        $this->log('Archived ' . $archived . ' documents', __METHOD__);

        $this->stopService();
    }
}

?>