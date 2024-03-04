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
        parent::__construct('DocumentArchivationService', $logger, $serviceModel, $cm);

        $this->documentModel = $documentModel;
        $this->documentAuthorizator = $documentAuthorizator;
    }

    public function run() {
        $this->startService();

        $qb = $this->documentModel->composeQueryStandardDocuments();
        $qb ->andWhere('status = ?', [DocumentStatus::ARCHIVATION_APPROVED])
            ->execute();

        $ids = [];
        while($row = $qb->fetchAssoc()) {
            $ids[] = $row['id'];
        }

        $this->log('Found ' . count($ids) . ' documents waiting for archivation', __METHOD__);

        $archived = count($ids);;
        if(count($ids) > 0) {
            $this->documentModel->updateDocumentsBulk(['status' => DocumentStatus::ARCHIVED], $ids);
        }

        $this->log('Archived ' . $archived . ' documents', __METHOD__);

        $this->stopService();
    }
}

?>