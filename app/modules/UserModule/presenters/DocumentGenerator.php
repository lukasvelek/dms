<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\FlashMessageTypes;
use DMS\Core\AppConfiguration;
use DMS\Modules\APresenter;

class DocumentGenerator extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('DocumentGenerator', 'Document generator');

        $this->getActionNamesFromClass($this);
    }

    protected function showForm() {
        global $app;

        if(!AppConfiguration::getIsDebug()) {
            $app->flashMessage('Debug is not enabled!', FlashMessageTypes::ERROR);
            $app->redirect('HomePage:showHomepage');
        }

        if(!$app->actionAuthorizator->canUseDocumentGenerator()) {
            $app->flashMessage('You are not authorized to use document generator.', 'error');
            $app->redirect('Documents:showAll');
        }

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/document-generator.html');

        $data = array(
            '$PAGE_TITLE$' => 'Document generator',
            '$PAGE_CONTENT$' => $this->internalCreateForm(AppConfiguration::getIsDebug())
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateForm(bool $isDebug) {
        $code = '
            <label for="count">Count</label>
            <input type="number" id="count" name="count" min="1">
            <br>
            <br>
            <label for="id_folder">ID folder</label>
            <input type="number" id="id_folder" name="id_folder">
            <br>
            <br>
            <button id="submitBtn" type="button" onclick="generateDocuments(\'' . ($isDebug ? 1 : 0) . '\')">Submit</button>
        ';

        return $code;
    }
}

?>