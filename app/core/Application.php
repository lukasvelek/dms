<?php

namespace DMS\Core;

use \DMS\Entities\User;
use \DMS\Modules\IModule;
use \DMS\Core\DB\Database;
use DMS\Authenticators\UserAuthenticator;
use DMS\Authorizators\ActionAuthorizator;
use DMS\Authorizators\BulkActionAuthorizator;
use DMS\Authorizators\DocumentAuthorizator;
use DMS\Authorizators\MetadataAuthorizator;
use DMS\Authorizators\PanelAuthorizator;
use DMS\Components\ProcessComponent;
use \DMS\Core\Logger\Logger;
use \DMS\Core\FileManager;
use DMS\Models\DocumentModel;
use DMS\Models\FolderModel;
use DMS\Models\GroupModel;
use DMS\Models\GroupRightModel;
use DMS\Models\GroupUserModel;
use DMS\Models\MetadataModel;
use DMS\Models\ProcessModel;
use DMS\Models\TableModel;
use DMS\Models\UserModel;
use DMS\Models\UserRightModel;
use DMS\Panels\Panels;

class Application {
    public const URL_LOGIN_PAGE = 'AnonymModule:LoginPage:showForm';
    public const URL_HOME_PAGE = 'UserModule:HomePage:showHomepage';
    public const URL_SETTINGS_PAGE = 'UserModule:Settings:showDashboard';
    public const URL_DOCUMENTS_PAGE = 'UserModule:Documents:showAll';
    public const URL_PROCESSES_PAGE = 'UserModule:Processes:showAll';

    public array $cfg;
    public ?string $currentUrl;

    public ?User $user;
    public Logger $logger;
    public FileManager $fileManager;
    
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

    public PanelAuthorizator $panelAuthorizator;
    public BulkActionAuthorizator $bulkActionAuthorizator;
    public DocumentAuthorizator $documentAuthorizator;
    public ActionAuthorizator $actionAuthorizator;
    public MetadataAuthorizator $metadataAuthorizator;

    public ProcessComponent $processComponent;

    private array $modules;
    private ?string $pageContent;

    private Database $conn;

    public function __construct(array $cfg) {
        $this->cfg = $cfg;

        $this->currentUrl = null;
        $this->modules = array();
        $this->pageContent = null;
        $this->user = null;

        $this->fileManager = new FileManager($this->cfg['log_dir'], $this->cfg['cache_dir']);
        $this->logger = new Logger($this->fileManager);
        $this->conn = new Database($this->cfg['db_server'], $this->cfg['db_user'], $this->cfg['db_pass'], $this->cfg['db_name'], $this->logger);

        $this->userAuthenticator = new UserAuthenticator($this->conn, $this->logger);

        $this->userModel = new UserModel($this->conn, $this->logger);
        $this->userRightModel = new UserRightModel($this->conn, $this->logger);
        $this->documentModel = new DocumentModel($this->conn, $this->logger);
        $this->groupModel = new GroupModel($this->conn, $this->logger);
        $this->groupUserModel = new GroupUserModel($this->conn, $this->logger);
        $this->processModel = new ProcessModel($this->conn, $this->logger);
        $this->groupRightModel = new GroupRightModel($this->conn, $this->logger);
        $this->metadataModel = new MetadataModel($this->conn, $this->logger);
        $this->tableModel = new TableModel($this->conn, $this->logger);
        $this->folderModel = new FolderModel($this->conn, $this->logger);

        $this->panelAuthorizator = new PanelAuthorizator($this->conn, $this->logger);
        $this->bulkActionAuthorizator = new BulkActionAuthorizator($this->conn, $this->logger);
        $this->documentAuthorizator = new DocumentAuthorizator($this->conn, $this->logger);
        $this->actionAuthorizator = new ActionAuthorizator($this->conn, $this->logger);
        $this->metadataAuthorizator= new MetadataAuthorizator($this->conn, $this->logger);

        $this->processComponent = new ProcessComponent($this->conn, $this->logger);

        $this->installDb();

        //$this->conn->installer->updateDefaultUserPanelRights();
    }

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

    public function showPage() {
        if(is_null($this->pageContent)) {
            $this->renderPage();
        }

        echo $this->pageContent;
    }

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

    public function renderToppanel() {
        $panel = Panels::createTopPanel();

        return $panel;
    }

    public function registerModule(IModule $module) {
        $this->modules[$module->getName()] = $module;
    }

    public function getComponent(string $name) {
        if(isset($this->$name)) {
            return $this->$name;
        }
    }

    public function setCurrentUser(User $user) {
        $this->user = $user;
    }

    public function getConn() {
        return $this->conn;
    }

    private function installDb() {
        if(!file_exists('app/core/install')) {
            $this->conn->installer->install();

            file_put_contents('app/core/install', 'installed');
        }
    }
}

?>