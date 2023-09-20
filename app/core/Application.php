<?php

namespace DMS\Core;

use \DMS\Entities\User;
use DMS\Modules\IModule;

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
     * @var string
     */
    private $currentUrl;

    /**
     * @var array<IModule>
     */
    private $modules;

    public function __construct(array $cfg) {
        $this->cfg = $cfg;

        $this->currentUrl = null;
        $this->modules = array();
    }

    public function redirect(string $url) {
        $this->currentUrl = $url;

        return $this->loadPage();
    }

    public function loadPage() {
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
        return $module->currentPresenter->performAction($action);
    }

    public function registerModule(IModule $module) {
        $this->modules[$module->getName()] = $module;
    }
}

?>