<?php

namespace DMS\Core;

use DMS\Authorizators\DocumentAuthorizator;
use DMS\Authorizators\DocumentBulkActionAuthorizator;
use DMS\Components\DocumentLockComponent;
use DMS\Components\DocumentReportGeneratorComponent;
use DMS\Components\NotificationComponent;
use DMS\Components\ProcessComponent;
use DMS\Constants\CacheCategories;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Models\DocumentMetadataHistoryModel;
use DMS\Models\DocumentModel;
use DMS\Models\FileStorageModel;
use DMS\Models\GroupModel;
use DMS\Models\GroupUserModel;
use DMS\Models\MailModel;
use DMS\Models\NotificationModel;
use DMS\Models\ServiceModel;
use DMS\Models\UserModel;
use DMS\Repositories\DocumentCommentRepository;
use DMS\Repositories\DocumentRepository;
use DMS\Repositories\UserAbsenceRepository;
use DMS\Repositories\UserRepository;
use DMS\Services\DeclinedDocumentRemoverService;
use DMS\Services\DocumentArchivationService;
use DMS\Services\DocumentReportGeneratorService;
use DMS\Services\ExtractionService;
use DMS\Services\FileManagerService;
use DMS\Services\LogRotateService;
use DMS\Services\MailService;
use DMS\Services\NotificationManagerService;
use DMS\Services\ShreddingSuggestionService;
use DMS\Services\UserLoginBlockingManagerService;
use DMS\Services\UserSubstitutionProcessService;

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
    private FileStorageModel $fsModel;
    private DocumentMetadataHistoryModel $dmhm;
    private DocumentLockComponent $dlc;
    private DocumentBulkActionAuthorizator $dbaa;
    private FileManager $fm;
    private DocumentRepository $dr;
    private DocumentCommentRepository $dcr;
    private GroupModel $gm;
    private UserRepository $userRepository;
    private UserAbsenceRepository $userAbsenceRepository;

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
     * @param DocumentMetadataHistoryModel $dmhm DocumentMetadataHistoryModel instance
     * @param DocumentLockComponent $dlc DocumentLockComponent instance
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
                                NotificationComponent $notificationComponent,
                                FileStorageModel $fsModel,
                                DocumentMetadataHistoryModel $dmhm,
                                DocumentLockComponent $dlc,
                                DocumentBulkActionAuthorizator $dbaa,
                                FileManager $fm,
                                DocumentRepository $dr,
                                DocumentCommentRepository $dcr,
                                GroupModel $gm,
                                UserRepository $userRepository,
                                UserAbsenceRepository $userAbsenceRepository) {
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
        $this->fsModel = $fsModel;
        $this->dmhm = $dmhm;
        $this->dlc = $dlc;
        $this->dbaa = $dbaa;
        $this->fm = $fm;
        $this->dr = $dr;
        $this->dcr = $dcr;
        $this->gm = $gm;
        $this->userRepository = $userRepository;
        $this->userAbsenceRepository = $userAbsenceRepository;
        
        $this->loadServices();
        $this->loadRunDates();
    }

    /**
     * Starts a background service asynchronously
     * 
     * @param string $serviceName Service name
     * @return true
     */
    public function startBgProcess(string $serviceName) {
        $phpExe = AppConfiguration::getPhpDirectoryPath() . 'php.exe';

        $serviceFile = AppConfiguration::getServerPath() . 'services\\' . $serviceName . '.php';

        $cmd = $phpExe . ' ' . $serviceFile;

        if(substr(php_uname(), 0, 7) == "Windows") {
            pclose(popen("start /B ". $cmd, "w")); 
        } else {
            exec($cmd . " > /dev/null &");  
        }

        return true;
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
            if(array_key_exists('last_run_date', $this->runDates[$name])) {
                return $this->runDates[$name]['last_run_date'];
            } else {
                return '-';
            }
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
     * Updates run dates for services
     */
    public function updateRunDates() {
        $cm = CacheManager::getTemporaryObject(CacheCategories::SERVICE_RUN_DATES);
        $cm->invalidateCache();
        $this->loadRunDates();
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
        $this->services['FileManagerService'] = new FileManagerService($this->logger, $this->serviceModel, $this->fsm, $this->documentModel, $this->cm, $this->fsModel);
        $this->services['ShreddingSuggestionService'] = new ShreddingSuggestionService($this->logger, $this->serviceModel, $this->cm, $this->documentAuthorizator, $this->documentModel, $this->processComponent);
        $this->services['MailService'] = new MailService($this->logger, $this->serviceModel, $this->cm, $this->mailModel, $this->mailManager);
        $this->services['NotificationManagerService'] = new NotificationManagerService($this->logger, $this->serviceModel, $this->cm, $this->notificationModel);
        $this->services['DocumentArchivationService'] = new DocumentArchivationService($this->logger, $this->serviceModel, $this->cm, $this->documentModel, $this->documentAuthorizator, $this->dmhm, $this->dbaa);
        $this->services['DeclinedDocumentRemoverService'] = new DeclinedDocumentRemoverService($this->logger, $this->serviceModel, $this->cm, $this->documentModel, $this->documentAuthorizator, $this->dmhm, $this->dlc);
        $this->services['DocumentReportGeneratorService'] = new DocumentReportGeneratorService($this->logger, $this->serviceModel, $this->cm, $this->documentModel, $this->documentReportGeneratorComponent, $this->notificationComponent);
        $this->services['ExtractionService'] = new ExtractionService($this->logger, $this->serviceModel, $this->cm, $this->dr, $this->dcr, $this->fsm, $this->gm);
        $this->services['UserLoginBlockingManagerService'] = new UserLoginBlockingManagerService($this->logger, $this->serviceModel, $this->cm, $this->userRepository);
        $this->services['UserSubstitutionProcessService'] = new UserSubstitutionProcessService($this->logger, $this->serviceModel, $this->cm, $this->processComponent, $this->userAbsenceRepository);
    }
}

?>