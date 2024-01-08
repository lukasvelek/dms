<?php

namespace DMS\Services;

use DMS\Authorizators\DocumentAuthorizator;
use DMS\Constants\DocumentStatus;
use DMS\Core\CacheManager;
use DMS\Core\Logger\Logger;
use DMS\Models\DocumentModel;
use DMS\Models\ServiceModel;

class DeclinedDocumentRemoverService extends AService {
    private DocumentModel $documentModel;
    private DocumentAuthorizator $documentAuthorizator;

    public function __construct(Logger $logger, ServiceModel $serviceModel, CacheManager $cm, DocumentModel $documentModel, DocumentAuthorizator $documentAuthorizator) {
        parent::__construct('DeclinedDocumentRemoverService', 'Deletes declined documents', $logger, $serviceModel, $cm);

        $this->documentModel = $documentModel;
        $this->documentAuthorizator = $documentAuthorizator;
    }

    public function run() {
        $this->startService();

        $documents = $this->documentModel->getAllDocumentsByStatus(DocumentStatus::ARCHIVATION_DECLINED);

        $this->log('Found ' . count($documents) . ' declined documents', __METHOD__);

        $deleted = 0;
        if(count($documents) > 0) {
            foreach($documents as $document) {
                if($this->documentAuthorizator->canDeleteDocument($document->getId(), false)) {
                    $this->documentModel->deleteDocument($document->getId(), true);
                    $deleted++;
                }
            }
        }

        $this->log('Deleted ' . $deleted . ' documents', __METHOD__);

        $this->stopService();
    }
}

?>