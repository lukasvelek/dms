<?php

namespace DMS\Services;

use DMS\Authorizators\DocumentAuthorizator;
use DMS\Components\ProcessComponent;
use DMS\Constants\DocumentShreddingStatus;
use DMS\Constants\ProcessTypes;
use DMS\Core\CacheManager;
use DMS\Core\Logger\Logger;
use DMS\Models\DocumentModel;
use DMS\Models\ServiceModel;

class ShreddingSuggestionService extends AService {
    private DocumentAuthorizator $documentAuthorizator;
    private DocumentModel $documentModel;
    private ProcessComponent $processComponent;
    private array $cfg;

    public function __construct(Logger $logger, ServiceModel $serviceModel, CacheManager $cm, DocumentAuthorizator $documentAuthorizator, DocumentModel $documentModel, ProcessComponent $processComponent, array $cfg) {
        parent::__construct('ShreddingSuggestionService', 'Suggests documents for shredding', $logger, $serviceModel, $cm);

        $this->documentAuthorizator = $documentAuthorizator;
        $this->documentModel = $documentModel;
        $this->processComponent = $processComponent;
        $this->cfg = $cfg;
    }

    public function run() {
        $this->startService();

        $documents = $this->documentModel->getAllDocuments();

        $toSuggest = [];
        foreach($documents as $document) {
            if($this->documentAuthorizator->canSuggestForShredding($document->getId())) {
                $toSuggest[] = $document->getId();
            }
        }

        $this->log(sprintf('Found %s documents that have been suggested for shredding', count($toSuggest)), __METHOD__);

        foreach($toSuggest as $id) {
            $this->documentModel->updateDocument($id, array(
                'shredding_status' => DocumentShreddingStatus::IN_APPROVAL
            ));

            $this->processComponent->startProcess(ProcessTypes::SHREDDING, $id, $this->cfg['id_service_user']);
        }

        $this->stopService();
    }
}

?>