<?php

namespace DMS\Core;

use DMS\Entities\User;
use DMS\Modules\IModule;
use DMS\Core\DB\Database;
use DMS\Authenticators\UserAuthenticator;
use DMS\Authorizators\ActionAuthorizator;
use DMS\Authorizators\BulkActionAuthorizator;
use DMS\Authorizators\DocumentAuthorizator;
use DMS\Authorizators\DocumentBulkActionAuthorizator;
use DMS\Authorizators\MetadataAuthorizator;
use DMS\Authorizators\PanelAuthorizator;
use DMS\Authorizators\RibbonAuthorizator;
use DMS\Components\ExternalEnumComponent;
use DMS\Components\NotificationComponent;
use DMS\Components\ProcessComponent;
use DMS\Components\RibbonComponent;
use DMS\Components\SharingComponent;
use DMS\Components\WidgetComponent;
use DMS\Constants\CacheCategories;
use DMS\Constants\FlashMessageTypes;
use DMS\Core\Logger\Logger;
use DMS\Core\FileManager;
use DMS\Helpers\ArrayStringHelper;
use DMS\Models\DocumentCommentModel;
use DMS\Models\DocumentModel;
use DMS\Models\FilterModel;
use DMS\Models\FolderModel;
use DMS\Models\GroupModel;
use DMS\Models\GroupRightModel;
use DMS\Models\GroupUserModel;
use DMS\Models\MailModel;
use DMS\Models\MetadataModel;
use DMS\Models\NotificationModel;
use DMS\Models\ProcessCommentModel;
use DMS\Models\ProcessModel;
use DMS\Models\RibbonModel;
use DMS\Models\RibbonRightsModel;
use DMS\Models\ServiceModel;
use DMS\Models\TableModel;
use DMS\Models\UserModel;
use DMS\Models\UserRightModel;
use DMS\Models\WidgetModel;
use DMS\Modules\IPresenter;
use DMS\Panels\Panels;
use DMS\Repositories\DocumentCommentRepository;
use DMS\Repositories\DocumentRepository;

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
    public const URL_LOGOUT_PAGE = 'UserModule:UserLogout:logoutUser';

    public const SYSTEM_VERSION_MAJOR = 1;
    public const SYSTEM_VERSION_MINOR = 7;
    public const SYSTEM_VERSION_PATCH = 0;
    public const SYSTEM_VERSION_PATCH_DISPLAY = false;

    public const SYSTEM_IS_BETA = false;
    public const SYSTEM_DEBUG = false && self::SYSTEM_IS_BETA;
    public const SYSTEM_VERSION = self::SYSTEM_VERSION_MAJOR . '.' . self::SYSTEM_VERSION_MINOR . (self::SYSTEM_VERSION_PATCH_DISPLAY ? ('.' . self::SYSTEM_VERSION_PATCH) : '') . (self::SYSTEM_IS_BETA ? '_beta' : '');
    public const SYSTEM_BUILD_DATE = self::SYSTEM_IS_BETA ? '- (This is beta version)' : '2023/01/17';

    //private array $cfg;
    public ?string $currentUrl;
    
    public IModule $currentModule;
    public IPresenter $currentPresenter;
    public string $currentAction;
    public ?int $currentIdRibbon;

    public array $pageList;
    public array $missingUrlValues;

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
    public MailModel $mailModel;
    public RibbonModel $ribbonModel;
    public RibbonRightsModel $ribbonRightsModel;
    public FilterModel $filterModel;

    public PanelAuthorizator $panelAuthorizator;
    public BulkActionAuthorizator $bulkActionAuthorizator;
    public DocumentAuthorizator $documentAuthorizator;
    public ActionAuthorizator $actionAuthorizator;
    public MetadataAuthorizator $metadataAuthorizator;
    public DocumentBulkActionAuthorizator $documentBulkActionAuthorizator;
    public RibbonAuthorizator $ribbonAuthorizator;

    public ProcessComponent $processComponent;
    public WidgetComponent $widgetComponent;
    public SharingComponent $sharingComponent;
    public NotificationComponent $notificationComponent;
    public ExternalEnumComponent $externalEnumComponent;
    public RibbonComponent $ribbonComponent;

    public DocumentCommentRepository $documentCommentRepository;
    public DocumentRepository $documentRepository;

    public MailManager $mailManager;

    private array $models;
    private array $modules;
    private ?string $pageContent;
    private string $baseDir;
    private ?string $flashMessage;

    private Database $conn;

    /**
     * This is the application class constructor. Here are all other classes constructed and assigned to their respective variables.
     * 
     * @param array $cfg The application configuration file contents
     */
    public function __construct(string $baseDir = '', bool $install = true) {
        //$this->cfg = $cfg;
        $this->baseDir = $baseDir;

        $this->currentUrl = null;
        $this->modules = [];
        $this->pageContent = null;
        $this->user = null;
        $this->flashMessage = null;
        $this->pageList = [];
        $this->missingUrlValues = [];
        $this->currentIdRibbon = null;

        $this->fileManager = new FileManager($this->baseDir . AppConfiguration::getLogDir(), $this->baseDir . AppConfiguration::getCacheDir());
        $this->logger = new Logger($this->fileManager);
        $this->conn = new Database(AppConfiguration::getDbServer(), AppConfiguration::getDbUser(), AppConfiguration::getDbPass(), AppConfiguration::getDbName(), $this->logger);

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
        $this->mailModel = new MailModel($this->conn, $this->logger);
        $this->ribbonModel = new RibbonModel($this->conn, $this->logger);
        $this->ribbonRightsModel = new RibbonRightsModel($this->conn, $this->logger);
        $this->filterModel = new FilterModel($this->conn, $this->logger);
        
        $this->models = array(
            'userModel' => $this->userModel,
            'userRightModel' => $this->userRightModel,
            'documentModel' => $this->documentModel,
            'groupModel' => $this->groupModel,
            'groupUserModel' => $this->groupUserModel,
            'processModel' => $this->processModel,
            'groupRightModel' => $this->groupRightModel,
            'metadataModel' => $this->metadataModel,
            'tableModel' => $this->tableModel,
            'folderModel' => $this->folderModel,
            'serviceModel' => $this->serviceModel,
            'documentCommentModel' => $this->documentCommentModel,
            'processCommentModel' => $this->processCommentModel,
            'widgetModel' => $this->widgetModel,
            'notificationModel' => $this->notificationModel,
            'mailModel' => $this->mailModel,
            'ribbonModel' => $this->ribbonModel,
            'filterModel' => $this->filterModel
        );

        $this->panelAuthorizator = new PanelAuthorizator($this->conn, $this->logger, $this->userRightModel, $this->groupUserModel, $this->groupRightModel, $this->user);
        $this->bulkActionAuthorizator = new BulkActionAuthorizator($this->conn, $this->logger, $this->userRightModel, $this->groupUserModel, $this->groupRightModel, $this->user);
        $this->actionAuthorizator = new ActionAuthorizator($this->conn, $this->logger, $this->userRightModel, $this->groupUserModel, $this->groupRightModel, $this->user);
        $this->metadataAuthorizator = new MetadataAuthorizator($this->conn, $this->logger, $this->user, $this->userModel, $this->groupUserModel);
        $this->ribbonAuthorizator = new RibbonAuthorizator($this->conn, $this->logger, $this->user, $this->ribbonModel, $this->ribbonRightsModel, $this->groupUserModel);
        
        $sessionDestroyed = false;
        if($install) {
            $this->installDb($sessionDestroyed);
        }
        
        $this->fsManager = new FileStorageManager($this->baseDir . AppConfiguration::getFileDir(), $this->fileManager, $this->logger);
        $this->mailManager = new MailManager();
        
        $serviceManagerCacheManager = new CacheManager(AppConfiguration::getSerializeCache(), CacheCategories::SERVICE_CONFIG);
        
        $this->notificationComponent = new NotificationComponent($this->conn, $this->logger, $this->notificationModel);
        $this->processComponent = new ProcessComponent($this->conn, $this->logger, $this->processModel, $this->groupModel, $this->groupUserModel, $this->documentModel, $this->notificationComponent, $this->processCommentModel);
        $this->widgetComponent = new WidgetComponent($this->conn, $this->logger, $this->documentModel, $this->processModel, $this->mailModel);
        $this->sharingComponent = new SharingComponent($this->conn, $this->logger, $this->documentModel);
        $this->ribbonComponent = new RibbonComponent($this->conn, $this->logger, $this->ribbonModel, $this->ribbonAuthorizator);
        
        $this->documentAuthorizator = new DocumentAuthorizator($this->conn, $this->logger, $this->documentModel, $this->userModel, $this->processModel, $this->user, $this->processComponent);
        $this->documentBulkActionAuthorizator = new DocumentBulkActionAuthorizator($this->conn, $this->logger, $this->user, $this->documentAuthorizator, $this->bulkActionAuthorizator);
        
        $this->serviceManager = new ServiceManager($this->logger, $this->serviceModel, $this->fsManager, $this->documentModel, $serviceManagerCacheManager, $this->documentAuthorizator, $this->processComponent, $this->userModel, $this->groupUserModel, $this->mailModel, $this->mailManager, $this->notificationModel);

        $this->documentCommentRepository = new DocumentCommentRepository($this->conn, $this->logger, $this->documentCommentModel, $this->documentModel);
        $this->documentRepository = new DocumentRepository($this->conn, $this->logger, $this->documentModel, $this->documentAuthorizator);

        $this->externalEnumComponent = new ExternalEnumComponent($this->models);

        if($sessionDestroyed) {
            CacheManager::invalidateAllCache();
            $this->redirect(self::URL_LOGIN_PAGE);
        }
    }

    /**
     * Redirects the application page to different page using constructed URL that is based on passed parameters.
     * 
     * @param string $url The default page URL
     * @param array $params All other parameters that should be passed to the presenter
     */
    public function redirect(string $url, array $params = array(), string $hashtag = '') {
        $page = '?';

        $newParams = array('page' => $url);

        foreach($params as $k => $v) {
            if($k == 'page') continue;

            $newParams[$k] = $v;
        }

        if(!array_key_exists('id_ribbon', $newParams) && $url != self::URL_LOGIN_PAGE) {
            if($this->currentIdRibbon != null) {
                //$newParams['id_ribbon'] = $this->currentIdRibbon;
            } else if(isset($_SESSION['id_current_ribbon'])) {
                $newParams['id_ribbon'] = $this->currentIdRibbon;
            }
        }

        if($url != self::URL_LOGIN_PAGE) {
            if(array_key_exists('id_current_ribbon', $_SESSION)) {
                unset($_SESSION['id_current_ribbon']);
            }
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

        if($hashtag != '') {
            if(!str_contains($hashtag, '#')) {
                $hashtag = '#' . $hashtag;
            }
        }

        $page .= $hashtag;

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
        $subpanel = $this->renderSubpanel();

        // --- TOPPANEL ---

        if(is_null($this->currentUrl)) {
            die('Current URL is null!');
        }

        // Module:Presenter:action

        $parts = explode(':', $this->currentUrl);
        $module = $parts[0];
        $presenter = $parts[1];
        $action = $parts[2];

        // Get current action
        $this->currentAction = $parts[2];

        // Get module
        if(array_key_exists($module, $this->modules)) {
            $this->currentModule = $module = $this->modules[$module];
        } else {
            die('Module does not exist!');
        }

        // Get presenter
        $presenter = $module->getPresenterByName($presenter);

        if($presenter === NULL) {
            die('Presenter does not exist');
        }

        $this->currentPresenter = $presenter;
        if(!is_null($presenter)) {
            $module->setPresenter($presenter);
        } else {
            die('Presenter does not exist');
        }

        // User is allowed to visit specific pages before logging in
        if($this->currentPresenter->allowWhenLoginProcess === false && isset($_SESSION['login_in_process'])) {
            $this->flashMessage('You must login first!', 'warn');
            $this->redirect(self::URL_LOGIN_PAGE);
        }

        // Load page body
        $pageBody = $module->currentPresenter->performAction($action);

        // --- PAGE CONTENT CREATION ---

        $this->pageContent = '';

        if($presenter::DRAW_TOPPANEL) {
            $this->pageContent .= $toppanel;
        }

        if(!is_null($subpanel)) {
            $this->pageContent .= $subpanel;
        }

        $this->renderFlashMessage();

        $this->pageContent .= $pageBody;

        // --- END OF PAGE CONTENT CREATION ---

        if(str_contains($action, 'show')) {
            $this->clearFlashMessage();
        }
    }

    public function renderFlashMessage() {
        if($this->flashMessage != null) {
            $this->pageContent .= $this->flashMessage;
            return;
        }

        $cm = CacheManager::getTemporaryObject(CacheCategories::FLASH_MESSAGES);

        $valFromCache = $cm->loadFlashMessage();

        $createCode = function($message, $type, $index) {
            $code = '<div id="flash-message-' . $index . '" class="' . $type . '">';
            $code .= '<div class="row">';
            $code .= '<div class="col-md">';
            $code .= $message;
            $code .= '</div>';
            $code .= '<div class="col-md" id="right">';
            $code .= '<a style="cursor: pointer" onclick="hideFlashMessage(\'' . $index . '\')">x</a>';
            $code .= '</div>';
            $code .= '</div>';
            $code .= '</div>';

            return $code;
        };

        if(!is_null($valFromCache)) {
            $this->flashMessage = '';

            $i = 0;
            foreach($valFromCache as $msg) {
                $message = $msg['message'];
                $type = $msg['type'];

                $this->flashMessage .= $createCode($message, $type, $i);

                $i++;
            }

            $this->pageContent .= $this->flashMessage;
        }

        return;
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

    public function renderSubpanel() {
        $panel = Panels::createSubpanel();

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
     * Flashes a message to the user
     * 
     * @param string $message Message text
     * @param string $type Message type (options defined in DMS\Constants\FlashMessageTypes)
     */
    public function flashMessage(string $message, string $type = FlashMessageTypes::INFO) {
        $cm = CacheManager::getTemporaryObject(CacheCategories::FLASH_MESSAGES);
        $cm->saveFlashMessage(array('message' => $message, 'type' => $type));
    }

    /**
     * Clears a flash message
     * 
     * @param bool $clearFromSession If the flash message should be removed entirely
     */
    public function clearFlashMessage(bool $clearFromSession = true) {
        $this->flashMessage = null;

        $cm = CacheManager::getTemporaryObject(CacheCategories::FLASH_MESSAGES);
        $cm->invalidateCache();
    }

    /**
     * Returns the grid size config parameter as defined in the config file
     * 
     * @return int Grid size
     */
    public function getGridSize() {
        return AppConfiguration::getGridSize();
    }

    public function getGridUseAjax() {
        return AppConfiguration::getGridUseAjax();
    }

    /**
     * Loads a list of pages available to be set as default. 
     * Nothing is returned because it is saved to cache.
     */
    public function loadPages() {
        $pcm = CacheManager::getTemporaryObject(CacheCategories::PAGES);

        $cachePages = $pcm->loadStringsFromCache();

        if(!is_null($cachePages) || $cachePages === FALSE) {
            $this->pageList = $cachePages;
        } else {
            foreach($this->modules as $module) {
                if(in_array($module->getName(), array('AnonymModule'))) continue;
    
                foreach($module->getPresenters() as $presenter) {
                    foreach($presenter->getActions() as $realAction => $action) {
                        $page = $module->getName() . ':' . $presenter->getName() . ':' . $action;
                        $realPage = $module->getName() . ':' . $presenter->getName() . ':' . $realAction;

                        $this->pageList[$realPage] = $page;
                    }
                }
            }

            $pcm->saveArrayToCache($this->pageList);
        }
    }

    /**
     * Checks is passed parameters exist in the global variables $_POST and $_GET. If one of the passed is missing it returns false, otherwise true.
     * 
     * @param array $values Values to be checked
     * @return bool True if all exist or false if one or more do not exist
     */
    public function isset(...$values) {
        $present = true;

        foreach($values as $value) {
            if(!isset($_POST[$value]) && !isset($_GET[$value])) {
                $this->missingUrlValues[] = $value;
                $present = false;
            }
        }

        return $present;
    }

    /**
     * Flashes a message to the user that one of given values is missing in the $_GET or $_POST global variables.
     * 
     * @param array $values Values to be checked
     * @param bool $redirect True if the page should be redirected automatically
     * @param array|null $redirectUrl The URL where should the page redirect to
     */
    public function flashMessageIfNotIsset(array $values, bool $redirect = true, ?array $redirectUrl = []) {
        $present = true;

        foreach($values as $value) {
            if(!isset($_POST[$value]) && !isset($_GET[$value])) {
                $this->missingUrlValues[] = $value;
                $present = false;
            }
        }

        if(!$present) {
            $this->flashMessage('These values: ' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($this->missingUrlValues, ',') . ' are missing!', 'error');
            
            if($redirect) {
                if(is_null($redirectUrl) || empty($redirectUrl)) {
                    $this->redirect(self::URL_HOME_PAGE);
                } else {
                    $this->redirect($redirectUrl['page'], $redirectUrl);
                }
            }
        }
    }

    /**
     * Performs the initial database installation.
     * After installing, it creates a file that shows whether the database has been installed or not.
     */
    private function installDb(bool &$sessionDestroyed) {
        if(!file_exists('app/core/install')) {
            $conn = $this->conn;

            $this->logger->logFunction(function() use ($conn) {
                $conn->installer->install();
            }, __METHOD__);

            file_put_contents('app/core/install', 'installed');

            $sessionDestroyed = session_destroy();
        }
    }
}

?>