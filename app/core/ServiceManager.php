<?php

namespace DMS\Core;

use DMS\Authorizators\DocumentAuthorizator;
use DMS\Components\DocumentReportGeneratorComponent;
use DMS\Components\ProcessComponent;
use DMS\Constants\CacheCategories;
use DMS\Core\DB\Database;
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

    private array $runDates;

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
        $this->loadRunDates();
    }

    public function getServiceByName(string $name) {
        foreach($this->services as $k => $v) {
            if($v->name == $name) {
                return $v;
            }
        }

        return null;
    }

    public function getLastRunDateForService(string $name) {
        if(array_key_exists($name, $this->runDates)) {
            return $this->runDates[$name]['last_run_date'];
        } else {
            return '-';
        }
    }

    public function getNextRunDateForService(string $name) {
        if(array_key_exists($name, $this->runDates)) {
            return $this->runDates[$name]['next_run_date'];
        } else {
            return '-';
        }
    }

    private function loadRunDates() {
        $cm = CacheManager::getTemporaryObject(CacheCategories::SERVICE_RUN_DATES);
        $data = [];

        foreach($this->services as $service) {
            $valFromCache = $cm->loadServiceEntry($service->name);

            if($valFromCache === NULL) {
                // load from db

                $logEntry = $this->serviceModel->getServiceLogLastEntryForServiceName($service->name);

                if($logEntry !== NULL) {
                    $data[$service->name]['last_run_date'] = $logEntry['date_created'];
                    $data[$service->name]['next_run_date'] = date(Database::DB_DATE_FORMAT, strtotime($logEntry['date_created']) + ($this->serviceModel->getConfigForServiceName($service->name)['service_run_period'] * 24 * 60 * 60));
                }

                $cm->saveServiceEntry($service->name, $data[$service->name]);
            } else {
                $data[$service->name]['last_run_date'] = $valFromCache['last_run_date'];
                $data[$service->name]['next_run_date'] = $valFromCache['next_run_date'];
            }
        }

        $this->runDates = $data;
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