<?php

namespace DMS\Core;

use DMS\Authorizators\DocumentAuthorizator;
use DMS\Components\ProcessComponent;
use DMS\Core\Logger\Logger;
use DMS\Models\DocumentModel;
use DMS\Models\ServiceModel;
use DMS\Services\CacheRotateService;
use DMS\Services\FileManagerService;
use DMS\Services\LogRotateService;
use DMS\Services\ShreddingSuggestionService;

class ServiceManager {
    private Logger $logger;
    private ServiceModel $serviceModel;
    private FileStorageManager $fsm;
    private DocumentModel $documentModel;
    private CacheManager $cm;
    private array $cfg;
    private DocumentAuthorizator $documentAuthorizator;
    private ProcessComponent $processComponent;

    public array $services;

    public function __construct(Logger $logger, ServiceModel $serviceModel, array $cfg, FileStorageManager $fsm, DocumentModel $documentModel, CacheManager $cm, DocumentAuthorizator $documentAuthorizator, ProcessComponent $processComponent) {
        $this->logger = $logger;
        $this->cfg = $cfg;
        $this->serviceModel = $serviceModel;
        $this->fsm = $fsm;
        $this->documentModel = $documentModel;
        $this->cm = $cm;
        $this->documentAuthorizator = $documentAuthorizator;
        $this->processComponent = $processComponent;
        
        $this->loadServices();
    }

    public function getServiceByName(string $name) {
        foreach($this->services as $k => $v) {
            if($v->name == $name) {
                return $v;
            }
        }

        return null;
    }

    private function loadServices() {
        $this->services['Log Rotate'] = new LogRotateService($this->logger, $this->serviceModel, $this->cfg, $this->cm);
        $this->services['Cache Rotate'] = new CacheRotateService($this->logger, $this->serviceModel, $this->cfg, $this->cm);
        $this->services['File Manager'] = new FileManagerService($this->logger, $this->serviceModel, $this->cfg, $this->fsm, $this->documentModel, $this->cm);
        $this->services['Shredding Suggestion Service'] = new ShreddingSuggestionService($this->logger, $this->serviceModel, $this->cm, $this->documentAuthorizator, $this->documentModel, $this->processComponent, $this->cfg);
    }
}

?>