<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\CacheCategories;
use DMS\Constants\MetadataAllowedTables;
use DMS\Constants\MetadataInputType;
use DMS\Constants\ServiceMetadata;
use DMS\Constants\UserActionRights;
use DMS\Constants\UserPasswordChangeStatus;
use DMS\Constants\UserStatus;
use DMS\Constants\WidgetLocations;
use DMS\Core\AppConfiguration;
use DMS\Core\Application;
use DMS\Core\CacheManager;
use DMS\Core\ScriptLoader;
use DMS\Entities\Folder;
use DMS\Entities\Metadata;
use DMS\Helpers\ArrayHelper;
use DMS\Helpers\ArrayStringHelper;
use DMS\Helpers\DatetimeFormatHelper;
use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;

class Settings extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('Settings');

        $this->getActionNamesFromClass($this);
    }

    protected function deleteGroup() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);

        $id = $this->get('id');

        $notDeletableIdGroups = array(1, 2);

        if($app->actionAuthorizator->checkActionRight(UserActionRights::DELETE_GROUP) &&
           !in_array($id, $notDeletableIdGroups)) {
            $app->groupModel->deleteGroupById($id);
            $app->groupRightModel->removeAllGroupRightsForIdGroup($id);
            $app->groupUserModel->removeAllGroupUsersForIdGroup($id);
            $app->ribbonRightsModel->deleteAllRibbonRightsForIdGroup($id);
        }
    }

    protected function deleteUser() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);

        $id = $this->get('id');
        $user = $app->userModel->getUserById($id);

        $notDeletableIdUsers = array($app->user->getId(), AppConfiguration::getIdServiceUser());

        if($app->actionAuthorizator->checkActionRight(UserActionRights::DELETE_USER) &&
           !in_array($id, $notDeletableIdUsers) &&
           $user->getUsername() != 'admin') {
            $app->userModel->deleteUserById($id);
            $app->userModel->deleteConnectionsForIdUser($id);
            $app->userRightModel->removeAllUserRightsForIdUser($id);
            $app->groupUserModel->removeUserFromAllGroups($id);
            $app->ribbonRightsModel->deleteAllRibonRightsForIdUser($id);
            $app->widgetModel->removeAllWidgetsForIdUser($id);

            $app->flashMessage('Successfully removed user #' . $id, 'success');
        } else {
            $app->flashMessage('Could not remove user #' . $id, 'error');
        }

        $app->redirect('UserModule:Settings:showUsers');
    }

    protected function updateDashboardWidgets() {
        global $app;

        $app->flashMessageIfNotIsset(['id_user', 'widget00', 'widget01', 'widget10', 'widget11']);

        $idUser = $this->get('id_user');

        $widget0_0 = $this->post('widget00');
        $widget0_1 = $this->post('widget01');
        $widget1_0 = $this->post('widget10');
        $widget1_1 = $this->post('widget11');

        if($widget0_0 != '-') {
            if(is_null($app->widgetModel->getWidgetForIdUserAndLocation($idUser, WidgetLocations::HOME_DASHBOARD_WIDGET00))) {
                $app->widgetModel->insertWidgetForIdUser($idUser, WidgetLocations::HOME_DASHBOARD_WIDGET00, $widget0_0);
            } else {
                $app->widgetModel->updateWidgetForIdUser($idUser, WidgetLocations::HOME_DASHBOARD_WIDGET00, $widget0_0);
            }
        }

        if($widget0_1 != '-') {
            if(is_null($app->widgetModel->getWidgetForIdUserAndLocation($idUser, WidgetLocations::HOME_DASHBOARD_WIDGET01))) {
                $app->widgetModel->insertWidgetForIdUser($idUser, WidgetLocations::HOME_DASHBOARD_WIDGET01, $widget0_1);
            } else {
                $app->widgetModel->updateWidgetForIdUser($idUser, WidgetLocations::HOME_DASHBOARD_WIDGET01, $widget0_1);
            }
        }
        
        if($widget1_0 != '-') {
            if(is_null($app->widgetModel->getWidgetForIdUserAndLocation($idUser, WidgetLocations::HOME_DASHBOARD_WIDGET10))) {
                $app->widgetModel->insertWidgetForIdUser($idUser, WidgetLocations::HOME_DASHBOARD_WIDGET10, $widget1_0);
            } else {
                $app->widgetModel->updateWidgetForIdUser($idUser, WidgetLocations::HOME_DASHBOARD_WIDGET10, $widget1_0);
            }
        }

        if($widget1_1 != '-') {
            if(is_null($app->widgetModel->getWidgetForIdUserAndLocation($idUser, WidgetLocations::HOME_DASHBOARD_WIDGET11))) {
                $app->widgetModel->insertWidgetForIdUser($idUser, WidgetLocations::HOME_DASHBOARD_WIDGET11, $widget1_1);
            } else {
                $app->widgetModel->updateWidgetForIdUser($idUser, WidgetLocations::HOME_DASHBOARD_WIDGET11, $widget1_1);
            }
        }

        $app->flashMessage('Successfully updated widgets.', 'success');
        $app->redirect('UserModule:Settings:showDashboardWidgets');
    }

    protected function showDashboardWidgets() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-widgets-grid.html');

        $data = array(
            '$PAGE_TITLE$' => 'Dashboard widgets',
            '$SETTINGS_FORM$' => $this->internalCreateDashboardWidgetsForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function editService() {
        global $app;

        $app->flashMessageIfNotIsset(['name']);

        $name = $this->get('name');
        
        $values = ArrayHelper::formatArrayData($_POST);

        unset($values['name']);
        unset($values['description']);

        if($name == 'PasswordPolicyService') {
            if(!array_key_exists('password_change_force_administrators', $values)) {
                $values['password_change_force_administrators'] = '0';
            } else {
                $values['password_change_force_administrators'] = '1';
            }

            if(!array_key_exists('password_change_force', $values)) {
                $values['password_change_force'] = '0';
            } else {
                $values['password_change_force'] = '1';
            }
        } else if($name == 'NotificationManagerService') {
            if(!array_key_exists('notification_keep_unseen_service_user', $values)) {
                $values['notification_keep_unseen_service_user'] = '0';
            } else {
                $values['notification_keep_unseen_service_user'] = '1';
            }
        }

        foreach($values as $k => $v) {
            $app->serviceModel->updateService($name, $k, $v);
        }

        $cm = CacheManager::getTemporaryObject(CacheCategories::SERVICE_CONFIG);
        $cm->invalidateCache();

        $app->logger->info('Updated configuration for service \'' . $name . '\'', __METHOD__);

        $app->redirect('UserModule:Settings:showServices');
    }

    protected function editServiceForm() {
        global $app;
        
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');
        
        $app->flashMessageIfNotIsset(['name']);
        
        $name = $this->get('name');

        $data = array(
            '$PAGE_TITLE$' => 'Edit service <i>' . $name . '</i>',
            '$FORM$' => $this->internalCreateEditServiceForm($name)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showServices() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-grid.html');

        $servicesGrid = '';

        $app->logger->logFunction(function() use (&$servicesGrid) {
            $servicesGrid = $this->internalCreateServicesGrid();
        }, __METHOD__);

        $data = array(
            '$PAGE_TITLE$' => 'Services',
            '$SETTINGS_GRID$' => $servicesGrid,
            '$LINKS$' => ''
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function askToRunService() {
        global $app;

        $app->flashMessageIfNotIsset(['name']);

        $name = $this->get('name');

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

        $app->flashMessageIfNotIsset(['name']);

        $name = $this->get('name');

        foreach($app->serviceManager->services as $service) {
            if($service->name == $name) {
                $app->logger->info('Running service \'' . $name . '\'', __METHOD__);

                $app->logger->logFunction(function() use ($service) {
                    $service->run();
                }, __METHOD__);

                break;
            }
        }

        $app->redirect('UserModule:Settings:showServices');
    }

    protected function showFolders() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-folders.html');

        $newEntityLink = '';

        if($app->actionAuthorizator->checkActionRight(UserActionRights::CREATE_DOCUMENT_FOLDER)) {
            $newEntityLink = LinkBuilder::createLink('UserModule:Settings:showNewFolderForm', 'New folder');
        }

        $backLink = '';
        $pageTitle = 'Document folders';

        $idFolder = null;
        if(isset($_GET['id_folder'])) {
            $idFolder = $this->get('id_folder');
            $folder = $app->folderModel->getFolderById($idFolder);

            if((($folder->getNestLevel() + 1) < AppConfiguration::getFolderMaxNestLevel()) && ($app->actionAuthorizator->checkActionRight(UserActionRights::CREATE_DOCUMENT_FOLDER))) {
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

        $foldersGrid = '';

        $app->logger->logFunction(function() use (&$foldersGrid) {
            $foldersGrid = $this->internalCreateFolderGrid();
        }, __METHOD__);
        
        $data = array(
            '$PAGE_TITLE$' => $pageTitle,
            '$LINKS$' => '<div class="row"><div class="col-md" id="right">' . $backLink . '&nbsp;' . $newEntityLink . '</div></div>',
            '$FOLDERS_GRID$' => $foldersGrid
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showEditFolderForm() {
        global $app;

        $app->flashMessageIfNotIsset(['id_folder']);
        $idFolder = $this->get('id_folder');

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'Edit document folder form',
            '$FORM$' => $this->internalCreateEditFolderForm((int)($idFolder))
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showNewFolderForm() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $idParentFolder = null;

        if(isset($_GET['id_parent_folder'])) {
            $idParentFolder = $this->get('id_parent_folder');
        }

        $data = array(
            '$PAGE_TITLE$' => 'New document folder form',
            '$FORM$' => $this->internalCreateNewFolderForm($idParentFolder)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function processUpdateFolderForm() {
        global $app;

        $app->flashMessageIfNotIsset(['name', 'parent_folder', 'id_folder']);

        $idFolder = $this->get('id_folder');
        
        $data = [];

        $parentFolder = $this->post('parent_folder');
        $nestLevel = 0;

        $data['name'] = $this->post('name');

        $create = true;

        if(isset($_POST['description']) && $_POST['description'] != '') {
            $data['description'] = $this->post('description');
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
            $app->folderModel->updateFolder($idFolder, $data);
        }

        $idFolder = $app->folderModel->getLastInsertedFolder()->getId();
        
        $app->logger->info('Inserted new folder #' . $idFolder, __METHOD__);

        if($parentFolder != '-1') {
            $app->redirect('UserModule:Settings:showFolders', array('id_folder' => $idFolder));
        } else {
            $app->redirect('UserModule:Settings:showFolders');
        }
    }

    protected function createNewFolder() {
        global $app;

        $app->flashMessageIfNotIsset(['name', 'parent_folder']);

        $data = [];

        $parentFolder = $this->post('parent_folder');
        $nestLevel = 0;

        $data['name'] = $this->post('name');

        $create = true;

        if(isset($_POST['description']) && $_POST['description'] != '') {
            $data['description'] = $this->post('description');
        }

        if($parentFolder == '-1') {
            $parentFolder = null;
        } else {
            $data['id_parent_folder'] = $parentFolder;

            $nestLevelParentFolder = $app->folderModel->getFolderById($parentFolder);

            $nestLevel = $nestLevelParentFolder->getNestLevel() + 1;

            if($nestLevel == AppConfiguration::getFolderMaxNestLevel()) {
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
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-dashboard.html');

        $data = array(
            '$PAGE_TITLE$' => 'Settings'
        );

        $widgets = '';

        $app->logger->logFunction(function() use (&$widgets) {
            $widgets = $this->internalDashboardCreateWidgets();
        }, __METHOD__);

        $data['$WIDGETS$'] = $widgets;

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showUsers() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-grid-bottom-links.html');

        $page = 1;

        if(isset($_GET['grid_page'])) {
            $page = (int)($this->get('grid_page'));
        }

        $usersGrid = '';

        $app->logger->logFunction(function() use (&$usersGrid, $page) {
            $usersGrid = $this->internalCreateUsersGridAjax($page);
        }, __METHOD__);

        $data = array(
            '$PAGE_TITLE$' => 'Users',
            '$NEW_ENTITY_LINK$' => '',
            '$SETTINGS_GRID$' => $usersGrid,
            '$LINKS$' => [],
            '$PAGE_CONTROL$' => $this->internalCreateUserGridPageControl($page)
        );

        if($app->actionAuthorizator->checkActionRight('create_user')) {
            $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:Settings:showNewUserForm', 'New user');
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showGroups() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-grid-bottom-links.html');

        $groupsGrid = '';

        $page = 1;

        if(isset($_GET['grid_page'])) {
            $page = (int)($this->get('grid_page'));
        }

        $app->logger->logFunction(function() use (&$groupsGrid, $page) {
            $groupsGrid = $this->internalCreateGroupGridAjax($page);
        }, __METHOD__);

        $data = array(
            '$PAGE_TITLE$' => 'Groups',
            '$NEW_ENTITY_LINK$' => '',
            '$SETTINGS_GRID$' => $groupsGrid,
            '$LINKS$' => [],
            '$PAGE_CONTROL$' => $this->internalCreateGroupGridPageControl($page)
        );

        if($app->actionAuthorizator->checkActionRight('create_group')) {
            $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:Settings:showNewGroupForm', 'New group');
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showMetadata() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-grid.html');

        $metadataGrid = '';

        $app->logger->logFunction(function() use (&$metadataGrid) {
            $metadataGrid = $this->internalCreateMetadataGrid();
        }, __METHOD__);

        $data = array(
            '$PAGE_TITLE$' => 'Metadata manager',
            '$SETTINGS_GRID$' => $metadataGrid
        );

        $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:ExternalEnumViewer:showList', 'External enums') . '&nbsp;&nbsp;';

        if($app->actionAuthorizator->checkActionRight('create_metadata')) {
            $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:Settings:showNewMetadataForm', 'New metadata');
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showSystem() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-dashboard.html');

        $widgets = [];

        if($app->actionAuthorizator->checkActionRight(UserActionRights::UPDATE_DEFAULT_USER_RIGHTS)) {
            $widgets[] = LinkBuilder::createLink('UserModule:Settings:updateDefaultUserRights', 'Update default user rights') . '<br>';
        }

        if(Application::SYSTEM_DEBUG && $app->actionAuthorizator->checkActionRight(UserActionRights::USE_DOCUMENT_GENERATOR)) {
            $widgets[] = LinkBuilder::createLink('UserModule:DocumentGenerator:showForm', 'Document generator') . '<br>';
        }

        $widgets[] = LinkBuilder::createLink('UserModule:ImageBrowser:showAll', 'Images');

        $widgetsCode = '';

        $x = 100;
        foreach($widgets as $widget) {
            $code = '<div id="subpanel" style="position: absolute; top: 15%; left: ' . $x . 'px; width: 250px; height: 100px;">';
            $code .= $widget;
            $code .= '</div>';

            $widgetsCode .= $code;

            $x += 260;
        }

        $data = array(
            '$PAGE_TITLE$' => 'System',
            '$WIDGETS$' => $widgetsCode
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function updateDefaultUserRights() {
        global $app;

        $app->getConn()->installer->updateDefaultUserRights();

        $app->redirect('UserModule:Settings:showSystem');
    }

    protected function showEditMetadataForm() {
        global $app;

        $app->flashMessageIfNotIsset(['id_metadata']);
        $idMetadata = $this->get('id_metadata');
        $metadata = $app->metadataModel->getMetadataById($idMetadata);

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'New metadata form',
            '$FORM$' => $this->internalCreateEditMetadataForm($metadata)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showNewMetadataForm() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'New metadata form',
            '$FORM$' => $this->internalCreateNewMetadataForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showNewUserForm() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'New user form',
            '$FORM$' => $this->internalCreateNewUserForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showNewGroupForm() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'New group form',
            '$FORM$' => $this->internalCreateNewGroupForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function processEditMetadataForm() {
        global $app;

        $app->flashMessageIfNotIsset(['name', 'text', 'id_metadata']);

        $idMetadata = $this->get('id_metadata');

        $data = [];
        $data['name'] = $this->post('name');
        $data['text'] = $this->post('text');

        if(isset($_POST['readonly'])) {
            $data['is_readonly'] = '1';
        }

        $app->metadataModel->updateMetadata($idMetadata, $data);

        $app->flashMessage('Saved metadata #'. $idMetadata, 'success');
        $app->redirect('UserModule:Settings:showMetadata');
    }

    protected function createNewMetadata() {
        global $app;

        $app->flashMessageIfNotIsset(['name', 'table_name', 'length', 'input_type']);

        $data = [];

        $data['name'] = $name = $this->post('name');
        $data['table_name'] = $tableName = $this->post('table_name');
        $length = $this->post('length');
        $inputType = $this->post('input_type');

        if(isset($_POST['select_external_enum']) && $inputType == 'select_external') {
            $data['select_external_enum_name'] = $this->post('select_external_enum');
        }

        if(isset($_POST['readonly'])) {
            $data['is_readonly'] = '1';
        }

        $data['text'] = $this->post('text');
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

        $app->flashMessageIfNotIsset(['id']);

        $id = $this->get('id');
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

        $app->flashMessageIfNotIsset(['name']);

        $name = $this->post('name');
        $code = null;

        if(isset($_POST['code'])) {
            $code = $this->post('code');
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
        
        $app->flashMessageIfNotIsset($required);

        foreach($required as $r) {
            $data[$r] = $this->post($r);
        }

        if(isset($_POST['email']) && !empty($_POST['email'])) {
            $data['email'] = $this->post('email');
        }
        if(isset($_POST['address_street']) && !empty($_POST['address_street'])) {
            $data['address_street'] = $this->post('address_street');
        }
        if(isset($_POST['address_house_number']) && !empty($_POST['address_house_number'])) {
            $data['address_house_number'] = $this->post('address_house_number');
        }
        if(isset($_POST['address_city']) && !empty($_POST['address_city'])) {
            $data['address_city'] = $this->post('address_city');
        }
        if(isset($_POST['address_zip_code']) && !empty($_POST['address_zip_code'])) {
            $data['address_zip_code'] = $this->post('address_zip_code');
        }
        if(isset($_POST['address_country']) && !empty($_POST['address_country'])) {
            $data['address_country'] = $this->post('address_country');
        }

        $data['status'] = UserStatus::PASSWORD_CREATION_REQUIRED;
        $data['password_change_status'] = UserPasswordChangeStatus::FORCE;

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
        global $app;

        $app->flashMessageIfNotIsset(['id_folder']);

        $id = $this->get('id_folder');

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

        $app->flashMessageIfNotIsset(['id_folder']);

        $idFolder = $this->get('id_folder');
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

    private function internalCreateEditMetadataForm(Metadata $metadata) {
        $fb = FormBuilder::getTemporaryObject();

        $fb ->setMethod('POST')->setAction('?page=UserModule:Setttings:processEditMetadataForm&id_metadata=' . $metadata->getId())

            ->addElement($fb->createLabel()->setText('Name')->setFor('name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->setValue($metadata->getName())->disable()->require())

            ->addElement($fb->createLabel()->setText('Text')->setFor('text'))
            ->addElement($fb->createInput()->setType('text')->setName('text')->setValue($metadata->getText())->require())

            ->addElement($fb->createLabel()->setFor('readonly')->setText('Readonly'))
            ->addElement($fb->createInput()->setType('checkbox')->setName('readonly')->setSpecial($metadata->getIsReadonly() ? 'checked' : ''))

            ->addElement($fb->createSubmit('Save'))
        ;

        return $fb->build();
    }

    private function internalCreateNewMetadataForm() {
        global $app;

        $fb = FormBuilder::getTemporaryObject();

        $metadataTypesConst = MetadataInputType::$texts;

        $metadataInputTypes = [];
        foreach($metadataTypesConst as $k => $v) {
            $metadataInputTypes[] = array(
                'value' => $k,
                'text' => $v
            );
        }

        $selectExternalEnumsList = $app->externalEnumComponent->getEnumsList();

        $selectExternalEnums = [];
        foreach($selectExternalEnumsList as $k => $v) {
            $selectExternalEnums[] = array(
                'value' => $k,
                'text' => $k
            );
        }

        $tables = MetadataAllowedTables::$tables;

        $tablesArr = [];
        foreach($tables as $table) {
            $tablesArr[] = array(
                'value' => $table,
                'text' => $table
            );
        }

        $fb ->setMethod('POST')->setAction('?page=UserModule:Settings:createNewMetadata')->setId('new_metadata_form')
            ->addElement($fb->createLabel()->setFor('name')->setText('Name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->require())

            ->addElement($fb->createLabel()->setFor('text')->setText('Text'))
            ->addElement($fb->createInput()->setType('text')->setName('text')->require())

            ->addElement($fb->createLabel()->setFor('table_name')->setText('Database table'))
            ->addElement($fb->createSelect()->setname('table_name')->addOptionsBasedOnArray($tablesArr))

            ->addElement($fb->createLabel()->setFor('input_type')->setText('Metadata input type'))
            ->addElement($fb->createSelect()->setName('input_type')->addOptionsBasedOnArray($metadataInputTypes)->setId('input_type'))

            ->addElement($fb->createLabel()->setFor('length')->setText('Length'))
            ->addElement($fb->createInput()->setType('text')->setName('length')->require()->setId('length')->setValue(''))

            ->addElement($fb->createLabel()->setFor('select_external_enum')->setText('External select enumerator'))
            ->addElement($fb->createSelect()->setName('select_external_enum')->addOptionsBasedOnArray($selectExternalEnums)->setId('select_external_enum'))

            ->addElement($fb->createLabel()->setFor('readonly')->setText('Readonly'))
            ->addElement($fb->createInput()->setType('checkbox')->setName('readonly'))

            ->addElement($fb->createSubmit('Create'))
        ;

        $formJS = ScriptLoader::loadJSScript('js/MetadataForm.js');

        $fb->addJSScript($formJS);

        return $fb->build();
    }

    private function internalCreateGroupGridAjax(int $page = 1) {
        $code = '<script type="text/javascript">loadGroups("' . $page . '");</script>';
        $code .= '<table border="1"><img id="groups-loading" style="position: fixed; top: 50%; left: 49%;" src="img/loading.gif" width="32" height="32"></table>';

        return $code;
    }

    private function internalCreateUsersGridAjax(int $page = 1) {
        $code = '<script type="text/javascript">loadUsers("' . $page . '");</script>';
        $code .= '<table border="1"><img id="users-loading" style="position: fixed; top: 50%; left: 49%;" src="img/loading.gif" width="32" height="32"></table>';

        return $code;
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
        $systemIsDebugEnabled = $app::SYSTEM_DEBUG ? 'Enabled' : 'Disabled';
        $systemBuildDate = $app::SYSTEM_BUILD_DATE;

        if(!$app::SYSTEM_IS_BETA) {
            $systemBuildDate = DatetimeFormatHelper::formatDateByUserDefaultFormat($systemBuildDate, $app->user);
        }

        $code = '<div class="col-md">
                    <div class="row">
                        <div class="col-md" id="center">
                            <p class="page-title">System information</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md">
                            <p><b>System version: </b>' . $systemVersion . '</p>
                            <p><b>System build date: </b>' . $systemBuildDate . '</p>
                            <p><b>System debug: </b>' . $systemIsDebugEnabled . '</p>
                        </div>
                    </div>
                 </div>';

        return $code;
    }

    private function internalCreateCountWidget() {
        global $app;

        $users = $app->userModel->getUserCount();
        $groups = $app->groupModel->getGroupCount();
        $documents = $app->documentModel->getTotalDocumentCount();
        $folders = $app->folderModel->getFolderCount();
        $emails = $app->mailModel->getMailInQueueCount();

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
                            <p><b>Total emails in queue: </b>' . $emails . '</p>
                        </div>
                    </div>
                </div>';

        return $code;
    }

    private function internalCreateMetadataGrid() {
        global $app;

        $metadataModel = $app->metadataModel;
        $metadataAuthorizator = $app->metadataAuthorizator;
        $actionAuthorizator = $app->actionAuthorizator;
        $idUser = $app->user->getId();

        $data = function() use ($metadataModel, $metadataAuthorizator, $idUser) {
            $values = [];
            foreach($metadataModel->getAllMetadata() as $m) {
                if(!$metadataAuthorizator->canUserViewMetadata($idUser, $m->getId())) continue;

                $values[] = $m;
            }
            return $values;
        };

        $gb = new GridBuilder();
        
        $gb->addColumns(['name' => 'Name', 'text' => 'Text', 'dbTable' => 'Database table', 'inputType' => 'Input type']);
        $gb->addDataSourceCallback($data);
        $gb->addOnColumnRender('dbTable', function (\DMS\Entities\Metadata $metadata) {
            return $metadata->getTableName();
        });
        $gb->addAction(function(\DMS\Entities\Metadata $metadata) use ($metadataAuthorizator, $idUser, $actionAuthorizator) {
            $link = '-';
            if($metadataAuthorizator->canUserEditMetadata($idUser, $metadata->getId()) &&
               $actionAuthorizator->checkActionRight(UserActionRights::DELETE_METADATA) &&
               !$metadata->getIsSystem()) {
                $link = LinkBuilder::createAdvLink(array('page' => 'UserModule:Settings:deleteMetadata', 'id' => $metadata->getId()), 'Delete');
            }
            return $link;
        });
        $gb->addAction(function(\DMS\Entities\Metadata $metadata) use ($metadataAuthorizator, $idUser, $actionAuthorizator) {
            $link = '-';
            if($metadataAuthorizator->canUserEditMetadata($idUser, $metadata->getId()) &&
               $actionAuthorizator->checkActionRight(UserActionRights::EDIT_METADATA)) {
                $link = LinkBuilder::createAdvLink(array('page' => 'UserModule:Settings:showEditMetadataForm', 'id_metadata' => $metadata->getId()), 'Edit');
            }
            return $link;
        });
        $gb->addAction(function(\DMS\Entities\Metadata $metadata) use ($metadataAuthorizator, $idUser, $actionAuthorizator) {
            $link = '-';
            if((in_array($metadata->getInputType(), ['select', 'select_external'])) &&
               $metadataAuthorizator->canUserViewMetadataValues($idUser, $metadata->getId()) &&
               $actionAuthorizator->checkActionRight(UserActionRights::EDIT_METADATA_VALUES)) {
                $link = LinkBuilder::createAdvLink(array('page' => 'UserModule:Metadata:showValues', 'id' => $metadata->getId()), 'Values');
            }
            return $link;
        });
        $gb->addAction(function(\DMS\Entities\Metadata $metadata) use ($actionAuthorizator) {
            $link = '-';
            if($actionAuthorizator->checkActionRight(UserActionRights::EDIT_USER_METADATA_RIGHTS)) {
                $link = LinkBuilder::createAdvLink(array('page' => 'UserModule:Metadata:showUserRights', 'id_metadata' => $metadata->getId()), 'User rights');
            }
            return $link;
        });

        return $gb->build();
    }

    private function internalCreateFolderGrid() {
        global $app;

        $folderModel = $app->folderModel;

        $idFolder = null;

        if(isset($_GET['id_folder'])) {
            $idFolder = $this->get('id_folder');
        }

        $data = function() use ($folderModel, $idFolder) {
            return $folderModel->getFoldersForIdParentFolder($idFolder);
        };

        $gb = new GridBuilder();

        $gb->addColumns(['name' => 'Name', 'description' => 'Description']);
        $gb->addDataSourceCallback($data);
        $gb->addAction(function(Folder $folder) {
            return LinkBuilder::createAdvLink(array('page' => 'UserModule:Settings:showFolders', 'id_folder' => $folder->getId()), 'Open');
        });
        $gb->addAction(function(Folder $folder) {
            return LinkBuilder::createAdvLink(array('page' => 'UserModule:Settings:showEditFolderForm', 'id_folder' => $folder->getId()), 'Edit');
        });
        $gb->addAction(function(Folder $folder) {
            return LinkBuilder::createAdvLink(array('page' => 'UserModule:Settings:askToDeleteFolder', 'id_folder' => $folder->getId()), 'Delete');
        });

        return $gb->build();
    }

    private function internalCreateEditFolderForm(int $idFolder) {
        global $app;

        $fb = FormBuilder::getTemporaryObject();

        $folder = null;

        $foldersDb = [];

        $app->logger->logFunction(function() use ($app, &$foldersDb) {
            $foldersDb = $app->folderModel->getAllFolders();
        }, __METHOD__);

        foreach($foldersDb as $fdb) {
            if($fdb->getId() == $idFolder) {
                $folder = $fdb;
            }
        }

        if($folder === NULL) {
            die('Folder does not exist!');
        }

        $foldersArr = array(array(
            'value' => '-1',
            'text' => 'None'
        ));
        foreach($foldersDb as $fdb) {
            $temp = array(
                'value' => $fdb->getId(),
                'text' => $fdb->getName()
            );

            if(!is_null($folder->getIdParentFolder()) && ($fdb->getId() == $folder->getIdParentFolder())) {
                $temp['selected'] = 'selected';
            }

            $foldersArr[] = $temp;
        }

        $textArea = $fb->createTextArea()->setName('description');
        if($folder->getDescription() !== NULL) {
            $textArea->setText($folder->getDescription());
        }

        $fb ->setMethod('POST')->setAction('?page=UserModule:Settings:processUpdateFolderForm&id_folder=' . $idFolder)

            ->addElement($fb->createLabel()->setFor('name')->setText('Name'))
            ->addElement($fb->createInput()->setType('input')->setName('name')->setValue($folder->getName())->require())

            ->addElement($fb->createLabel()->setFor('parent_folder')->setText('Parent folder'))
            ->addElement($fb->createSelect()->setName('parent_folder')->addOptionsBasedOnArray($foldersArr))

            ->addElement($fb->createLabel()->setFor('description')->setText('Description'))
            ->addElement($textArea)

            ->addElement($fb->createSubmit('Save'))
        ;

        return $fb->build();
    }
    
    private function internalCreateNewFolderForm(?int $idParentFolder) {
        global $app;

        $fb = FormBuilder::getTemporaryObject();

        $foldersDb = [];

        $app->logger->logFunction(function() use ($app, &$foldersDb) {
            $foldersDb = $app->folderModel->getAllFolders();
        }, __METHOD__);

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

        $serviceManager = $app->serviceManager;
        $serviceModel = $app->serviceModel;
        $user = $app->user;
        $actionAuthorizator = $app->actionAuthorizator;

        $data = function() use ($serviceManager, $serviceModel, $user) {
            $values = [];
            foreach($serviceManager->services as $k => $v) {
                $serviceLogEntry = $serviceModel->getServiceLogLastEntryForServiceName($v->name);
                $serviceLastRunDate = '-';
            
                if($serviceLogEntry !== NULL) {
                    $serviceLastRunDate = $serviceLogEntry['date_created'];
                    $serviceLastRunDate = DatetimeFormatHelper::formatDateByUserDefaultFormat($serviceLastRunDate, $user);
                }

                $values[] = new class($v->name, $k, $v->description, $serviceLastRunDate) {
                    private $systemName;
                    private $name;
                    private $description;
                    private $lastRunDate;

                    function __construct($systemName, $name, $description, $lastRunDate) {
                        $this->systemName = $systemName;
                        $this->name = $name;
                        $this->description = $description;
                        $this->lastRunDate = $lastRunDate;
                    }

                    function getSystemName() {
                        return $this->systemName;
                    }

                    function getName() {
                        return $this->name;
                    }

                    function getDescription() {
                        return $this->description;
                    }

                    function getLastRunDate() {
                        return $this->lastRunDate;
                    }
                };
            }
            return $values;
        };

        $gb = new GridBuilder();

        $gb->addColumns(['systemName' => 'System name', 'name' => 'Name', 'description' => 'Description', 'lastRunDate' => 'Last run date']);
        $gb->addDataSourceCallback($data);
        $gb->addAction(function($service) use ($actionAuthorizator) {
            $link = '-';
            if($actionAuthorizator->checkActionRight(UserActionRights::RUN_SERVICE)) {
                $link = LinkBuilder::createAdvLink(array('page' => 'UserModule:Settings:askToRunService', 'name' => $service->getName()), 'Run');
            }
            return $link;
        });
        $gb->addAction(function($service) use ($actionAuthorizator) {
            $link = '-';
            if($actionAuthorizator->checkActionRight(UserActionRights::EDIT_SERVICE)) {
                $link = LinkBuilder::createAdvLink(array('page' => 'UserModule:Settings:editServiceForm', 'name' => $service->getName()), 'Edit');
            }
            return $link;
        });

        return $gb->build();
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
            $fb ->addElement($fb->createLabel()->setText(ServiceMetadata::$texts[$key] . ' (' . $key . ')')->setFor($key));

            switch($key) {
                case ServiceMetadata::FILES_KEEP_LENGTH:
                    $fb
                    ->addElement($fb->createSpecial('<span id="files_keep_length_text_value">__VAL__</span>'))
                    ->addElement($fb->createInput()->setType('range')->setMin('1')->setMax('30')->setName($key)->setValue($value))
                    ;
                    break;

                case ServiceMetadata::PASSWORD_CHANGE_PERIOD:
                    $fb
                    ->addElement($fb->createSpecial('<span id="password_change_period_text_value">__VAL__</span>'))
                    ->addElement($fb->createInput()->setType('range')->setMin('0')->setMax('60')->setName($key)->setValue($value))
                    ;
                    break;

                case ServiceMetadata::PASSWORD_CHANGE_FORCE_ADMINISTRATORS:
                    $fb
                    ->addElement($fb->createSpecial('<span id="password_change_force_administrators_text_value">__VAL__</span>'))
                    ;

                    $checkbox = $fb->createInput()->setType('checkbox')->setName($key);

                    if($value == '1') {
                        $checkbox->setSpecial('checked');
                    }

                    $fb->addElement($checkbox);

                    break;

                case ServiceMetadata::PASSWORD_CHANGE_FORCE:
                    $fb
                    ->addElement($fb->createSpecial('<span id="password_change_force_text_value">__VAL__</span>'))
                    ;

                    $checkbox = $fb->createInput()->setType('checkbox')->setName($key);

                    if($value == '1') {
                        $checkbox->setSpecial('checked');
                    }

                    $fb->addElement($checkbox);

                    break;

                case ServiceMetadata::NOTIFICATION_KEEP_LENGTH:
                    $fb
                    ->addElement($fb->createSpecial('<span id="notification_keep_length_text_value">__VAL__</span>'))
                    ->addElement($fb->createInput()->setType('range')->setMin('0')->setMax('30')->setName($key)->setValue($value))
                    ;
                    break;

                case ServiceMetadata::NOTIFICATION_KEEP_UNSEEN_SERVICE_USER:
                    $fb
                    ->addElement($fb->createSpecial('<span id="notification_keep_unseen_service_user_text_value">__VAL__</span>'))
                    ;

                    $checkbox = $fb->createInput()->setType('checkbox')->setName($key);

                    if($value == '1') {
                        $checkbox->setSpecial('checked');
                    }

                    $fb->addElement($checkbox);

                    break;
            }
        }

        $fb ->loadJSScript('js/EditServiceForm.js')
            ->addElement($fb->createSubmit('Save'));

        return $fb->build();
    }

    private function internalCreateDashboardWidgetsForm() {
        global $app;

        $idUser = $app->user->getId();

        $allWidgets = $app->widgetComponent->homeDashboardWidgets;

        $widgets00Select = array(
            array(
                'value' => '-',
                'text' => '-'
            )
        );

        $widgets01Select = array(
            array(
                'value' => '-',
                'text' => '-'
            )
        );

        $widgets10Select = array(
            array(
                'value' => '-',
                'text' => '-'
            )
        );

        $widgets11Select = array(
            array(
                'value' => '-',
                'text' => '-'
            )
        );

        $widget00loc = $widget01loc = $widget10loc = $widget11loc = null;

        $app->logger->logFunction(function() use ($app, &$widget00loc, &$widget01loc, &$widget10loc, &$widget11loc, $idUser) {
            $widget00loc = $app->widgetModel->getWidgetForIdUserAndLocation($idUser, WidgetLocations::HOME_DASHBOARD_WIDGET00);
            $widget01loc = $app->widgetModel->getWidgetForIdUserAndLocation($idUser, WidgetLocations::HOME_DASHBOARD_WIDGET01);
            $widget10loc = $app->widgetModel->getWidgetForIdUserAndLocation($idUser, WidgetLocations::HOME_DASHBOARD_WIDGET10);
            $widget11loc = $app->widgetModel->getWidgetForIdUserAndLocation($idUser, WidgetLocations::HOME_DASHBOARD_WIDGET11);
        }, __METHOD__);

        foreach($allWidgets as $name => $content) {
            $text = $content['text'];

            if(!is_null($widget00loc) && ($name == $widget00loc['widget_name'])) {
                $widgets00Select[] = array(
                    'value' => $name,
                    'text' => $text,
                    'selected' => 'selected'
                );
            } else {
                $widgets00Select[] = array(
                    'value' => $name,
                    'text' => $text
                );
            }

            if(!is_null($widget01loc) && ($name == $widget01loc['widget_name'])) {
                $widgets01Select[] = array(
                    'value' => $name,
                    'text' => $text,
                    'selected' => 'selected'
                );
            } else {
                $widgets01Select[] = array(
                    'value' => $name,
                    'text' => $text
                );
            }

            if(!is_null($widget10loc) && ($name == $widget10loc['widget_name'])) {
                $widgets10Select[] = array(
                    'value' => $name,
                    'text' => $text,
                    'selected' => 'selected'
                );
            } else {
                $widgets10Select[] = array(
                    'value' => $name,
                    'text' => $text
                );
            }

            if(!is_null($widget11loc) && ($name == $widget11loc['widget_name'])) {
                $widgets11Select[] = array(
                    'value' => $name,
                    'text' => $text,
                    'selected' => 'selected'
                );
            } else {
                $widgets11Select[] = array(
                    'value' => $name,
                    'text' => $text
                );
            }
        }

        $fb = FormBuilder::getTemporaryObject();

        $fb->setMethod('POST')->setAction('?page=UserModule:Settings:updateDashboardWidgets&id_user=' . $idUser);

        $fb ->addElement($fb->createLabel()->setText('Widget 1 (upper-left)'))
            ->addElement($fb->createSelect()->setName('widget00')
                                            ->addOptionsBasedOnArray($widgets00Select))

            ->addElement($fb->createLabel()->setText('Widget 2 (upper-right)'))
            ->addElement($fb->createSelect()->setName('widget01')
                                            ->addOptionsBasedOnArray($widgets01Select))

            ->addElement($fb->createLabel()->setText('Widget 3 (lower-left)'))
            ->addElement($fb->createSelect()->setName('widget10')
                                            ->addOptionsBasedOnArray($widgets10Select))
            
            ->addElement($fb->createLabel()->setText('Widget 4 (lower-right)'))
            ->addElement($fb->createSelect()->setName('widget11')
                                            ->addOptionsBasedOnArray($widgets11Select))

            ->addElement($fb->createSubmit('Save'))
        ;

        return $fb->build();
    }

    private function internalCreateUserGridPageControl(int $page) {
        global $app;

        $userCount = $app->userModel->getUserCount();

        $userPageControl = '';
        $firstPageLink = '<a class="general-link" title="First page" href="?page=UserModule:Settings:showUsers';
        $previousPageLink = '<a class="general-link" title="Previous page" href="?page=UserModule:Settings:showUsers';
        $nextPageLink = '<a class="general-link" title="Next page" href="?page=UserModule:Settings:showUsers';
        $lastPageLink = '<a class="general-link" title="Last page" href="?page=UserModule:Settings:showUsers';

        $pageCheck = $page - 1;

        $firstPageLink .= '"';
        if($page == 1) {
            $firstPageLink .= ' hidden';
        }

        $firstPageLink .= '>&lt;&lt;</a>';

        if($page > 2) {
            $previousPageLink .= '&grid_page=' . ($page - 1);
        }
        
        $previousPageLink .= '"';

        if($page == 1) {
            $previousPageLink .= ' hidden';
        }

        $previousPageLink .= '>&lt;</a>';

        $nextPageLink .= '&grid_page=' . ($page + 1);
        $nextPageLink .= '"';

        if($userCount <= ($page * AppConfiguration::getGridSize())) {
            $nextPageLink .= ' hidden';
        }

        $nextPageLink .= '>&gt;</a>';

        $lastPageLink .= '&grid_page=' . (ceil($userCount / AppConfiguration::getGridSize()));
        $lastPageLink .= '"';
        
        if($userCount <= ($page * AppConfiguration::getGridSize())) {
            $lastPageLink .= ' hidden';
        }

        $lastPageLink .= '>&gt;&gt;</a>';

        if($userCount > AppConfiguration::getGridSize()) {
            if($pageCheck * AppConfiguration::getGridSize() >= $userCount) {
                $userPageControl = (1 + ($page * AppConfiguration::getGridSize()));
            } else {
                $userPageControl = (1 + ($pageCheck * AppConfiguration::getGridSize())) . '-' . (AppConfiguration::getGridSize() + ($pageCheck * AppConfiguration::getGridSize()));
            }
        } else {
            $userPageControl = $userCount;
        }

        $userPageControl .= ' | ' . $firstPageLink . ' ' . $previousPageLink . ' ' . $nextPageLink . ' ' . $lastPageLink;

        return $userPageControl;
    }

    private function internalCreateGroupGridPageControl(int $page) {
        global $app;

        $groupCount = $app->groupModel->getGroupCount();

        $groupPageControl = '';
        $firstPageLink = '<a class="general-link" title="First page" href="?page=UserModule:Settings:showGroups';
        $previousPageLink = '<a class="general-link" title="Previous page" href="?page=UserModule:Settings:showGroups';
        $nextPageLink = '<a class="general-link" title="Next page" href="?page=UserModule:Settings:showGroups';
        $lastPageLink = '<a class="general-link" title="Last page" href="?page=UserModule:Settings:showGroups';

        $pageCheck = $page - 1;

        $firstPageLink .= '"';
        if($page == 1) {
            $firstPageLink .= ' hidden';
        }

        $firstPageLink .= '>&lt;&lt;</a>';

        if($page > 2) {
            $previousPageLink .= '&grid_page=' . ($page - 1);
        }
        
        $previousPageLink .= '"';

        if($page == 1) {
            $previousPageLink .= ' hidden';
        }

        $previousPageLink .= '>&lt;</a>';

        $nextPageLink .= '&grid_page=' . ($page + 1);
        $nextPageLink .= '"';

        if($groupCount <= ($page * AppConfiguration::getGridSize())) {
            $nextPageLink .= ' hidden';
        }

        $nextPageLink .= '>&gt;</a>';

        $lastPageLink .= '&grid_page=' . (ceil($groupCount / AppConfiguration::getGridSize()));
        $lastPageLink .= '"';
        
        if($groupCount <= ($page * AppConfiguration::getGridSize())) {
            $lastPageLink .= ' hidden';
        }

        $lastPageLink .= '>&gt;&gt;</a>';

        if($groupCount > AppConfiguration::getGridSize()) {
            if($pageCheck * AppConfiguration::getGridSize() >= $groupCount) {
                $groupPageControl = (1 + ($page * AppConfiguration::getGridSize()));
            } else {
                $groupPageControl = (1 + ($pageCheck * AppConfiguration::getGridSize())) . '-' . (AppConfiguration::getGridSize() + ($pageCheck * AppConfiguration::getGridSize()));
            }
        } else {
            $groupPageControl = $groupCount;
        }

        $groupPageControl .= ' | ' . $firstPageLink . ' ' . $previousPageLink . ' ' . $nextPageLink . ' ' . $lastPageLink;

        return $groupPageControl;
    }
}

?>