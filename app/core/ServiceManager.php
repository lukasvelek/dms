<?php

namespace DMS\Core;

use DMS\Authorizators\DocumentAuthorizator;
use DMS\Components\DocumentReportGeneratorComponent;
use DMS\Components\NotificationComponent;
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

/**
 * Manager responsible for services
 * 
 * @author Lukas Velek
 */
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
    private NotificationComponent $notificationComponent;

    private array $runDates;

    public array $services;

    /**
     * Class constructor
     * 
     * @param Logger $logger Logger instance
     * @param ServiceModel $serviceModel ServiceModel instance
     * @param FileStorageManager $fsm FileStorageManager instance
     * @param DocumentModel $documentModel DocumentModel instance
     * @param CacheManager $cm CacheManager instance
     * @param DocumentAuthorizator $documentAuthorizator DocumentAuthorizator instance
     * @param ProcessComponent $processComponent ProcessComponent instance
     * @param UserModel $userModel UserModel instance
     * @param GroupUserModel $groupUserModel GroupUserModel instance
     * @param MailModel $mailModel MailModel instance
     * @param MailManager $mailManager MailManager instance
     * @param NotificationModel $notificationModel NotificationModel instance
     * @param DocumentReportGeneratorComponent $documentReportGeneratorComponent DocumentReportGeneratorComponent instance
     * @param NotificationComponent $notificationComponent NotificationComponent instance
     */
    public function __construct(Logger $logger, 
                                ServiceModel $serviceModel, 
                                FileStorageManager $fsm, 
                                DocumentModel $documentModel, 
                                CacheManager $cm, 
                                DocumentAuthorizator $documentAuthorizator, 
                                ProcessComponent $processComponent, 
                                UserModel $userModel, 
                                GroupUserModel $groupUserModel, 
                                MailModel $mailModel, 
                                MailManager $mailManager, 
                                NotificationModel $notificationModel, 
                                DocumentReportGeneratorComponent $documentReportGeneratorComponent, 
                                NotificationComponent $notificationComponent) {
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
        $this->notificationComponent = $notificationComponent;
        
        $this->loadServices();
        $this->loadRunDates();
    }

    /**
     * Returns service by its name
     * 
     * @param string $name Service name
     * @return null|AService Service instance or null
     */
    public function getServiceByName(string $name) {
        foreach($this->services as $k => $v) {
            if($v->name == $name) {
                return $v;
            }
        }

        return null;
    }

    /**
     * Returns last run date for a service
     * 
     * @param string $name Service name
     * @return string Run date or dash
     */
    public function getLastRunDateForService(string $name) {
        if(array_key_exists($name, $this->runDates)) {
            return $this->runDates[$name]['last_run_date'];
        } else {
            return '-';
        }
    }

    /**
     * Returns next run date for a service
     * 
     * @param string $name Service name
     * @return string Run date or dash
     */
    public function getNextRunDateForService(string $name) {
        if(array_key_exists($name, $this->runDates) && array_key_exists('last_run_date', $this->runDates[$name]) && array_key_exists('next_run_date', $this->runDates[$name])) {
            return $this->runDates[$name]['next_run_date'];
        } else {
            return '-';
        }
    }

    /**
     * Loads run dates for services
     */
    private function loadRunDates() {
        $cm = CacheManager::getTemporaryObject(CacheCategories::SERVICE_RUN_DATES);
        $data = [];

        foreach($this->services as $service) {
            $valFromCache = $cm->loadServiceEntry($service->name);

            $data[$service->name] = [];

            if($valFromCache === NULL || empty($valFromCache)) {
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

    /**
     * Creates service instances
     * 
     * To disable service, comment the service line that saves instance to the ServiceManager::services array
     */
    private function loadServices() {
        $this->services['LogRotateService'] = new LogRotateService($this->logger, $this->serviceModel, $this->cm);
        $this->services['CacheRotateService'] = new CacheRotateService($this->logger, $this->serviceModel, $this->cm);
        $this->services['FileManagerService'] = new FileManagerService($this->logger, $this->serviceModel, $this->fsm, $this->documentModel, $this->cm);
        $this->services['ShreddingSuggestionService'] = new ShreddingSuggestionService($this->logger, $this->serviceModel, $this->cm, $this->documentAuthorizator, $this->documentModel, $this->processComponent);
        $this->services['PasswordPolicyService'] = new PasswordPolicyService($this->logger, $this->serviceModel, $this->cm, $this->userModel, $this->groupUserModel);
        $this->services['MailService'] = new MailService($this->logger, $this->serviceModel, $this->cm, $this->mailModel, $this->mailManager);
        $this->services['NotificationManagerService'] = new NotificationManagerService($this->logger, $this->serviceModel, $this->cm, $this->notificationModel);
        $this->services['DocumentArchivationService'] = new DocumentArchivationService($this->logger, $this->serviceModel, $this->cm, $this->documentModel, $this->documentAuthorizator);
        $this->services['DeclinedDocumentRemoverService'] = new DeclinedDocumentRemoverService($this->logger, $this->serviceModel, $this->cm, $this->documentModel, $this->documentAuthorizator);
        $this->services['DocumentReportGenerator'] = new DocumentReportGeneratorService($this->logger, $this->serviceModel, $this->cm, $this->documentModel, $this->documentReportGeneratorComponent, $this->notificationComponent);
    }
}

?>