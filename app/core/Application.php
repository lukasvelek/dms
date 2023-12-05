<?php

namespace DMS\Core;

use \DMS\Entities\User;
use \DMS\Modules\IModule;
use \DMS\Core\DB\Database;
use DMS\Authenticators\UserAuthenticator;
use DMS\Authorizators\ActionAuthorizator;
use DMS\Authorizators\BulkActionAuthorizator;
use DMS\Authorizators\DocumentAuthorizator;
use DMS\Authorizators\DocumentBulkActionAuthorizator;
use DMS\Authorizators\MetadataAuthorizator;
use DMS\Authorizators\PanelAuthorizator;
use DMS\Components\NotificationComponent;
use DMS\Components\ProcessComponent;
use DMS\Components\SharingComponent;
use DMS\Components\WidgetComponent;
use DMS\Constants\CacheCategories;
use \DMS\Core\Logger\Logger;
use \DMS\Core\FileManager;
use DMS\Models\DocumentCommentModel;
use DMS\Models\DocumentModel;
use DMS\Models\FolderModel;
use DMS\Models\GroupModel;
use DMS\Models\GroupRightModel;
use DMS\Models\GroupUserModel;
use DMS\Models\MetadataModel;
use DMS\Models\NotificationModel;
use DMS\Models\ProcessCommentModel;
use DMS\Models\ProcessModel;
use DMS\Models\ServiceModel;
use DMS\Models\TableModel;
use DMS\Models\UserModel;
use DMS\Models\UserRightModel;
use DMS\Models\WidgetModel;
use DMS\Panels\Panels;

/**
 * This is the entry point of the whole application. It contains definition for the whole frontend and backend as well.
 * All necessary classes are constructed here and kept in the variables.
 * The loaded application config file is also kept here.
 * 
 * @author Lukas Velek
 */
class Application {
    public const URL_LOGIN_PAGE = 'AnonymModule:LoginPage:showForm';
    public const URL_HOME_PAGE = 'UserModule:HomePage:showHomepage';
    public const URL_SETTINGS_PAGE = 'UserModule:Settings:showDashboard';
    public const URL_DOCUMENTS_PAGE = 'UserModule:Documents:showAll';
    public const URL_PROCESSES_PAGE = 'UserModule:Processes:showAll';

    public const SYSTEM_VERSION = '1.4_beta';
    public const SYSTEM_BUILD_DATE = '2023/12/05';

    public array $cfg;
    public ?string $currentUrl;

    public ?User $user;
    public Logger $logger;
    public FileManager $fileManager;
    public FileStorageManager $fsManager;
    public ServiceManager $serviceManager;
    
    public UserAuthenticator $userAuthenticator;

    public UserModel $userModel;
    public UserRightModel $userRightModel;
    public DocumentModel $documentModel;
    public GroupModel $groupModel;
    public GroupUserModel $groupUserModel;
    public ProcessModel $processModel;
    public GroupRightModel $groupRightModel;
    public MetadataModel $metadataModel;
    public TableModel $tableModel;
    public FolderModel $folderModel;
    public ServiceModel $serviceModel;
    public DocumentCommentModel $documentCommentModel;
    public ProcessCommentModel $processCommentModel;
    public WidgetModel $widgetModel;
    public NotificationModel $notificationModel;

    public PanelAuthorizator $panelAuthorizator;
    public BulkActionAuthorizator $bulkActionAuthorizator;
    public DocumentAuthorizator $documentAuthorizator;
    public ActionAuthorizator $actionAuthorizator;
    public MetadataAuthorizator $metadataAuthorizator;
    public DocumentBulkActionAuthorizator $documentBulkActionAuthorizator;

    public ProcessComponent $processComponent;
    public WidgetComponent $widgetComponent;
    public SharingComponent $sharingComponent;
    public NotificationComponent $notificationComponent;

    private array $modules;
    private ?string $pageContent;
    private string $baseDir;

    private Database $conn;

    /**
     * This is the application class constructor. Here are all other classes constructed and assigned to their respective variables.
     * 
     * @param array $cfg The application configuration file contents
     */
    public function __construct(array $cfg, string $baseDir = '', bool $install = true) {
        $this->cfg = $cfg;
        $this->baseDir = $baseDir;

        $this->currentUrl = null;
        $this->modules = array();
        $this->pageContent = null;
        $this->user = null;

        $this->fileManager = new FileManager($this->baseDir . $this->cfg['log_dir'], $this->baseDir . $this->cfg['cache_dir']);
        $this->logger = new Logger($this->fileManager, $this->cfg);
        $this->conn = new Database($this->cfg['db_server'], $this->cfg['db_user'], $this->cfg['db_pass'], $this->cfg['db_name'], $this->logger);

        $this->userAuthenticator = new UserAuthenticator($this->conn, $this->logger);

        $this->userModel = new UserModel($this->conn, $this->logger);
        $this->userRightModel = new UserRightModel($this->conn, $this->logger);
        $this->documentModel = new DocumentModel($this->conn, $this->logger);
        $this->groupModel = new GroupModel($this->conn, $this->logger);
        $this->groupUserModel = new GroupUserModel($this->conn, $this->logger, $this->groupModel);
        $this->processModel = new ProcessModel($this->conn, $this->logger);
        $this->groupRightModel = new GroupRightModel($this->conn, $this->logger);
        $this->metadataModel = new MetadataModel($this->conn, $this->logger);
        $this->tableModel = new TableModel($this->conn, $this->logger);
        $this->folderModel = new FolderModel($this->conn, $this->logger);
        $this->serviceModel = new ServiceModel($this->conn, $this->logger);
        $this->documentCommentModel = new DocumentCommentModel($this->conn, $this->logger);
        $this->processCommentModel = new ProcessCommentModel($this->conn, $this->logger);
        $this->widgetModel = new WidgetModel($this->conn, $this->logger);
        $this->notificationModel = new NotificationModel($this->conn, $this->logger);
        
        $this->panelAuthorizator = new PanelAuthorizator($this->conn, $this->logger, $this->userRightModel, $this->groupUserModel, $this->groupRightModel, $this->user);
        $this->bulkActionAuthorizator = new BulkActionAuthorizator($this->conn, $this->logger, $this->userRightModel, $this->groupUserModel, $this->groupRightModel, $this->user);
        $this->actionAuthorizator = new ActionAuthorizator($this->conn, $this->logger, $this->userRightModel, $this->groupUserModel, $this->groupRightModel, $this->user);
        $this->metadataAuthorizator = new MetadataAuthorizator($this->conn, $this->logger, $this->user, $this->userModel, $this->groupUserModel);
        
        if($install) {
            $this->installDb();
        }
        
        $this->fsManager = new FileStorageManager($this->baseDir . $this->cfg['file_dir'], $this->fileManager, $this->logger);
        
        $serviceManagerCacheManager = new CacheManager($this->cfg['serialize_cache'], CacheCategories::SERVICE_CONFIG);
        
        $this->notificationComponent = new NotificationComponent($this->conn, $this->logger, $this->notificationModel);
        $this->processComponent = new ProcessComponent($this->conn, $this->logger, $this->processModel, $this->groupModel, $this->groupUserModel, $this->documentModel, $this->notificationComponent);
        $this->widgetComponent = new WidgetComponent($this->conn, $this->logger, $this->documentModel, $this->processModel);
        $this->sharingComponent = new SharingComponent($this->conn, $this->logger, $this->documentModel);
        
        $this->documentAuthorizator = new DocumentAuthorizator($this->conn, $this->logger, $this->documentModel, $this->userModel, $this->processModel, $this->user, $this->processComponent);
        $this->documentBulkActionAuthorizator = new DocumentBulkActionAuthorizator($this->conn, $this->logger, $this->user, $this->documentAuthorizator, $this->bulkActionAuthorizator);
        
        $this->serviceManager = new ServiceManager($this->logger, $this->serviceModel, $this->cfg, $this->fsManager, $this->documentModel, $serviceManagerCacheManager, $this->documentAuthorizator, $this->processComponent);
        
        //$this->conn->installer->updateDefaultUserPanelRights();
    }

    /**
     * Redirects the application page to different page using constructed URL that is based on passed parameters.
     * 
     * @param string $url The default page URL
     * @param array $params All other parameters that should be passed to the presenter
     */
    public function redirect(string $url, array $params = array()) {
        $page = '?';

        $newParams = array('page' => $url);

        foreach($params as $k => $v) {
            $newParams[$k] = $v;
        }

        $i = 0;
        foreach($newParams as $paramKey => $paramValue) {
            if(($i + 1) == count($newParams)) {
                $page .= $paramKey . '=' . $paramValue;
            } else {
                $page .= $paramKey . '=' . $paramValue . '&';
            }

            $i++;
        }

        echo $page;

        header('Location: ' . $page);
    }

    /**
     * Shows the current page to the user
     */
    public function showPage() {
        if(is_null($this->pageContent)) {
            $this->renderPage();
        }

        echo $this->pageContent;
    }

    /**
     * Renders the current page and saves it to the $pageContent variable
     */
    public function renderPage() {
        // --- TOPPANEL ---

        $toppanel = $this->renderToppanel();

        // --- TOPPANEL ---

        if(is_null($this->currentUrl)) {
            die('Current URL is null!');
        }

        // Module:Presenter:action

        $parts = explode(':', $this->currentUrl);
        $module = $parts[0];
        $presenter = $parts[1];
        $action = $parts[2];

        if(array_key_exists($module, $this->modules)) {
            $module = $this->modules[$module];
        } else {
            die('Module does not exist!');
        }

        $presenter = $module->getPresenterByName($presenter);
        if(!is_null($presenter)) {
            $module->setPresenter($presenter);
        } else {
            die('Presenter does not exist');
        }

        if($presenter::DRAW_TOPPANEL) {
            $this->pageContent = $toppanel;
        }

        $this->pageContent .= $module->currentPresenter->performAction($action);
    }

    /**
     * Renders the toppanel
     * 
     * @return string HTML code of the toppanel
     */
    public function renderToppanel() {
        $panel = Panels::createTopPanel();

        return $panel;
    }

    /**
     * Registers the passed module to the module system
     * 
     * @param IModule $module Module to be saved
     */
    public function registerModule(IModule $module) {
        $this->modules[$module->getName()] = $module;
    }

    /**
     * Returns a component based on its name
     * 
     * @param string $name Component name
     * @param mixed|null Mixed if the component exists and null if it does not exist
     */
    public function getComponent(string $name) {
        if(isset($this->$name)) {
            return $this->$name;
        } else {
            return null;
        }
    }

    /**
     * Sets the current user
     * 
     * @param User $user Current user
     */
    public function setCurrentUser(User $user) {
        $this->user = $user;
        $this->actionAuthorizator->setIdUser($this->user->getId());
        $this->bulkActionAuthorizator->setIdUser($this->user->getId());
        $this->documentAuthorizator->setIdUser($this->user->getId());
        $this->metadataAuthorizator->setIdUser($this->user->getId());
        $this->panelAuthorizator->setIdUser($this->user->getId());
    }

    /**
     * Returns the database connection
     * 
     * @return Database $conn Database connection
     */
    public function getConn() {
        return $this->conn;
    }

    /**
     * Performs the initial database installation.
     * After installing, it creates a file that shows whether the database has been installed or not.
     */
    private function installDb() {
        if(!file_exists('app/core/install')) {
            $this->conn->installer->install();

            file_put_contents('app/core/install', 'installed');
        }
    }
}

?>