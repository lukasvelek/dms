<?php

namespace DMS\Modules\UserModule;

use DMS\Core\TemplateManager;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;
use DMS\UI\TableBuilder\TableBuilder;

class SingleDocument extends APresenter {
    private string $name;
    private TemplateManager $templateManager;
    private IModule $module;

    public const DRAW_TOPPANEL = true;

    public function __construct() {
        $this->name = 'SingleDocument';

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

    protected function showInfo() {
        global $app;

        $id = htmlspecialchars($_GET['id']);
        $document = $app->documentModel->getDocumentById($id);

        $tb = TableBuilder::getTemporaryObject();
    }
}

?>