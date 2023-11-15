<?php

namespace DMS\Modules\UserModule;

use DMS\Core\TemplateManager;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;

class UserLogout extends APresenter {
    private string $name;
    private TemplateManager $templateManager;
    private IModule $module;

    public const DRAW_TOPPANEL = true;

    public function __construct() {
        $this->name = 'UserLogout';

        $this->templateManager = TemplateManager::getTemporaryObject();
    }

    public function setModule(IModule $module) {
        $this->module = $module;
    }

    public function getModule() {
        return $this->module;
    }

    public function getName() {
        return $this->name;
    }

    protected function logoutUser() {
        global $app;
        if($app->userAuthenticator->logoutCurrentUser()) {
            $app->redirect($app::URL_LOGIN_PAGE);
        }
    }
}

?>