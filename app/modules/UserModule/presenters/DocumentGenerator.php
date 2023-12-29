<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\FlashMessageTypes;
use DMS\Constants\Groups;
use DMS\Core\CypherManager;
use DMS\Core\TemplateManager;
use DMS\Entities\Document;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;
use DMS\UI\FormBuilder\FormBuilder;

class DocumentGenerator extends APresenter {
    private string $name;
    private TemplateManager $templateManager;
    private IModule $module;

    public const DRAW_TOPPANEL = true;

    public function __construct() {
        $this->name = 'DocumentGenerator';

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

    protected function showForm() {
        global $app;

        if(!$app::SYSTEM_DEBUG) {
            $app->flashMessage('Debug is not enabled!', FlashMessageTypes::ERROR);
            $app->redirect('UserModule:HomePage:showHomepage');
        }

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/document-generator.html');

        $data = array(
            '$PAGE_TITLE$' => 'Document generator',
            '$PAGE_CONTENT$' => $this->internalCreateForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateForm() {
        $code = '
            <label for="count">Count</label>
            <input type="number" id="count" name="count" min="1">
            <br>
            <br>
            <label for="id_folder">ID folder</label>
            <input type="number" id="id_folder" name="id_folder">
            <br>
            <br>
            <button type="button" onclick="generateDocuments()">Submit</button>
        ';

        return $code;
    }
}

?>