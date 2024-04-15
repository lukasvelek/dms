<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\FlashMessageTypes;
use DMS\Core\AppConfiguration;
use DMS\Modules\APresenter;
use DMS\UI\LinkBuilder;

class DocumentGeneratorPresenter extends APresenter {
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
            '$PAGE_CONTENT$' => $this->internalCreateForm(AppConfiguration::getIsDebug()),
            '$LINKS$' => []
        );

        $data['$LINKS$'][] = LinkBuilder::createAdvLink(['page' => 'Settings:showSystem'], '&larr;');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateForm(bool $isDebug) {
        global $app;

        $options = '<option value="0">Main folder</option>';

        $dbFolders = $app->folderModel->getAllFolders();

        foreach($dbFolders as $folder) {
            $options .= '<option value="' . $folder->getId() . '">' . $folder->getName() . '</option>';
        }

        $code = '
            <label for="count">Count</label>
            <input type="number" id="count" name="count" min="1">
            <br>
            <br>
            <label for="id_folder">ID folder</label>
            <!--<input type="number" id="id_folder" name="id_folder">-->
            <select name="id_folder" id="id_folder">
                ' . $options .  '
            </select>
            <br>
            <br>
            <button id="submitBtn" type="button" onclick="generateDocuments(\'' . ($isDebug ? 1 : 0) . '\')">Submit</button>
        ';

        return $code;
    }
}

?>