<?php

namespace DMS\Services;

use DMS\Authorizators\DocumentAuthorizator;
use DMS\Components\DocumentLockComponent;
use DMS\Constants\DocumentStatus;
use DMS\Core\CacheManager;
use DMS\Core\Logger\Logger;
use DMS\Models\DocumentMetadataHistoryModel;
use DMS\Models\DocumentModel;
use DMS\Models\ServiceModel;

class DeclinedDocumentRemoverService extends AService {
    private DocumentModel $documentModel;
    private DocumentAuthorizator $documentAuthorizator;
    private DocumentMetadataHistoryModel $dmhm;
    private DocumentLockComponent $dlc;

    public function __construct(Logger $logger, ServiceModel $serviceModel, CacheManager $cm, DocumentModel $documentModel, DocumentAuthorizator $documentAuthorizator, DocumentMetadataHistoryModel $dmhm, DocumentLockComponent $dlc) {
        parent::__construct('DeclinedDocumentRemoverService', $logger, $serviceModel, $cm);

        $this->documentModel = $documentModel;
        $this->documentAuthorizator = $documentAuthorizator;
        $this->dmhm = $dmhm;
        $this->dlc = $dlc;
    }

    public function run() {
        $this->startService();

        $documents = $this->documentModel->getAllDocumentsByStatus(DocumentStatus::ARCHIVATION_DECLINED);

        $this->log('Found ' . count($documents) . ' declined documents', __METHOD__);

        $deleted = 0;
        if(count($documents) > 0) {
            foreach($documents as $document) {
                if($this->documentAuthorizator->canDeleteDocument($document, null, false, true)) {
                    $this->documentModel->deleteDocument($document->getId(), true);
                    $this->dmhm->deleteEntriesForIdDocument($document->getId());
                    $this->dlc->deleteLockEntriesForIdDocument($document->getId());
                    $deleted++;
                }
            }
        }

        $this->log('Deleted ' . $deleted . ' documents', __METHOD__);

        $this->stopService();
    }
}

?>