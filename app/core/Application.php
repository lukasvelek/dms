<?php

namespace DMS\Core;

use \DMS\Entities\User;
use \DMS\Modules\IModule;
use \DMS\App\Core\DB\Database;
use \DMS\Core\Logger\Logger;
use \DMS\Core\FileManager;

class Application {
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
    private $currentUrl;

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

    public function __construct(array $cfg) {
        $this->cfg = $cfg;

        $this->currentUrl = null;
        $this->modules = array();

        $this->fileManager = new FileManager($this->cfg['log_dir'], $this->cfg['cache_dir']);
        $this->logger = new Logger($this->fileManager);
        $this->conn = new Database($this->cfg['db_server'], $this->cfg['db_user'], $this->cfg['db_pass'], $this->cfg['db_name'], $this->logger);
    }

    public function redirect(string $url) {
        $this->currentUrl = $url;

        $this->renderPage();
    }

    public function showPage() {
        return $this->pageContent;
    }

    public function renderPage() {
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
        $this->pageContent = $module->currentPresenter->performAction($action);
    }

    public function registerModule(IModule $module) {
        $this->modules[$module->getName()] = $module;
    }
}

?>