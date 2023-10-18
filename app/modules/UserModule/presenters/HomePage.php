<?php

namespace DMS\Modules\UserModule;

use DMS\Core\TemplateManager;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;

class HomePage extends APresenter {
    private string $name;
    private TemplateManager $templateManager;
    private IModule $module;

    public const DRAW_TOPPANEL = true;

    public function __construct() {
        $this->name = 'HomePage';

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

    protected function showHomePage() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/homepage.html');

        $data = array(
            '$PAGE_TITLE$' => 'Home page'
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }
}

?>