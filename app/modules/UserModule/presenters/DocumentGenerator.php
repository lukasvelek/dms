<?php

namespace DMS\Modules\UserModule;

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

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/document-generator.html');

        $data = array(
            '$PAGE_TITLE$' => 'Document generator',
            '$PAGE_CONTENT$' => $this->internalCreateForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function createDocuments() {
        global $app;

        $count = htmlspecialchars($_POST['count']);
        $idFolder = htmlspecialchars($_POST['id_folder']);

        if($idFolder == '0') {
            $idFolder = null;
        }

        $data = [];
        for($i = 0; $i < $count; $i++) {
            $data[$i] = array(
                'name' => 'DG_' . CypherManager::createCypher(8),
                'id_author' => $app->user->getId(),
                'id_officer' => $app->user->getId(),
                'status' => '1',
                'id_manager' => '2',
                'id_group' => '1',
                'is_deleted' => '0',
                'rank' => 'public',
                'shred_year' => '2023',
                'after_shred_action' => 'showAsShredded',
                'shredding_status' => '5'
            );

            if($idFolder != null) {
                $data[$i]['id_folder'] = $idFolder;
            }
        }

        foreach($data as $index => $d) {
            $app->documentModel->insertNewDocument($d);
        }

        $app->redirect('UserModule:Documents:showAll');
    }

    private function internalCreateForm() {
        /*$fb = new FormBuilder();

        $fb ->setAction('?page=UserModule:DocumentGenerator:createDocuments')->setMethod('POST')
            ->addElement($fb->createLabel()->setFor('count')->setText('Count'))
            ->addElement($fb->createInput()->setType('number')->setName('count')->setMin('1')->setMax('10000'))

            ->addElement($fb->createLabel()->setFor('id_folder')->setText('ID folder'))
            ->addElement($fb->createInput()->setType('number')->setName('id_folder'))

            ->addElement($fb->createSubmit('Generate'));

        return $fb->build();*/

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