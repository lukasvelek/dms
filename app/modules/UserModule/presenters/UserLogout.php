<?php

namespace DMS\Modules\UserModule;

use DMS\Core\TemplateManager;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;

class UserLogout extends APresenter {
    /**
     * @var string
     */
    private $name;

    /**
     * @var DMS\Core\TemplateManager
     */
    private $templateManager;

    /**
     * @var DMS\Modules\IModule
     */
    private $module;

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
        $app->userAuthenticator->logoutCurrentUser();

        $app->redirect($app::URL_LOGIN_PAGE);
    }
}

?>