<?php

namespace DMS\Modules\UserModule;

use DMS\Entities\MetadataValue;
use DMS\Entities\User;
use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;

class Metadata extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('Metadata');

        $this->getActionNamesFromClass($this);
    }

    protected function setAsDefault() {
        global $app;

        $app->flashMessageIfNotIsset(['id_metadata', 'id_metadata_value']);

        $idMetadata = $this->get('id_metadata');
        $idMetadataValue = $this->get('id_metadata_value');

        $hasDefault = $app->metadataModel->hasMetadataDefaultValue($idMetadata);

        $app->metadataModel->setDefaultMetadataValue($idMetadata, $idMetadataValue);

        if(!is_null($hasDefault)) {
            $app->metadataModel->unsetDefaultMetadataValue($idMetadata, $hasDefault);
        }

        $app->flashMessage('Successfully set default metadata value', 'success');
        $app->redirect('UserModule:Metadata:showValues', array('id' => $idMetadata));
    }

    protected function deleteValue() {
        global $app;

        $app->flashMessageIfNotIsset(['id_metadata', 'id_metadata_value']);

        $idMetadata = $this->get('id_metadata');
        $idMetadataValue = $this->get('id_metadata_value');

        $app->metadataModel->deleteMetadataValueByIdMetadataValue($idMetadataValue);

        $app->flashMessage('Deleted metadata value for metadata #' . $idMetadata, 'warning');
        $app->redirect('UserModule:Metadata:showValues', array('id' => $idMetadata));
    }

    protected function showValues() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);

        $idMetadata = $this->get('id');
        $metadata = $app->metadataModel->getMetadataById($idMetadata);

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/metadata/metadata-grid.html');

        $metadataValues = '';

        $app->logger->logFunction(function() use (&$metadataValues, $idMetadata, $metadata) {
            $metadataValues = $this->internalCreateValuesGrid($idMetadata, $metadata->getIsSystem());
        }, __METHOD__);

        $data = array(
            '$PAGE_TITLE$' => 'Metadata <i>' . $metadata->getTableName() . '.' . $metadata->getName() . '</i> values',
            '$METADATA_GRID$' => $metadataValues
        );

        $newEntityLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Metadata:showNewValueForm', 'id_metadata' => $idMetadata), 'Create new value');
        $backLink = LinkBuilder::createLink('UserModule:Settings:showMetadata', '<-');

        if($app->metadataAuthorizator->canUserEditMetadataValues($app->user->getId(), $idMetadata) && !$metadata->getIsSystem() && ($metadata->getInputType() != 'select_external')) {
            $data['$NEW_ENTITY_LINK$'] = '<div class="row"><div class="col-md" id="right">' . $backLink . '&nbsp;' . $newEntityLink . '</div></div>';
        } else {
            $data['$NEW_ENTITY_LINK$'] = '<div class="row"><div class="col-md" id="right">' . $backLink . '</div></div>';
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showNewValueForm() {
        global $app;

        $app->flashMessageIfNotIsset(['id_metadata']);

        $idMetadata = $this->get('id_metadata');

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/metadata/metadata-new-entity-form.html');

        $backLink = LinkBuilder::createLink('UserModule:Settings:showMetadata', '<-');

        $data = array(
            '$PAGE_TITLE$' => 'New value form',
            '$FORM$' => $this->internalCreateNewValueForm($idMetadata),
            '$LINKS$' => '<div class="row"><div class="col-md" id="right">' . $backLink . '</div></div>'
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function createNewValue() {
        global $app;

        $app->flashMessageIfNotIsset(['id_metadata', 'name', 'value']);

        $idMetadata = $this->get('id_metadata');
        $name = $this->post('name');
        $value = $this->post('value');

        $app->metadataModel->insertMetadataValueForIdMetadata($idMetadata, $name, $value);

        $app->logger->info('Created new value for metadata #' . $idMetadata, __METHOD__);

        $app->redirect('UserModule:Metadata:showValues', array('id' => $idMetadata));
    }

    protected function showUserRights() {
        global $app;

        $app->flashMessageIfNotIsset(['id_metadata']);

        $idMetadata = $this->get('id_metadata');
        $metadata = $app->metadataModel->getMetadataById($idMetadata);

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/metadata/metadata-rights-grid.html');

        $backLink = LinkBuilder::createLink('UserModule:Settings:showMetadata', '<-');

        $data = array(
            '$PAGE_TITLE$' => 'Metadata <i>' . $metadata->getName() . '</i> user rights',
            '$METADATA_RIGHTS_GRID$' => $this->internalCreateRightsGrid($idMetadata),
            '$LINKS$' => '<div class="row"><div class="col-md" id="right">' . $backLink . '</div></div>'
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function updateRight() {
        global $app;

        $app->flashMessageIfNotIsset(['id_metadata', 'id_user', 'name', 'action']);

        $idMetadata = $this->get('id_metadata');
        $idUser = $this->get('id_user');
        $name = $this->get('name');
        $action = $this->get('action');

        switch($action) {
            case 'enable':
                $app->userRightModel->enableRight($idUser, $idMetadata, $name);
                break;

            case 'disable':
                $app->userRightModel->disableRight($idUser, $idMetadata, $name);
                break;
        }

        $app->logger->info('Updated metadata right for user #' . $idUser . ' and metadata #' . $idMetadata, __METHOD__);

        $app->redirect('UserModule:Metadata:showUserRights', array('id_metadata' => $idMetadata));
    }

    private function internalCreateRightsGrid(int $idMetadata) {
        global $app;

        $userModel = $app->userModel;
        $userRightModel = $app->userRightModel;

        $dataSourceCallback = function() use ($userModel) {
            return $userModel->getAllUsers();
        };

        $enableLink = function (string $name, int $idUser) use ($idMetadata) {
            $link = LinkBuilder::createAdvLink(array('page' => 'UserModule:Metadata:updateRight', 'id_metadata' => $idMetadata, 'name' => $name, 'id_user' => $idUser, 'action' => 'enable'), 'No', 'general-link', 'color: red');
            return $link;
        };

        $disableLink = function (string $name, int $idUser) use ($idMetadata) {
            $link = LinkBuilder::createAdvLink(array('page' => 'UserModule:Metadata:updateRight', 'id_metadata' => $idMetadata, 'name' => $name, 'id_user' => $idUser, 'action' => 'disable'), 'Yes', 'general-link', 'color: green');
            return $link;
        };

        $gb = new GridBuilder();

        $gb->addColumns(['user' => 'User', 'view' => 'View', 'edit' => 'Edit', 'viewValues' => 'View values', 'editValues' => 'Edit values']);
        $gb->addOnColumnRender('user', function(User $user) {
            return $user->getFullname();
        });
        $gb->addOnColumnRender('view', function(User $user) use ($idMetadata, $enableLink, $disableLink, $userRightModel) {
            $userRights = $userRightModel->getMetadataRights($user->getId(), $idMetadata);

            $right = 0;
            if(!is_null($userRights)) {
                $right = $userRights['view'];
            }

            return $right ? $disableLink('view', $user->getId()) : $enableLink('view', $user->getId());
        });
        $gb->addOnColumnRender('edit', function(User $user) use ($idMetadata, $enableLink, $disableLink, $userRightModel) {
            $userRights = $userRightModel->getMetadataRights($user->getId(), $idMetadata);

            $right = 0;
            if(!is_null($userRights)) {
                $right = $userRights['edit'];
            }

            return $right ? $disableLink('edit', $user->getId()) : $enableLink('edit', $user->getId());
        });
        $gb->addOnColumnRender('viewValues', function(User $user) use ($idMetadata, $enableLink, $disableLink, $userRightModel) {
            $userRights = $userRightModel->getMetadataRights($user->getId(), $idMetadata);

            $right = 0;
            if(!is_null($userRights)) {
                $right = $userRights['view_values'];
            }

            return $right ? $disableLink('view_values', $user->getId()) : $enableLink('view_values', $user->getId());
        });
        $gb->addOnColumnRender('editValues', function(User $user) use ($idMetadata, $enableLink, $disableLink, $userRightModel) {
            $userRights = $userRightModel->getMetadataRights($user->getId(), $idMetadata);

            $right = 0;
            if(!is_null($userRights)) {
                $right = $userRights['edit_values'];
            }

            return $right ? $disableLink('edit_values', $user->getId()) : $enableLink('edit_values', $user->getId());
        });
        $gb->addDataSourceCallback($dataSourceCallback);

        return $gb->build();
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

        $metadataModel = $app->metadataModel;
        $externalEnumComponent = $app->externalEnumComponent;
        $metadataAuthorizator = $app->metadataAuthorizator;
        $idUser = $app->user->getId();

        $dataSourceCallback = function() use ($metadataModel, $externalEnumComponent, $id) {
            $metadata = $metadataModel->getMetadataById($id);

            if($metadata->getInputType() == 'select_external') {
                $enum = $externalEnumComponent->getEnumByName($metadata->getSelectExternalEnumName());

                $values = [];

                foreach($enum->getValues() as $k => $v) {
                    $values[] = new class($k, $v) {
                        private string $key;
                        private string $value;

                        public function __construct(string $k, string $v) {
                            $this->key = $k;
                            $this->value = $v;
                        }

                        public function getName() {
                            return $this->value;
                        }

                        public function getValue() {
                            return $this->key;
                        }
                    };
                }

                return $values;
            } else {
                $values = $metadataModel->getAllValuesForIdMetadata($id);

                return $values;
            }
        };

        $idsCanEditMetadataValues = $app->metadataAuthorizator->getEditableMatadataValuesForIdUser($idUser);

        $gb = new GridBuilder();

        $gb->addColumns(['name' => 'Name', 'value' => 'Value']);
        $gb->addDataSourceCallback($dataSourceCallback);
        $gb->addAction(function($value) use ($idsCanEditMetadataValues, $id, $isSystem) {
            $actionLink = '-';
            if($value instanceof MetadataValue) {
                if(in_array($id, $idsCanEditMetadataValues) && !$isSystem) {
                    $actionLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Metadata:deleteValue', 'id_metadata' => $id, 'id_metadata_value' => $value->getId()), 'Delete');
                }
            }
            return $actionLink;
        });
        $gb->addAction(function($value) use ($idsCanEditMetadataValues, $id, $isSystem) {
            $actionLink = '-';
            if($value instanceof MetadataValue) {
                if(in_array($id, $idsCanEditMetadataValues) && !$isSystem && !$value->getIsDefault()) {
                    $actionLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Metadata:setAsDefault', 'id_metadata' => $id, 'id_metadata_value' => $value->getId()), 'Set as default');
                }
            }
            return $actionLink;
        });

        return $gb->build();
    }
}

?>