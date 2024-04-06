<?php

namespace DMS\Services;

use DMS\Authorizators\DocumentAuthorizator;
use DMS\Authorizators\DocumentBulkActionAuthorizator;
use DMS\Constants\DocumentStatus;
use DMS\Core\CacheManager;
use DMS\Core\Logger\Logger;
use DMS\Models\DocumentMetadataHistoryModel;
use DMS\Models\DocumentModel;
use DMS\Models\ServiceModel;

class DocumentArchivationService extends AService {
    private DocumentModel $documentModel;
    private DocumentAuthorizator $documentAuthorizator;
    private DocumentMetadataHistoryModel $dmhm;
    private DocumentBulkActionAuthorizator $dbaa;

    public function __construct(Logger $logger, ServiceModel $serviceModel, CacheManager $cm, DocumentModel $documentModel, DocumentAuthorizator $documentAuthorizator, DocumentMetadataHistoryModel $dmhm, DocumentBulkActionAuthorizator $dbaa) {
        parent::__construct('DocumentArchivationService', $logger, $serviceModel, $cm);

        $this->documentModel = $documentModel;
        $this->documentAuthorizator = $documentAuthorizator;
        $this->dmhm = $dmhm;
        $this->dbaa = $dbaa;
    }

    public function run() {
        $this->startService();
        
        $canArchiveIds = $this->dbaa->getAllDocumentIdsForArchive($this->documentModel, null, false, false);

        $qb = $this->documentModel->composeQueryStandardDocuments();
        $qb ->andWhere('status = ?', [DocumentStatus::ARCHIVATION_APPROVED])
            ->andWhere($qb->getColumnInValues('id', $canArchiveIds))
            ->execute();

        $ids = [];
        while($row = $qb->fetchAssoc()) {
            $ids[] = $row['id'];
        }

        $this->log('Found ' . count($ids) . ' documents waiting for archivation', __METHOD__);

        $archived = count($ids);
        if(count($ids) > 0) {
            $this->documentModel->updateDocumentsBulk(['status' => DocumentStatus::ARCHIVED], $ids);
            $this->dmhm->bulkInsertNewMetadataHistoryEntriesBasedOnDocumentMetadataArray(['status' => DocumentStatus::ARCHIVED], $ids, $_SESSION['id_current_user']);
        }

        $this->log('Archived ' . $archived . ' documents', __METHOD__);

        $this->stopService();
    }
}

?>