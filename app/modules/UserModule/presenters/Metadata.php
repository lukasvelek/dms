<?php

namespace DMS\Modules\UserModule;

use DMS\Core\TemplateManager;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

class Metadata extends APresenter {
    private string $name;
    private TemplateManager $templateManager;
    private IModule $module;

    public const DRAW_TOPPANEL = true;

    public function __construct() {
        $this->name = 'Metadata';

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

    protected function showValues() {
        global $app;

        $idMetadata = htmlspecialchars($_GET['id']);
        $metadata = $app->metadataModel->getMetadataById($idMetadata);

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/metadata/metadata-grid.html');

        $data = array(
            '$PAGE_TITLE$' => 'Metadata <i>' . $metadata->getName() . '</i> values',
            '$METADATA_GRID$' => $this->internalCreateValuesGrid($idMetadata),
            '$NEW_ENTITY_LINK$' => '<div class="row"><div class="col-md" id="right">' . LinkBuilder::createAdvLink(array('page' => 'UserModule:Metadata:showNewValueForm', 'id_metadata' => $idMetadata), 'Create new value') . '</div></div>'
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showNewValueForm() {
        global $app;

        $idMetadata = htmlspecialchars($_GET['id_metadata']);

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/metadata/metadata-new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'New value form',
            '$FORM$' => $this->internalCreateNewValueForm($idMetadata)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function createNewValue() {
        global $app;

        $idMetadata = htmlspecialchars($_GET['id_metadata']);
        $name = htmlspecialchars($_POST['name']);
        $value = htmlspecialchars($_POST['value']);

        $app->metadataModel->insertMetadataValueForIdMetadata($idMetadata, $name, $value);

        $app->redirect('UserModule:Metadata:showValues', array('id' => $idMetadata));
    }

    private function internalCreateNewValueForm(int $idMetadata) {
        $fb = FormBuilder::getTemporaryObject();

        $fb ->setMethod('POST')->setAction('?page=UserModule:Metadata:createNewValue&id_metadata=' . $idMetadata)

            ->addElement($fb->createLabel()->setText('Name')->setFor('name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->require())

            ->addElement($fb->createLabel()->setText('Value')->setFor('value'))
            ->addElement($fb->createInput()->setType('text')->setName('value')->require())

            ->addElement($fb->createSubmit('Create'))
        ;

        return $fb->build();
    }

    private function internalCreateValuesGrid(int $id) {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $headers = array(
            'Actions',
            'Name',
            'Value'
        );

        $headerRow = null;

        $values = $app->metadataModel->getAllValuesForIdMetadata($id);

        if(empty($values)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($values as $v) {
                $actionLinks = array(
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:Metadata:deleteValue', 'id_metadata' => $id, 'id_metadata_value' => $v->getId()), 'Delete')
                );

                if(is_null($headerRow)) {
                    $row = $tb->createRow();

                    foreach($headers as $header) {
                        $col = $tb->createCol()->setText($header)
                                               ->setBold();

                        if($headers == 'Actions') {
                            $col->setColspan(count($actionLinks));
                        }

                        $row->addCol($col);
                    }

                    $headerRow = $row;
                    
                    $tb->addRow($row);
                }

                $valueRow = $tb->createRow();

                foreach($actionLinks as $actionLink) {
                    $valueRow->addCol($tb->createCol()->setText($actionLink));
                }

                $valueArray = array(
                    $v->getName(),
                    $v->getValue()
                );

                foreach($valueArray as $va) {
                    $valueRow->addCol($tb->createCol()->setText($va));
                }

                $tb->addRow($valueRow);
            }
        }

        return $tb->build();
    }
}

?>