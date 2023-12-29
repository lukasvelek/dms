<?php

namespace DMS\Modules\UserModule;

use DMS\Core\TemplateManager;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;

class AjaxHelper extends APresenter {
    private string $name;
    private TemplateManager $templateManager;
    private IModule $module;

    public const DRAW_TOPPANEL = true;

    public function __construct() {
        $this->name = 'AjaxHelper';

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

    protected function flashMessage() {
        global $app;

        $message = htmlspecialchars($_GET['message']);
        $type = htmlspecialchars($_GET['type']);
        $redirect = htmlspecialchars($_GET['redirect']);

        $toUnset = ['message', 'type', 'redirect', 'page'];

        foreach($toUnset as $tu) {
            unset($_GET[$tu]);
        }

        $special = $_GET;

        $app->flashMessage($message, $type);

        if(!empty($special)) {
            $app->redirect($redirect, $special);
        } else {
            $app->redirect($redirect);
        }
    }
}

?>