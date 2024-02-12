<?php

namespace DMS\Core;

use DMS\Authorizators\DocumentAuthorizator;
use DMS\Components\DocumentReportGeneratorComponent;
use DMS\Components\ProcessComponent;
use DMS\Core\Logger\Logger;
use DMS\Models\DocumentModel;
use DMS\Models\GroupUserModel;
use DMS\Models\MailModel;
use DMS\Models\NotificationModel;
use DMS\Models\ServiceModel;
use DMS\Models\UserModel;
use DMS\Services\CacheRotateService;
use DMS\Services\DeclinedDocumentRemoverService;
use DMS\Services\DocumentArchivationService;
use DMS\Services\DocumentReportGeneratorService;
use DMS\Services\FileManagerService;
use DMS\Services\LogRotateService;
use DMS\Services\MailService;
use DMS\Services\NotificationManagerService;
use DMS\Services\PasswordPolicyService;
use DMS\Services\ShreddingSuggestionService;

class ServiceManager {
    private Logger $logger;
    private ServiceModel $serviceModel;
    private FileStorageManager $fsm;
    private DocumentModel $documentModel;
    private CacheManager $cm;
    private DocumentAuthorizator $documentAuthorizator;
    private ProcessComponent $processComponent;
    private UserModel $userModel;
    private GroupUserModel $groupUserModel;
    private MailModel $mailModel;
    private MailManager $mailManager;
    private NotificationModel $notificationModel;
    private DocumentReportGeneratorComponent $documentReportGeneratorComponent;

    public array $services;

    public function __construct(Logger $logger, ServiceModel $serviceModel, FileStorageManager $fsm, DocumentModel $documentModel, CacheManager $cm, DocumentAuthorizator $documentAuthorizator, ProcessComponent $processComponent, UserModel $userModel, GroupUserModel $groupUserModel, MailModel $mailModel, MailManager $mailManager, NotificationModel $notificationModel, DocumentReportGeneratorComponent $documentReportGeneratorComponent) {
        $this->logger = $logger;
        $this->serviceModel = $serviceModel;
        $this->fsm = $fsm;
        $this->documentModel = $documentModel;
        $this->cm = $cm;
        $this->documentAuthorizator = $documentAuthorizator;
        $this->processComponent = $processComponent;
        $this->userModel = $userModel;
        $this->groupUserModel = $groupUserModel;
        $this->mailModel = $mailModel;
        $this->mailManager = $mailManager;
        $this->notificationModel = $notificationModel;
        $this->documentReportGeneratorComponent = $documentReportGeneratorComponent;
        
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
        $this->services['Log Rotate'] = new LogRotateService($this->logger, $this->serviceModel, $this->cm);
        $this->services['Cache Rotate'] = new CacheRotateService($this->logger, $this->serviceModel, $this->cm);
        $this->services['File Manager'] = new FileManagerService($this->logger, $this->serviceModel, $this->fsm, $this->documentModel, $this->cm);
        $this->services['Shredding Suggestion Service'] = new ShreddingSuggestionService($this->logger, $this->serviceModel, $this->cm, $this->documentAuthorizator, $this->documentModel, $this->processComponent);
        $this->services['Password Policy Service'] = new PasswordPolicyService($this->logger, $this->serviceModel, $this->cm, $this->userModel, $this->groupUserModel);
        $this->services['Mail Service'] = new MailService($this->logger, $this->serviceModel, $this->cm, $this->mailModel, $this->mailManager);
        $this->services['Notification manager'] = new NotificationManagerService($this->logger, $this->serviceModel, $this->cm, $this->notificationModel);
        $this->services['Document archivator'] = new DocumentArchivationService($this->logger, $this->serviceModel, $this->cm, $this->documentModel, $this->documentAuthorizator);
        $this->services['Declined document remover'] = new DeclinedDocumentRemoverService($this->logger, $this->serviceModel, $this->cm, $this->documentModel, $this->documentAuthorizator);
        $this->services['Document report generator'] = new DocumentReportGeneratorService($this->logger, $this->serviceModel, $this->cm, $this->documentModel, $this->documentReportGeneratorComponent);
    }
}

?>