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
            '$METADATA_GRID$' => $this->internalCreateValuesGrid($idMetadata, $metadata->getIsSystem())
        );

        if($app->metadataAuthorizator->canUserEditMetadataValues($app->user->getId(), $idMetadata) && !$metadata->getIsSystem()) {
            $data['$NEW_ENTITY_LINK$'] = '<div class="row"><div class="col-md" id="right">' . LinkBuilder::createAdvLink(array('page' => 'UserModule:Metadata:showNewValueForm', 'id_metadata' => $idMetadata), 'Create new value') . '</div></div>';
        } else {
            $data['$NEW_ENTITY_LINK$'] = '';
        }

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

    protected function showUserRights() {
        global $app;

        $idMetadata = htmlspecialchars($_GET['id_metadata']);
        $metadata = $app->metadataModel->getMetadataById($idMetadata);

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/metadata/metadata-rights-grid.html');

        $data = array(
            '$PAGE_TITLE$' => 'Metadata <i>' . $metadata->getName() . '</i> user rights',
            '$METADATA_RIGHTS_GRID$' => $this->internalCreateRightsGrid($idMetadata)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function updateRight() {
        global $app;

        $idMetadata = htmlspecialchars($_GET['id_metadata']);
        $idUser = htmlspecialchars($_GET['id_user']);
        $name = htmlspecialchars($_GET['name']);
        $action = htmlspecialchars($_GET['action']);

        switch($action) {
            case 'enable':
                $app->userRightModel->enableRight($idUser, $idMetadata, $name);
                break;

            case 'disable':
                $app->userRightModel->disableRight($idUser, $idMetadata, $name);
                break;
        }

        $app->redirect('UserModule:Metadata:showUserRights', array('id_metadata' => $idMetadata));
    }

    private function internalCreateRightsGrid(int $idMetadata) {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $headers = array(
            'User',
            'View',
            'Edit',
            'View values',
            'Edit values'
        );

        $headerRow = null;

        $users = $app->userModel->getAllUsers();

        if(empty($users)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($users as $user) {
                $idUser = $user->getId();

                if(is_null($headerRow)) {
                    $row = $tb->createRow();

                    foreach($headers as $header) {
                        $col = $tb->createCol()->setText($header)
                                               ->setBold();

                        $row->addCol($col);
                    }

                    $headerRow = $row;

                    $tb->addRow($row);
                }

                $userRow = $tb->createRow();

                $rights = $app->userRightModel->getMetadataRights($idUser, $idMetadata);

                $enableLink = function (string $name) use ($idMetadata, $idUser) {
                    $link = LinkBuilder::createAdvLink(array('page' => 'UserModule:Metadata:updateRight', 'id_metadata' => $idMetadata, 'name' => $name, 'id_user' => $idUser, 'action' => 'enable'), 'No', 'general-link', 'color: red');
                    return $link;
                };

                $disableLink = function (string $name) use ($idMetadata, $idUser) {
                    $link = LinkBuilder::createAdvLink(array('page' => 'UserModule:Metadata:updateRight', 'id_metadata' => $idMetadata, 'name' => $name, 'id_user' => $idUser, 'action' => 'disable'), 'Yes', 'general-link', 'color: green');
                    return $link;
                };

                $data = array(
                    $user->getFullname(),
                    $rights['view'] ? $disableLink('view') : $enableLink('view'),
                    $rights['edit'] ? $disableLink('edit') : $enableLink('edit'),
                    $rights['view_values'] ? $disableLink('view_values') : $enableLink('view_values'),
                    $rights['edit_values'] ? $disableLink('edit_values') : $enableLink('edit_values')
                );

                foreach($data as $d) {
                    $userRow->addCol($tb->createCol()->setText($d));
                }

                $tb->addRow($userRow);
            }
        }

        return $tb->build();
    }

    private function internalCreateNewValueForm(int $idMetadata) {
        $fb = FormBuilder::getTemporaryObject();

        $fb ->setMethod('POST')->setAction('?page=UserModule:Metadata:createNewValue&id_metadata=' . $idMetadata)

            ->addElement($fb->createLabel()->setText('Text')->setFor('name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->require())

            ->addElement($fb->createLabel()->setText('Database value')->setFor('value'))
            ->addElement($fb->createInput()->setType('text')->setName('value')->require())

            ->addElement($fb->createSubmit('Create'))
        ;

        return $fb->build();
    }

    private function internalCreateValuesGrid(int $id, bool $isSystem = false) {
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
                $actionLinks = array('new' => '-');

                if($app->metadataAuthorizator->canUserEditMetadataValues($app->user->getId(), $id) && !$isSystem) {
                    $actionLinks['new'] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Metadata:deleteValue', 'id_metadata' => $id, 'id_metadata_value' => $v->getId()), 'Delete');
                }

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