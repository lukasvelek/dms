<?php

namespace DMS\Core;

use \DMS\Entities\User;
use \DMS\Modules\IModule;
use \DMS\Core\DB\Database;
use DMS\Authenticators\UserAuthenticator;
use DMS\Authorizators\PanelAuthorizator;
use \DMS\Core\Logger\Logger;
use \DMS\Core\FileManager;
use DMS\Models\UserModel;
use DMS\Models\UserRightModel;
use DMS\Panels\Panels;

class Application {
    public const URL_LOGIN_PAGE = 'AnonymModule:LoginPage:showForm';
    public const URL_HOME_PAGE = 'UserModule:HomePage:showHomepage';
    public const URL_SETTINGS_PAGE = 'UserModule:Settings:showDashboard';

    /**
     * @var array
     */
    public $cfg;

    /**
     * @var User
     */
    public $user;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var FileManager
     */
    public $fileManager;

    /**
     * @var string
     */
    public $currentUrl;

    /**
     * @var array<IModule>
     */
    private $modules;

    /**
     * @var string
     */
    private $pageContent;

    /**
     * @var Database
     */
    private $conn;

    /**
     * @var UserAuthenticator
     */
    public $userAuthenticator;

    /**
     * @var UserModel
     */
    public $userModel;

    /**
     * @var UserRightModel
     */
    public $userRightModel;

    /**
     * @var panelAuthorizator
     */
    public $panelAuthorizator;

    public function __construct(array $cfg) {
        $this->cfg = $cfg;

        $this->currentUrl = null;
        $this->modules = array();
        $this->pageContent = null;

        $this->fileManager = new FileManager($this->cfg['log_dir'], $this->cfg['cache_dir']);
        $this->logger = new Logger($this->fileManager);
        $this->conn = new Database($this->cfg['db_server'], $this->cfg['db_user'], $this->cfg['db_pass'], $this->cfg['db_name'], $this->logger);

        $this->userAuthenticator = new UserAuthenticator($this->conn, $this->logger);

        $this->userModel = new UserModel($this->conn, $this->logger);
        $this->userRightModel = new UserRightModel($this->conn, $this->logger);

        $this->panelAuthorizator = new PanelAuthorizator($this->conn, $this->logger);

        $this->installDb();

        $this->conn->installer->updateDefaultUserPanelRights();
    }

    public function redirect(string $url) {
        header('Location: ?page=' . $url);
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
        $module->setPresenter($presenter);

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

    private function installDb() {
        if(!file_exists('app/core/install')) {
            $this->conn->installer->install();

            file_put_contents('app/core/install', 'installed');
        }
    }
}

?>