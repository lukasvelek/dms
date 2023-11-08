<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\MetadataInputType;
use DMS\Constants\ServiceMetadata;
use DMS\Constants\UserActionRights;
use DMS\Constants\UserStatus;
use DMS\Core\ScriptLoader;
use DMS\Core\TemplateManager;
use DMS\Entities\Folder;
use DMS\Helpers\ArrayStringHelper;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;
use DMS\Panels\Panels;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

class Settings extends APresenter {
    private string $name;
    private TemplateManager $templateManager;
    private IModule $module;

    public const DRAW_TOPPANEL = true;

    public function __construct() {
        $this->name = 'Settings';

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

    protected function editService() {
        global $app;

        $name = htmlspecialchars($_GET['name']);
        
        $values = $_POST;

        unset($values['name']);
        unset($values['description']);

        foreach($values as $k => $v) {
            $app->serviceModel->updateService($name, $k, $v);
        }

        $app->logger->info('Updated configuration for service \'' . $name . '\'', __METHOD__);

        $app->redirect('UserModule:Settings:showServices');
    }

    protected function editServiceForm() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $name = htmlspecialchars($_GET['name']);

        $data = array(
            '$PAGE_TITLE$' => 'Edit service <i>' . $name . '</i>',
            '$SETTINGS_PANEL$' => Panels::createSettingsPanel(),
            '$FORM$' => $this->internalCreateEditServiceForm($name)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showServices() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-grid.html');

        $data = array(
            '$SETTINGS_PANEL$' => Panels::createSettingsPanel(),
            '$PAGE_TITLE$' => 'Services',
            '$SETTINGS_GRID$' => $this->internalCreateServicesGrid(),
            '$NEW_ENTITY_LINK$' => ''
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function askToRunService() {
        $name = htmlspecialchars($_GET['name']);

        $urlConfirm = array(
            'page' => 'UserModule:Settings:runService',
            'name' => $name
        );

        $urlClose = array(
            'page' => 'UserModule:Settings:showServices'
        );

        $code = ScriptLoader::confirmUser('Do you want to run service ' . $name . '?', $urlConfirm, $urlClose);

        return $code;
    }

    protected function runService() {
        global $app;

        $name = htmlspecialchars($_GET['name']);

        foreach($app->serviceManager->services as $service) {
            if($service->name == $name) {
                $app->logger->info('Running service \'' . $name . '\'', __METHOD__);

                $service->run();
                break;
            }
        }

        $app->redirect('UserModule:Settings:showServices');
    }

    protected function showFolders() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-folders.html');

        $newEntityLink = LinkBuilder::createLink('UserModule:Settings:showNewFolderForm', 'New folder');
        $backLink = '';
        $pageTitle = 'Document folders';

        $idFolder = null;
        if(isset($_GET['id_folder'])) {
            $idFolder = htmlspecialchars($_GET['id_folder']);
            $folder = $app->folderModel->getFolderById($idFolder);

            if(($folder->getNestLevel() + 1) < 6) {
                $newEntityLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Settings:showNewFolderForm', 'id_parent_folder' => $idFolder), 'New folder');
            } else {
                $newEntityLink = '';
            }

            if($folder->getIdParentFolder() != NULL) {
                $backLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Settings:showFolders', 'id_folder' => $folder->getIdParentFolder()), '<-');
            } else {
                $backLink = LinkBuilder::createLink('UserModule:Settings:showFolders', '<-');
            }

            $pageTitle .= ' in <i>' . $folder->getName() . '</i>';
        }

        $data = array(
            '$SETTINGS_PANEL$' => Panels::createSettingsPanel(),
            '$PAGE_TITLE$' => $pageTitle,
            '$LINKS$' => '<div class="row"><div class="col-md" id="right">' . $backLink . '&nbsp;' . $newEntityLink . '</div></div>',
            '$FOLDERS_GRID$' => $this->internalCreateFolderGrid()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showNewFolderForm() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $idParentFolder = null;

        if(isset($_GET['id_parent_folder'])) {
            $idParentFolder = htmlspecialchars($_GET['id_parent_folder']);
        }

        $data = array(
            '$SETTINGS_PANEL$' => Panels::createSettingsPanel(),
            '$PAGE_TITLE$' => 'New document folder form',
            '$FORM$' => $this->internalCreateNewFolderForm($idParentFolder)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function createNewFolder() {
        global $app;

        $data = [];

        $parentFolder = htmlspecialchars($_POST['parent_folder']);
        $nestLevel = 0;

        $data['name'] = htmlspecialchars($_POST['name']);

        $create = true;

        if(isset($_POST['description']) && $_POST['description'] != '') {
            $data['description'] = htmlspecialchars($_POST['description']);
        }

        if($parentFolder == '-1') {
            $parentFolder = null;
        } else {
            $data['id_parent_folder'] = $parentFolder;

            $nestLevelParentFolder = $app->folderModel->getFolderById($parentFolder);

            $nestLevel = $nestLevelParentFolder->getNestLevel() + 1;

            if($nestLevel == 6) {
                $create = false;
            }
        }

        $data['nest_level'] = $nestLevel;

        if($create == true) {
            $app->folderModel->insertNewFolder($data);
        }

        $idFolder = $app->folderModel->getLastInsertedFolder()->getId();
        
        $app->logger->info('Inserted new folder #' . $idFolder, __METHOD__);

        if($parentFolder != '-1') {
            $app->redirect('UserModule:Settings:showFolders', array('id_folder' => $idFolder));
        } else {
            $app->redirect('UserModule:Settings:showFolders');
        }
    }

    protected function showDashboard() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-dashboard.html');

        $data = array(
            '$PAGE_TITLE$' => 'Settings',
            '$SETTINGS_PANEL$' => Panels::createSettingsPanel()
        );

        $widgets = $this->internalDashboardCreateWidgets();

        $data['$WIDGETS$'] = $widgets;

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showUsers() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-grid.html');

        $data = array(
            '$PAGE_TITLE$' => 'Users',
            '$NEW_ENTITY_LINK$' => '',
            '$SETTINGS_GRID$' => $this->internalCreateUsersGrid(),
            '$SETTINGS_PANEL$' => Panels::createSettingsPanel()
        );

        if($app->actionAuthorizator->checkActionRight('create_user')) {
            $data['$NEW_ENTITY_LINK$'] = '<div class="row"><div class="col-md" id="right">' . LinkBuilder::createLink('UserModule:Settings:showNewUserForm', 'New user') . '</div></div>';
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showGroups() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-grid.html');

        $data = array(
            '$PAGE_TITLE$' => 'Groups',
            '$NEW_ENTITY_LINK$' => '',
            '$SETTINGS_GRID$' => $this->internalCreateGroupGrid(),
            '$SETTINGS_PANEL$' => Panels::createSettingsPanel()
        );

        if($app->actionAuthorizator->checkActionRight('create_group')) {
            $data['$NEW_ENTITY_LINK$'] = '<div class="row"><div class="col-md" id="right">' . LinkBuilder::createLink('UserModule:Settings:showNewGroupForm', 'New group') . '</div></div>';
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showMetadata() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-grid.html');

        $data = array(
            '$PAGE_TITLE$' => 'Metadata manager',
            '$SETTINGS_PANEL$' => Panels::createSettingsPanel(),
            '$SETTINGS_GRID$' => $this->internalCreateMetadataGrid()
        );

        if($app->actionAuthorizator->checkActionRight('create_metadata')) {
            $data['$NEW_ENTITY_LINK$'] = '<div class="row"><div class="col-md" id="right">' . LinkBuilder::createLink('UserModule:Settings:showNewMetadataForm', 'New metadata') . '</div></div>';
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showSystem() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-dashboard.html');

        $data = array(
            '$PAGE_TITLE$' => 'System',
            '$SETTINGS_PANEL$' => Panels::createSettingsPanel(),
            '$WIDGETS$' => LinkBuilder::createLink('UserModule:Settings:updateDefaultUserRights', 'Update default user rights')
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function updateDefaultUserRights() {
        global $app;

        $app->getConn()->installer->updateDefaultUserRights();

        $app->redirect('UserModule:Settings:showSystem');
    }

    protected function showNewMetadataForm() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'New metadata form',
            '$SETTINGS_PANEL$' => Panels::createSettingsPanel(),
            '$FORM$' => $this->internalCreateNewMetadataForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showNewUserForm() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $data = array(
            '$SETTINGS_PANEL$' => Panels::createSettingsPanel(),
            '$PAGE_TITLE$' => 'New user form',
            '$FORM$' => $this->internalCreateNewUserForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showNewGroupForm() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $data = array(
            '$SETTINGS_PANEL$' => Panels::createSettingsPanel(),
            '$PAGE_TITLE$' => 'New group form',
            '$FORM$' => $this->internalCreateNewGroupForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function createNewMetadata() {
        global $app;

        $data = [];

        $name = htmlspecialchars($_POST['name']);
        $tableName = htmlspecialchars($_POST['table_name']);
        $length = htmlspecialchars($_POST['length']);
        $inputType = htmlspecialchars($_POST['input_type']);

        $data['name'] = htmlspecialchars($_POST['name']);
        $data['text'] = htmlspecialchars($_POST['text']);
        $data['table_name'] = htmlspecialchars($_POST['table_name']);
        $data['input_type'] = $inputType;

        if($inputType == 'boolean') {
            $length = '2';
        } else if($inputType == 'select') {
            $length = '256';
        } else if($inputType == 'date') {
            $length = '10';
        }

        $data['length'] = $length;

        $app->metadataModel->insertNewMetadata($data);

        $idMetadata = $app->metadataModel->getLastInsertedMetadata()->getId();

        $app->tableModel->addColToTable($tableName, $name, 'VARCHAR', $length);

        $app->userRightModel->insertMetadataRight($app->user->getId(), $idMetadata);

        $app->userRightModel->enableRight($app->user->getId(), $idMetadata, 'view');
        $app->userRightModel->enableRight($app->user->getId(), $idMetadata, 'edit');
        $app->userRightModel->enableRight($app->user->getId(), $idMetadata, 'view_values');
        $app->userRightModel->enableRight($app->user->getId(), $idMetadata, 'edit_values');
        
        $app->logger->info('Created new metadata #' . $idMetadata, __METHOD__);
        $app->logger->info('Enabled right \'view\' for metadata #' . $idMetadata, __METHOD__);
        $app->logger->info('Enabled right \'edit\' for metadata #' . $idMetadata, __METHOD__);
        $app->logger->info('Enabled right \'view_values\' for metadata #' . $idMetadata, __METHOD__);
        $app->logger->info('Enabled right \'edit_values\' for metadata #' . $idMetadata, __METHOD__);

        if($inputType == 'select') {
            $app->redirect('UserModule:Metadata:showValues', array('id' => $idMetadata));
        } else {
            $app->redirect('UserModule:Settings:showMetadata');
        }
    }

    protected function deleteMetadata() {
        global $app;

        $id = htmlspecialchars($_GET['id']);
        $metadata = $app->metadataModel->getMetadataById($id);

        // delete table column
        // delete values
        // delete metadata

        $app->tableModel->removeColFromTable($metadata->getTableName(), $metadata->getName());
        $app->metadataModel->deleteMetadataValues($id);
        $app->metadataModel->deleteMetadata($id);

        $app->logger->info('Deleted metadata #' . $id, __METHOD__);

        $app->redirect('UserModule:Settings:showMetadata');
    }

    protected function createNewGroup() {
        global $app;

        $name = htmlspecialchars($_POST['name']);
        $code = null;

        if(isset($_POST['code'])) {
            $code = htmlspecialchars($_POST['code']);
        }

        $app->groupModel->insertNewGroup($name, $code);
        $idGroup = $app->groupModel->getLastInsertedGroup()->getId();

        $app->logger->info('Created new group #' . $idGroup, __METHOD__);

        $app->groupRightModel->insertActionRightsForIdGroup($idGroup);
        $app->groupRightModel->insertPanelRightsForIdGroup($idGroup);
        $app->groupRightModel->insertBulkActionRightsForIdGroup($idGroup);

        $app->redirect('UserModule:Groups:showUsers', array('id' => $idGroup));
    }

    protected function createNewUser() {
        global $app;

        $data = [];

        $required = array('firstname', 'lastname', 'username');

        foreach($required as $r) {
            $data[$r] = htmlspecialchars($_POST[$r]);
        }

        if(isset($_POST['email']) && !empty($_POST['email'])) {
            $data['email'] = htmlspecialchars($_POST['email']);
        }
        if(isset($_POST['address_street']) && !empty($_POST['address_street'])) {
            $data['address_street'] = htmlspecialchars($_POST['address_street']);
        }
        if(isset($_POST['address_house_number']) && !empty($_POST['address_house_number'])) {
            $data['address_house_number'] = htmlspecialchars($_POST['address_house_number']);
        }
        if(isset($_POST['address_city']) && !empty($_POST['address_city'])) {
            $data['address_city'] = htmlspecialchars($_POST['address_city']);
        }
        if(isset($_POST['address_zip_code']) && !empty($_POST['address_zip_code'])) {
            $data['address_zip_code'] = htmlspecialchars($_POST['address_zip_code']);
        }
        if(isset($_POST['address_country']) && !empty($_POST['address_country'])) {
            $data['address_country'] = htmlspecialchars($_POST['address_country']);
        }

        $data['status'] = UserStatus::PASSWORD_CREATION_REQUIRED;

        $app->userModel->insertUser($data);
        $idUser = $app->userModel->getLastInsertedUser()->getId();

        $app->logger->info('Created new user #' . $idUser, __METHOD__);

        $app->userRightModel->insertActionRightsForIdUser($idUser);
        $app->userRightModel->insertPanelRightsForIdUser($idUser);
        $app->userRightModel->insertBulkActionRightsForIdUser($idUser);
        $app->userRightModel->insertMetadataRightsForIdUser($idUser, $app->metadataModel->getAllMetadata());

        $app->redirect('UserModule:Users:showProfile', array('id' => $idUser));
    }

    protected function askToDeleteFolder() {
        $id = htmlspecialchars($_GET['id_folder']);

        $urlConfirm = array(
            'page' => 'UserModule:Settings:deleteFolder',
            'id_folder' => $id
        );

        $urlClose = array(
            'page' => 'UserModule:Settings:showFolders'
        );

        $code = ScriptLoader::confirmUser('Do you want to delete folder #' . $id . '?', $urlConfirm, $urlClose);

        return $code;
    }

    protected function deleteFolder() {
        global $app;

        $idFolder = htmlspecialchars($_GET['id_folder']);
        $folder = $app->folderModel->getFolderById($idFolder);

        $childFolders = [];
        $this->_getChildFolderList($childFolders, $folder);
        
        foreach($childFolders as $cf) {
            $docs = $app->documentModel->getStandardDocumentsInIdFolder($cf->getId());
            
            foreach($docs as $doc) {
                $app->documentModel->nullIdFolder($doc->getId());
            }
        }

        foreach($childFolders as $cf) {
            $app->folderModel->deleteFolder($cf->getId());

            $app->logger->info('Deleted folder #' . $cf->getId(), __METHOD__);
        }

        $app->redirect('UserModule:Settings:showFolders');
    }

    private function internalCreateNewGroupForm() {
        $fb = FormBuilder::getTemporaryObject();

        $fb ->setAction('?page=UserModule:Settings:createNewGroup')->setMethod('POST')
            ->addElement($fb->createLabel()->setFor('name')->setText('Group name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->require())

            ->addElement($fb->createLabel()->setFor('code')->setText('Code'))
            ->addElement($fb->createInput()->setType('text')->setName('code'))

            ->addElement($fb->createSubmit('Create'))
        ;

        $form = $fb->build();

        return $form;
    }

    private function internalCreateNewUserForm() {
        $fb = FormBuilder::getTemporaryObject();

        $fb ->setMethod('POST')->setAction('?page=UserModule:Settings:createNewUser')
            ->addElement($fb->createLabel()->setFor('firstname')->setText('First name'))
            ->addElement($fb->createInput()->setType('text')->setName('firstname')->require())

            ->addElement($fb->createLabel()->setFor('lastname')->setText('Last name'))
            ->addElement($fb->createInput()->setType('text')->setName('lastname')->require())

            ->addElement($fb->createlabel()->setFor('email')->setText('Email'))
            ->addElement($fb->createInput()->setType('email')->setName('email'))

            ->addElement($fb->createlabel()->setFor('username')->setText('Username'))
            ->addElement($fb->createInput()->setType('text')->setName('username')->require())

            ->addElement($fb->createlabel()->setText('Address'))
            ->addElement($fb->createlabel()->setFor('address_street')->setText('Street'))
            ->addElement($fb->createInput()->setType('text')->setName('address_street'))

            ->addElement($fb->createlabel()->setFor('address_house_number')->setText('House number'))
            ->addElement($fb->createInput()->setType('text')->setName('address_house_number'))

            ->addElement($fb->createlabel()->setFor('address_city')->setText('City'))
            ->addElement($fb->createInput()->setType('text')->setName('address_city'))

            ->addElement($fb->createlabel()->setFor('address_zip_code')->setText('Zip code'))
            ->addElement($fb->createInput()->setType('text')->setName('address_zip_code'))

            ->addElement($fb->createlabel()->setFor('address_country')->setText('Country'))
            ->addElement($fb->createInput()->setType('text')->setName('address_country'))

            ->addElement($fb->createSubmit('Create'))
        ;

        $form = $fb->build();

        return $form;
    }

    private function internalCreateNewMetadataForm() {
        $fb = FormBuilder::getTemporaryObject();

        $metadataTypesConst = MetadataInputType::$texts;

        $metadataInputTypes = [];
        foreach($metadataTypesConst as $k => $v) {
            $metadataInputTypes[] = array(
                'value' => $k,
                'text' => $v
            );
        }

        $fb ->setMethod('POST')->setAction('?page=UserModule:Settings:createNewMetadata')->setId('new_metadata_form')
            ->addElement($fb->createLabel()->setFor('name')->setText('Name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->require())

            ->addElement($fb->createLabel()->setFor('text')->setText('Text'))
            ->addElement($fb->createInput()->setType('text')->setName('text')->require())

            ->addElement($fb->createLabel()->setFor('table_name')->setText('Database table name'))
            ->addElement($fb->createInput()->setType('text')->setName('table_name')->require())

            ->addElement($fb->createLabel()->setFor('input_type')->setText('Metadata input type'))
            ->addElement($fb->createSelect()->setName('input_type')->addOptionsBasedOnArray($metadataInputTypes)->setId('input_type'))

            ->addElement($fb->createLabel()->setFor('length')->setText('Length'))
            ->addElement($fb->createInput()->setType('text')->setName('length')->require()->setId('length')->setValue(''))

            ->addElement($fb->createSubmit('Create'))
        ;

        $formJS = ScriptLoader::loadJSScript('js/MetadataForm.js');

        $fb->addJSScript($formJS);

        return $fb->build();
    }

    private function internalCreateGroupGrid() {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $headers = array(
            'Actions',
            'Name',
            'Code'
        );

        $headerRow = null;

        $groups = $app->groupModel->getAllGroups();

        if(empty($groups)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($groups as $group) {
                $actionLinks = array(
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:showUsers', 'id' => $group->getId()), 'Users')
                );

                if($app->actionAuthorizator->checkActionRight(UserActionRights::MANAGE_GROUP_RIGHTS)) {
                    $actionLinks[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:showGroupRights', 'id' => $group->getId()), 'Group rights');
                }

                if(is_null($headerRow)) {
                    $row = $tb->createRow();

                    foreach($headers as $header) {
                        $col = $tb->createCol()->setText($header)
                                               ->setBold();

                        if($header == 'Actions') {
                            $col->setColspan(count($actionLinks));
                        }

                        $row->addCol($col);
                    }

                    $headerRow = $row;
                
                    $tb->addRow($row);
                }

                $groupRow = $tb->createRow();

                foreach($actionLinks as $actionLink) {
                    $groupRow->addCol($tb->createCol()->setText($actionLink));
                }

                $groupData = array(
                    $group->getName() ?? '-',
                    $group->getCode() ?? '-'
                );

                foreach($groupData as $gd) {
                    $groupRow->addCol($tb->createCol()->setText($gd));
                }

                $tb->addRow($groupRow);
            }
        }

        return $tb->build();
    }

    private function internalCreateUsersGrid() {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $headers = array(
            'Actions',
            'Firstname',
            'Lastname',
            'Username',
            'Email',
            'Status',
            'Address Street',
            'Address House number',
            'Address City',
            'Address Zip code',
            'Address Country'
        );

        $headerRow = null;

        $users = $app->userModel->getAllUsers();

        if(empty($users)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($users as $user) {
                $actionLinks = array(
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showProfile', 'id' => $user->getId()), 'Profile')
                );

                if($app->actionAuthorizator->checkActionRight(UserActionRights::MANAGE_USER_RIGHTS)) {
                    $actionLinks[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showUserRights', 'id' => $user->getId()), 'User rights');
                }

                if(is_null($headerRow)) {
                    $row = $tb->createRow();

                    foreach($headers as $header) {
                        $col = $tb->createCol()->setText($header)
                                               ->setBold();

                        if($header == 'Actions') {
                            $col->setColspan(count($actionLinks));
                        }

                        $row->addCol($col);
                    }

                    $headerRow = $row;
                
                    $tb->addRow($row);
                }

                $userRow = $tb->createRow();

                foreach($actionLinks as $actionLink) {
                    $userRow->addCol($tb->createCol()->setText($actionLink));
                }

                $userData = array(
                    $user->getFirstname() ?? '-',
                    $user->getLastname() ?? '-',
                    $user->getUsername() ?? '-',
                    $user->getEmail() ?? '-',
                    UserStatus::$texts[$user->getStatus()],
                    $user->getAddressStreet() ?? '-',
                    $user->getAddressHouseNumber() ?? '-',
                    $user->getAddressCity() ?? '-',
                    $user->getAddressZipCode() ?? '-',
                    $user->getAddressCountry() ?? '-'
                );

                foreach($userData as $ud) {
                    $userRow->addCol($tb->createCol()->setText($ud));
                }

                $tb->addRow($userRow);
            }
        }

        return $tb->build();
    }

    private function internalDashboardCreateWidgets() {
        $widgets = array(
            $this->internalCreateCountWidget(),
            $this->internalCreateSystemInfoWidget()
        );

        $code = array();
        $code[] = '<div class="row">';

        $i = 0;
        foreach($widgets as $widget) {
            $code[] = $widget;

            if(($i + 1) == count($widgets) || ((($i % 2) == 0) && ($i > 0))) {
                $code[] = '</div>';
            }

            $i++;
        }

        return ArrayStringHelper::createUnindexedStringFromUnindexedArray($code);
    }

    private function internalCreateSystemInfoWidget() {
        global $app;

        $systemVersion = $app::SYSTEM_VERSION;

        $code = '<div class="col-md">
                    <div class="row">
                        <div class="col-md" id="center">
                            <p class="page-title">System information</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md">
                            <p><b>System version: </b>' . $systemVersion . '</p>
                        </div>
                    </div>
                 </div>';

        return $code;
    }

    private function internalCreateCountWidget() {
        global $app;

        $users = count($app->userModel->getAllUsers());
        $groups = count($app->groupModel->getAllGroups());
        $documents = count($app->documentModel->getAllDocuments());
        $folders = count($app->folderModel->getAllFolders());

        $code = '<div class="col-md">
                    <div class="row">
                        <div class="col-md" id="center">
                            <p class="page-title">Statistics</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md">
                            <p><b>Total users: </b>' . $users . '</p>
                            <p><b>Total groups: </b>' . $groups . '</p>
                            <p><b>Total documents: </b>' . $documents . '</p>
                            <p><b>Total folders: </b>' . $folders . '</p>
                        </div>
                    </div>
                </div>';

        return $code;
    }

    private function internalCreateMetadataGrid() {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $headers = array(
            'Actions',
            'Name',
            'Text',
            'Database table',
            'Input type'
        );
        
        $headerRow = null;

        $metadata = $app->metadataModel->getAllMetadata();

        if(empty($metadata)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($metadata as $m) {
                $actionLinks = array(
                    'values' => '-',
                    'delete' => '-',
                    'edit_user_rights' => '-'
                );

                if(!$app->metadataAuthorizator->canUserViewMetadata($app->user->getId(), $m->getId())) continue;

                if($app->metadataAuthorizator->canUserEditMetadata($app->user->getId(), $m->getId()) && !$m->getIsSystem() && $app->actionAuthorizator->checkActionRight(UserActionRights::DELETE_METADATA)) {
                    $actionLinks['delete'] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Settings:deleteMetadata', 'id' => $m->getId()), 'Delete');
                }

                if($m->getInputType() == 'select' && $app->metadataAuthorizator->canUserViewMetadataValues($app->user->getId(), $m->getId()) && $app->actionAuthorizator->checkActionRight(UserActionRights::EDIT_METADATA_VALUES)) {
                    $actionLinks['values'] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Metadata:showValues', 'id' => $m->getId()), 'Values');
                }

                if($app->actionAuthorizator->checkActionRight(UserActionRights::EDIT_USER_METADATA_RIGHTS)) {
                    $actionLinks['edit_user_rights'] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Metadata:showUserRights', 'id_metadata' => $m->getId()), 'User rights');
                }

                if(is_null($headerRow)) {
                    $row = $tb->createRow();

                    foreach($headers as $header) {
                        $col = $tb->createCol()->setText($header)
                                               ->setBold();

                        if($header == 'Actions') {
                            $col->setColspan(count($actionLinks));
                        }

                        $row->addCol($col);
                    }

                    $headerRow = $row;

                    $tb->addRow($row);
                }

                $metaRow = $tb->createRow();

                foreach($actionLinks as $actionLink) {
                    $metaRow->addCol($tb->createCol()->setText($actionLink));
                }

                $metaArray = array(
                    $m->getName(),
                    $m->getText(),
                    $m->getTableName(),
                    MetadataInputType::$texts[$m->getInputType()]
                );

                foreach($metaArray as $ma) {
                    $metaRow->addCol($tb->createCol()->setText($ma));
                }

                $tb->addRow($metaRow);
            }
        }

        return $tb->build();
    }

    private function internalCreateFolderGrid() {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $idFolder = null;

        if(isset($_GET['id_folder'])) {
            $idFolder = htmlspecialchars($_GET['id_folder']);
        }

        $headers = array(
            'Actions',
            'Name',
            'Description'
        );

        $headerRow = null;

        $folders = $app->folderModel->getFoldersForIdParentFolder($idFolder);
        $cacheFolders = [];

        foreach($folders as $folder) {
            $cacheFolders[$folder->getId()] = array('name' => $folder->getName(), 'description' => $folder->getDescription());
        }

        if(empty($folders)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($folders as $folder) {
                $actionLinks = array(
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:Settings:showFolders', 'id_folder' => $folder->getId()), 'Open'),
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:Settings:askToDeleteFolder', 'id_folder' => $folder->getId()), 'Delete')
                );

                if(is_null($headerRow)) {
                    $row = $tb->createRow();

                    foreach($headers as $header) {
                        $col = $tb->createCol()->setText($header)
                                               ->setBold();

                        if($header == 'Actions') {
                            $col->setColspan(count($actionLinks));
                        }

                        $row->addCol($col);
                    }

                    $headerRow = $row;

                    $tb->addRow($row);
                }

                $folderRow = $tb->createRow();

                foreach($actionLinks as $actionLink) {
                    $folderRow->addCol($tb->createCol()->setText($actionLink));
                }

                $folderRow->addCol($tb->createCol()->setText($folder->getName()))
                          ->addCol($tb->createCol()->setText($folder->getDescription() ?? '-'));

                $tb->addRow($folderRow);
            }
        }

        return $tb->build();
    }

    private function internalCreateNewFolderForm(?int $idParentFolder) {
        global $app;

        $fb = FormBuilder::getTemporaryObject();

        $foldersDb = $app->folderModel->getAllFolders();

        $foldersArr = array(array(
            'value' => '-1',
            'text' => 'None'
        ));
        foreach($foldersDb as $fdb) {
            $temp = array(
                'value' => $fdb->getId(),
                'text' => $fdb->getName()
            );

            if(!is_null($idParentFolder) && $fdb->getId() == $idParentFolder) {
                $temp['selected'] = 'selected';
            }

            $foldersArr[] = $temp;
        }

        $fb ->setMethod('POST')->setAction('?page=UserModule:Settings:createNewFolder')

            ->addElement($fb->createLabel()->setFor('name')->setText('Name'))
            ->addElement($fb->createInput()->setType('input')->setName('name')->require())

            ->addElement($fb->createLabel()->setFor('parent_folder')->setText('Parent folder'))
            ->addElement($fb->createSelect()->setName('parent_folder')->addOptionsBasedOnArray($foldersArr))

            ->addElement($fb->createLabel()->setFor('description')->setText('Description'))
            ->addElement($fb->createTextArea()->setName('description'))

            ->addElement($fb->createSubmit('Create'))
        ;

        return $fb->build();
    }

    private function internalCreateServicesGrid() {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $headers = array(
            'Actions',
            'System name',
            'Name',
            'Description'
        );

        $headerRow = null;

        $services = $app->serviceManager->services;

        foreach($services as $serviceName => $service) {
            $actionLinks = array(
                LinkBuilder::createAdvLink(array('page' => 'UserModule:Settings:askToRunService', 'name' => $service->name), 'Run'),
                LinkBuilder::createAdvLink(array('page' => 'UserModule:Settings:editServiceForm', 'name' => $service->name), 'Edit')
            );

            if(is_null($headerRow)) {
                $row = $tb->createRow();

                foreach($headers as $header) {
                    $col = $tb->createCol()->setText($header)
                                           ->setBold();

                    if($header == 'Actions') {
                        $col->setColspan(count($actionLinks));
                    }

                    $row->addCol($col);
                }

                $headerRow = $row;

                $tb->addRow($row);
            }

            $serviceRow = $tb->createRow();

            foreach($actionLinks as $actionLink) {
                $serviceRow->addCoL($tb->createCol()->setText($actionLink));
            }

            $serviceRow ->addCol($tb->createCol()->setText($service->name))
                        ->addCol($tb->createCol()->setText($serviceName))
                        ->addCol($tb->createCol()->setText($service->description))
            ;

            $tb->addRow($serviceRow);
        }

        return $tb->build();
    }

    private function _getFolderCount(int &$count, Folder $folder) {
        global $app;

        $childFolders = $app->folderModel->getFoldersForIdParentFolder($folder->getId());
        $count += count($childFolders);
        
        foreach($childFolders as $cf) {
            $this->_getFolderCount($count, $cf);
        }
    }

    private function _getChildFolderList(array &$list, Folder $folder) {
        global $app;

        $childFolders = $app->folderModel->getFoldersForIdParentFolder($folder->getId());

        if(!array_key_exists($folder->getId(), $list)) {
            $list[$folder->getId()] = $folder;
        }

        foreach($childFolders as $cf) {
            $this->_getChildFolderList($list, $cf);
        }
    }
    
    private function internalCreateEditServiceForm(string $name) {
        global $app;

        $service = $app->serviceManager->getServiceByName($name);
        $serviceCfg = $app->serviceModel->getConfigForServiceName($name);

        $fb = FormBuilder::getTemporaryObject();

        $fb ->setMethod('POST')->setAction('?page=UserModule:Settings:editService&name=' . $name)
            
            ->addElement($fb->createLabel()->setText('Service name')->setFor('name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->disable()->setValue($name))

            ->addElement($fb->createLabel()->setText('Description')->setFor('description'))
            ->addElement($fb->createInput()->setType('text')->setName('description')->disable()->setValue($service->description))
        ;

        foreach($serviceCfg as $key => $value) {
            $fb ->addElement($fb->createLabel()->setText($key)->setFor($key));

            if($key == ServiceMetadata::FILES_KEEP_LENGTH) {
                $fb
                ->addElement($fb->createSpecial('<span id="files_keep_length_text_value">__VAL__</span>'))
                ->addElement($fb->createInput()->setType('range')->setMin('1')->setMax('30')->setName($key)->setValue($value))
                ;
            }
        }

        $fb ->loadJSScript('js/EditServiceForm.js')
            ->addElement($fb->createSubmit('Save'));

        return $fb->build();
    }
}

?>