<?php

namespace App\Core;

use App\Core\Helpers\UIHelper;
use App\UI\AModule;
use App\UI\APresenter;

class Application {
    public array $cfg;

    private ?AModule $currentModule;
    private ?APresenter $currentPresenter;

    private UIHelper $uiHelper;

    public function __construct(array $cfg) {
        $this->cfg = $cfg;

        $this->currentModule = null;

        $this->uiHelper = new UIHelper($this->cfg);
    }

    public function run() {
        // init
        $page = $this->getQueryParam('page');
        $action = $this->getQueryParam('action');

        if($page === null || $action === null) {
            $this->redirect('Error:Error:error');
        }

        $this->getInstances(explode(':', $page)[0], explode(':', $page)[1], $action);

        // auth user
        // redirect if needed
        // create page
        return $this->render();
    }

    public function render() {
        return $this->currentModule->render();
    }

    private function getInstances(string $module, string $presenter, string $action) {
        $this->currentModule = $this->uiHelper->createModuleInstance($module . 'Module');
        $this->currentModule->cfg = $this->cfg;

        $this->currentPresenter = $this->uiHelper->createPresenterInstance($presenter, $this->currentModule);
        $this->currentPresenter->setAction($action);
        $this->currentPresenter->setModuleName($module . 'Module');

        $this->currentModule->setPresenter($this->currentPresenter);
    }

    private function getQueryParam(string $name) {
        $query = $this->getUrlQuery();

        if(isset($query[$name])) {
            return $query[$name];
        } else {
            return null;
        }
    }

    private function getUrlQuery() {
        if(isset($_GET)) {
            return $_GET;
        } else {
            return [];
        }
    }

    public function redirect(string $url, array $params = []) {
        $pageParams = $this->composeUrlPage($url);

        $params = array_merge($params, $pageParams);

        $url = $this->composeUrlQuery($params);

        header('Location: ' . $url);
    }

    private function composeUrlPage(string $url) {
        $parts = explode(':', $url);

        $module = ucfirst($parts[0]);
        $presenter = ucfirst($parts[1]);
        $action = $parts[2];

        // check if all exist
        $this->uiHelper->checkModuleExists($module . 'Module');

        return ['page' => $module . ':' . $presenter, 'action' => $action];
    }

    private function composeUrlQuery(array $params) {
        $url = '?';

        $tmp = [];

        foreach($params as $key => $value) {
            $tmp[] = $key . '=' . $value;
        }

        $url .= implode('&', $tmp);

        return $url;
    }
}

?>