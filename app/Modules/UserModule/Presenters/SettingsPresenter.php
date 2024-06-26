<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\CacheCategories;
use DMS\Constants\Metadata\FolderMetadata;
use DMS\Constants\Metadata\MetadataMetadata;
use DMS\Constants\Metadata\ServiceMetadata as MetadataServiceMetadata;
use DMS\Constants\Metadata\UserMetadata;
use DMS\Constants\MetadataAllowedTables;
use DMS\Constants\MetadataInputType;
use DMS\Constants\ServiceMetadata;
use DMS\Constants\UserActionRights;
use DMS\Constants\UserStatus;
use DMS\Constants\WidgetLocations;
use DMS\Core\AppConfiguration;
use DMS\Core\CacheManager;
use DMS\Core\CryptManager;
use DMS\Core\ScriptLoader;
use DMS\Entities\Folder;
use DMS\Entities\Metadata;
use DMS\Entities\ServiceEntity;
use DMS\Helpers\ArrayHelper;
use DMS\Helpers\ArrayStringHelper;
use DMS\Helpers\DatetimeFormatHelper;
use DMS\Helpers\DocumentFolderListHelper;
use DMS\Helpers\GridDataHelper;
use DMS\Models\DocumentModel;
use DMS\Models\FolderModel;
use DMS\Models\GroupModel;
use DMS\Models\MailModel;
use DMS\Models\UserModel;
use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;

class SettingsPresenter extends APresenter {
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

            $app->flashMessage('Successfully removed group #' . $id, 'success');
        } else {
            $app->flashMessage('Could not remove user #' . $id, 'error');
        }

        $app->redirect('showGroups');
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

        $app->redirect('showUsers');
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
        $app->redirect('showDashboardWidgets');
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

    protected function showFolders() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-folders.html');

        $newEntityLink = '';

        if($app->actionAuthorizator->checkActionRight(UserActionRights::CREATE_DOCUMENT_FOLDER)) {
            $newEntityLink = LinkBuilder::createLink('showNewFolderForm', 'New folder');
        }

        $backLink = '';
        $pageTitle = 'Document folders';

        $idFolder = null;
        if(isset($_GET['id_folder']) && $this->get('id_folder') != '0') {
            $idFolder = $this->get('id_folder');
            $folder = $app->folderModel->getFolderById($idFolder);

            if((($folder->getNestLevel() + 1) < AppConfiguration::getFolderMaxNestLevel()) && ($app->actionAuthorizator->checkActionRight(UserActionRights::CREATE_DOCUMENT_FOLDER))) {
                $newEntityLink = LinkBuilder::createAdvLink(array('page' => 'showNewFolderForm', 'id_parent_folder' => $idFolder), 'New folder');
            } else {
                $newEntityLink = '';
            }

            if($folder->getIdParentFolder() != NULL) {
                $backLink = LinkBuilder::createAdvLink(array('page' => 'showFolders', 'id_folder' => $folder->getIdParentFolder()), '&larr;');
            } else {
                $backLink = LinkBuilder::createLink('showFolders', '&larr;');
            }

            $pageTitle .= ' in <i>' . $folder->getName() . '</i>';
        }

        $foldersGrid = '';

        $app->logger->logFunction(function() use (&$foldersGrid) {
            $foldersGrid = $this->internalCreateFolderGrid();
        }, __METHOD__);
        
        $data = array(
            '$PAGE_TITLE$' => $pageTitle,
            '$LINKS$' => [],
            '$FOLDERS_GRID$' => $foldersGrid
        );

        if($backLink !== '') {
            $data['$LINKS$'][] = $backLink . '&nbsp;&nbsp;';
        }
        if($newEntityLink !== '') {
            $data['$LINKS$'][] = $newEntityLink;
        }

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
            '$FORM$' => $this->internalCreateEditFolderForm((int)($idFolder)),
            '$LINKS$' => []
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
            '$FORM$' => $this->internalCreateNewFolderForm($idParentFolder),
            '$LINKS$' => []
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function processUpdateFolderForm() {
        global $app;

        $app->flashMessageIfNotIsset(['name', 'id_parent_folder', 'id_folder']);

        $idFolder = $this->get('id_folder');
        
        $data = [];

        $parentFolder = $this->post('id_parent_folder');
        $nestLevel = 0;

        $data[FolderMetadata::NAME] = $this->post('name');

        $create = true;

        if(isset($_POST['description']) && $_POST['description'] != '') {
            $data[FolderMetadata::DESCRIPTION] = $this->post('description');
        }

        if($parentFolder == '-1') {
            $parentFolder = null;
        } else {
            $data[FolderMetadata::ID_PARENT_FOLDER] = $parentFolder;

            $nestLevelParentFolder = $app->folderModel->getFolderById($parentFolder);

            $nestLevel = $nestLevelParentFolder->getNestLevel() + 1;

            if($nestLevel == AppConfiguration::getFolderMaxNestLevel()) {
                $create = false;
            }

            $lastOrderForParentFolder = $app->folderModel->getLastOrderForParentFolder($parentFolder);

            $data[FolderMetadata::ORDER] = $lastOrderForParentFolder + 1;
        }

        $data[FolderMetadata::NEST_LEVEL] = $nestLevel;

        if($create == true) {
            $app->folderModel->updateFolder($idFolder, $data);
        }
        
        $app->logger->info('Updated folder #' . $idFolder, __METHOD__);

        if($parentFolder !== NULL) {
            $app->redirect('showFolders', array('id_folder' => $idFolder));
        } else {
            $app->redirect('showFolders');
        }
    }

    protected function createNewFolder() {
        global $app;

        $app->flashMessageIfNotIsset([FolderMetadata::NAME, FolderMetadata::ID_PARENT_FOLDER]);

        $data = [];

        $parentFolder = $this->post(FolderMetadata::ID_PARENT_FOLDER);
        $nestLevel = 0;

        $data[FolderMetadata::NAME] = $this->post(FolderMetadata::NAME);

        $create = true;

        if(isset($_POST[FolderMetadata::DESCRIPTION]) && $_POST[FolderMetadata::DESCRIPTION] != '') {
            $data[FolderMetadata::DESCRIPTION] = $this->post(FolderMetadata::DESCRIPTION);
        }

        if($parentFolder == '-1') {
            $parentFolder = null;
        } else {
            $data[FolderMetadata::ID_PARENT_FOLDER] = $parentFolder;

            $nestLevelParentFolder = $app->folderModel->getFolderById($parentFolder);

            $nestLevel = $nestLevelParentFolder->getNestLevel() + 1;

            if($nestLevel == AppConfiguration::getFolderMaxNestLevel()) {
                $create = false;
            }
        }

        $folders = $app->folderModel->getFoldersForIdParentFolder($parentFolder);

        $maxOrder = 0;
        foreach($folders as $folder) {
            if($folder->getOrder() > $maxOrder) {
                $maxOrder = $folder->getOrder();
            }
        }

        $data[FolderMetadata::NEST_LEVEL] = $nestLevel;
        $data[FolderMetadata::ORDER] = $maxOrder + 1;

        if($create == true) {
            $app->folderModel->insertNewFolder($data);
        }

        $idFolder = $app->folderModel->getLastInsertedFolder()->getId();
        
        $app->logger->info('Inserted new folder #' . $idFolder, __METHOD__);

        if($parentFolder != '-1') {
            $idParentFolder = (int)$parentFolder;
            $app->redirect('showFolders', array('id_folder' => $idParentFolder));
        } else {
            $app->redirect('showFolders');
        }
    }

    protected function moveFolderOrder() {
        global $app;

        $app->flashMessageIfNotIsset(['id_folder', 'order', 'old_order', 'id_parent_folder']);

        $idFolder = $this->get('id_folder');
        $idParentFolder = $this->get('id_parent_folder');
        $order = $this->get('order');
        $oldOrder = $this->get('old_order');
        
        if($idParentFolder == 0) {
            $parentFolder = null;
        } else {
            $parentFolder = $idParentFolder;
        }

        $oldFolder = $app->folderModel->getFolderByOrderAndParentFolder($parentFolder, $this->get('order'));
        
        $data[FolderMetadata::ORDER] = $oldOrder;

        $app->folderModel->updateFolder($oldFolder->getId(), $data);
        
        $data = [];
        
        $data[FolderMetadata::ORDER] = $order;

        $app->folderModel->updateFolder($idFolder, $data);

        $app->redirect('showFolders', ['id_folder' => $idParentFolder]);
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
            '$LINKS$' => []
        );

        if($app->actionAuthorizator->checkActionRight('create_user')) {
            $data['$LINKS$'][] = LinkBuilder::createLink('showNewUserForm', 'New user');
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
            '$LINKS$' => []
        );

        if($app->actionAuthorizator->checkActionRight('create_group')) {
            $data['$LINKS$'][] = LinkBuilder::createLink('showNewGroupForm', 'New group');
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showMetadata() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-grid-bottom-links.html');

        $metadataGrid = '';

        $app->logger->logFunction(function() use (&$metadataGrid) {
            $metadataGrid = $this->internalCreateMetadataGrid(1);
        }, __METHOD__);

        $data = array(
            '$PAGE_TITLE$' => 'Metadata manager',
            '$SETTINGS_GRID$' => $metadataGrid/*,
            '$CURRENT_FOLDER_TITLE$' => '',
            '$FOLDER_LIST$' => DocumentFolderListHelper::getFolderList($app->folderModel, 'showMetadata') ''*/
        );

        $data['$LINKS$'][] = LinkBuilder::createLink('ExternalEnumViewer:showList', 'External enums') . '&nbsp;&nbsp;';

        if($app->actionAuthorizator->checkActionRight('create_metadata')) {
            $data['$LINKS$'][] = LinkBuilder::createLink('showNewMetadataForm', 'New metadata');
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showSystem() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-dashboard.html');

        $widgets = [];

        if($app->actionAuthorizator->checkActionRight(UserActionRights::UPDATE_DEFAULT_USER_RIGHTS)) {
            $widgets[] = LinkBuilder::createLink('updateDefaultUserRights', 'Update default user rights') . '<br>';
        }

        if(AppConfiguration::getIsDebug() && $app->actionAuthorizator->checkActionRight(UserActionRights::USE_DOCUMENT_GENERATOR)) {
            $widgets[] = LinkBuilder::createLink('DocumentGenerator:showForm', 'Document generator') . '<br>';
        }

        $widgets[] = LinkBuilder::createLink('ImageBrowser:showAll', 'Images') . '<br>';

        if($app->actionAuthorizator->checkActionRight(UserActionRights::VIEW_FILE_STORAGE_LOCATIONS)) {
            $widgets[] = LinkBuilder::createLink('FileStorageSettings:showLocations', 'File storage') . '<br>';
        }

        $widgets[] = LinkBuilder::createAdvLink(['page' => 'SystemEventCalendar:showEvents', 'tag' => 'system', 'year' => date('Y'), 'month' => date('m')], 'System event calendar');
        $widgets[] = LinkBuilder::createAdvLink(['page' => 'DocumentReports:showReportsForAllUsers'], 'Generated document reports');
        $widgets[] = LinkBuilder::createAdvLink(['page' => 'UserSettings:showLoginAttempts'], 'Login attempts');
        $widgets[] = LinkBuilder::createAdvLink(['page' => 'UserSettings:showBlockedUsers'], 'Blocked users');
        $widgets[] = LinkBuilder::createAdvLink(['page' => 'UserSettings:showAbsentUsers'], 'Absent users');

        $widgetsCode = '<div class="row"><div class="col-md" id="center">';

        $c = 0;
        foreach($widgets as $widget) {
            if($c == 0) {
                $widgetsCode .= '<div class="row">';
            }

            $widgetsCode .= '<div class="col-md-2" id="subpanel" style="height: 100px; margin: 25px;">';
            $widgetsCode .= $widget;
            $widgetsCode .= '</div>';

            if(($c + 1) == count($widgets)) {
                $widgetsCode .= '</div>';
                $c = 0;
            } else {
                $c++;
            }
        }

        $widgetsCode .= '</div></div>';

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

        $app->redirect('showSystem');
    }

    protected function showEditMetadataForm() {
        global $app;

        $app->flashMessageIfNotIsset(['id_metadata']);
        $idMetadata = $this->get('id_metadata');
        $metadata = $app->metadataModel->getMetadataById($idMetadata);

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'New metadata form',
            '$FORM$' => $this->internalCreateEditMetadataForm($metadata),
            '$LINKS$' => []
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showNewMetadataForm() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'New metadata form',
            '$FORM$' => $this->internalCreateNewMetadataForm(),
            '$LINKS$' => []
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showNewUserForm() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'New user form',
            '$FORM$' => $this->internalCreateNewUserForm(),
            '$LINKS$' => []
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showNewGroupForm() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'New group form',
            '$FORM$' => $this->internalCreateNewGroupForm(),
            '$LINKS$' => []
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
        $app->redirect('showMetadata');
    }

    protected function createNewMetadata() {
        global $app;

        $app->flashMessageIfNotIsset(['name', 'table_name', 'length', 'input_type']);

        $data = [];

        $data[MetadataMetadata::NAME] = $name = $this->post('name');
        $data[MetadataMetadata::TABLE_NAME] = $tableName = $this->post('table_name');
        $length = $this->post('length');
        $inputType = $this->post('input_type');

        if(isset($_POST['select_external_enum']) && $inputType == 'select_external') {
            $data[MetadataMetadata::SELECT_EXTERNAL_ENUM_NAME] = $this->post('select_external_enum');
        }

        if(isset($_POST['readonly'])) {
            $data[MetadataMetadata::IS_READONLY] = '1';
        }

        $data[MetadataMetadata::TEXT] = $this->post('text');
        $data[MetadataMetadata::INPUT_TYPE] = $inputType;

        if($inputType == 'boolean') {
            $length = '2';
        } else if($inputType == 'select') {
            $length = '256';
        } else if($inputType == 'date') {
            $length = '10';
        }

        $data[MetadataMetadata::LENGTH] = $length;

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
            $app->redirect('Metadata:showValues', array('id' => $idMetadata));
        } else {
            $app->redirect('showMetadata');
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

        $app->redirect('showMetadata');
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
        $app->groupRightModel->insertBulkActionRightsForIdGroup($idGroup);

        $app->redirect('Groups:showUsers', array('id' => $idGroup));
    }

    protected function createNewUser() {
        global $app;

        $data = [];

        $required = array('firstname', 'lastname', 'username', 'password', 'password2');
        
        $app->flashMessageIfNotIsset($required);

        foreach($required as $r) {
            $data[$r] = $this->post($r);
        }

        if(!$app->userAuthenticator->checkPasswordMatch([$data[UserMetadata::PASSWORD], $data['password2']])) {
            $app->flashMessage('Passwords do not match!', 'error');
            $app->redirect('showNewUserForm');
        } else {
            $data[UserMetadata::PASSWORD] = CryptManager::hashPassword($data[UserMetadata::PASSWORD]);
            unset($data['password2']);
        }

        if(isset($_POST['email']) && !empty($_POST['email'])) {
            $data[UserMetadata::EMAIL] = $this->post('email');
        }
        if(isset($_POST['address_street']) && !empty($_POST['address_street'])) {
            $data[UserMetadata::ADDRESS_STREET] = $this->post('address_street');
        }
        if(isset($_POST['address_house_number']) && !empty($_POST['address_house_number'])) {
            $data[UserMetadata::ADDRESS_HOUSE_NUMBER] = $this->post('address_house_number');
        }
        if(isset($_POST['address_city']) && !empty($_POST['address_city'])) {
            $data[UserMetadata::ADDRESS_CITY] = $this->post('address_city');
        }
        if(isset($_POST['address_zip_code']) && !empty($_POST['address_zip_code'])) {
            $data[UserMetadata::ADDRESS_ZIP_CODE] = $this->post('address_zip_code');
        }
        if(isset($_POST['address_country']) && !empty($_POST['address_country'])) {
            $data[UserMetadata::ADDRESS_COUNTRY] = $this->post('address_country');
        }

        $data[UserMetadata::STATUS] = UserStatus::ACTIVE;

        $app->userModel->insertUser($data);
        $idUser = $app->userModel->getLastInsertedUser()->getId();

        $app->logger->info('Created new user #' . $idUser, __METHOD__);

        $app->userRightModel->insertActionRightsForIdUser($idUser);
        $app->userRightModel->insertBulkActionRightsForIdUser($idUser);
        $app->userRightModel->insertMetadataRightsForIdUser($idUser, $app->metadataModel->getAllMetadata());

        $ribbons = $app->ribbonModel->getAllRibbons();

        $visibleRibbons = ['home', 'current_user', 'current_user.settings', 'current_user.document_reports'];
        foreach($ribbons as $ribbon) {
            if(in_array($ribbon->getCode(), $visibleRibbons)) {
                $app->ribbonRightsModel->insertNewUserRibbonRight($ribbon->getId(), $idUser, ['can_see' => '1']);
            }
        }

        $app->redirect('Users:showProfile', array('id' => $idUser));
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

        $app->redirect('showFolders');
    }

    private function internalCreateNewGroupForm() {
        $fb = FormBuilder::getTemporaryObject();

        $fb ->setAction('?page=UserModule:Settings:createNewGroup')->setMethod('POST')
            ->addElement($fb->createLabel()->setFor('name')->setText('Group name')->setRequired())
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
            ->addElement($fb->createLabel()->setFor('firstname')->setText('First name')->setRequired())
            ->addElement($fb->createInput()->setType('text')->setName('firstname')->require())

            ->addElement($fb->createLabel()->setFor('lastname')->setText('Last name')->setRequired())
            ->addElement($fb->createInput()->setType('text')->setName('lastname')->require())

            ->addElement($fb->createLabel()->setFor('email')->setText('Email'))
            ->addElement($fb->createInput()->setType('email')->setName('email'))

            ->addElement($fb->createLabel()->setFor('username')->setText('Username')->setRequired())
            ->addElement($fb->createInput()->setType('text')->setName('username')->require())

            ->addElement($fb->createLabel()->setFor('password')->setText('Password')->setRequired())
            ->addElement($fb->createInput()->setType('password')->setName('password')->require())
            
            ->addElement($fb->createLabel()->setFor('password2')->setText('Password again')->setRequired())
            ->addElement($fb->createInput()->setType('password')->setName('password2')->require())

            ->addElement($fb->createLabel()->setText('Address'))
            ->addElement($fb->createlabel()->setFor('address_street')->setText('Street'))
            ->addElement($fb->createInput()->setType('text')->setName('address_street'))

            ->addElement($fb->createLabel()->setFor('address_house_number')->setText('House number'))
            ->addElement($fb->createInput()->setType('text')->setName('address_house_number'))

            ->addElement($fb->createLabel()->setFor('address_city')->setText('City'))
            ->addElement($fb->createInput()->setType('text')->setName('address_city'))

            ->addElement($fb->createLabel()->setFor('address_zip_code')->setText('Zip code'))
            ->addElement($fb->createInput()->setType('text')->setName('address_zip_code'))

            ->addElement($fb->createLabel()->setFor('address_country')->setText('Country'))
            ->addElement($fb->createInput()->setType('text')->setName('address_country'))

            ->addElement($fb->createSubmit('Create'))
        ;

        $form = $fb->build();

        return $form;
    }

    private function internalCreateEditMetadataForm(Metadata $metadata) {
        $fb = FormBuilder::getTemporaryObject();

        $fb ->setMethod('POST')->setAction('?page=UserModule:Setttings:processEditMetadataForm&id_metadata=' . $metadata->getId())

            ->addElement($fb->createLabel()->setText('Name')->setFor('name')->setRequired())
            ->addElement($fb->createInput()->setType('text')->setName('name')->setValue($metadata->getName())->disable()->require())

            ->addElement($fb->createLabel()->setText('Text')->setFor('text')->setRequired())
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
            ->addElement($fb->createLabel()->setFor('name')->setText('Name')->setRequired())
            ->addElement($fb->createInput()->setType('text')->setName('name')->require())

            ->addElement($fb->createLabel()->setFor('text')->setText('Text')->setRequired())
            ->addElement($fb->createInput()->setType('text')->setName('text')->require())

            ->addElement($fb->createLabel()->setFor('table_name')->setText('Database table'))
            ->addElement($fb->createSelect()->setname('table_name')->addOptionsBasedOnArray($tablesArr))

            ->addElement($fb->createLabel()->setFor('input_type')->setText('Metadata input type'))
            ->addElement($fb->createSelect()->setName('input_type')->addOptionsBasedOnArray($metadataInputTypes)->setId('input_type'))

            ->addElement($fb->createLabel()->setFor('length')->setText('Length')->setRequired())
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
        $code .= '<div id="grid-loading"><img src="img/loading.gif" width="32" height="32"></div><table border="1"></table>';

        return $code;
    }

    private function internalCreateUsersGridAjax(int $page = 1) {
        $code = '<script type="text/javascript">loadUsers("' . $page . '");</script>';
        $code .= '<div id="grid-loading"><img src="img/loading.gif" width="32" height="32"></div><table border="1"></table>';

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
        $systemIsDebugEnabled = AppConfiguration::getIsDebug() ? 'Enabled' : 'Disabled';
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

        $counts = $app->logger->logFunction(function(UserModel $userModel, GroupModel $groupModel, DocumentModel $documentModel, FolderModel $folderModel, MailModel $mailModel) {
            $users = $userModel->getUserCount();
            $groups = $groupModel->getGroupCount();
            $documents = $documentModel->getTotalDocumentCount(null, false);
            $folders = $folderModel->getFolderCount();
            $emails = $mailModel->getMailInQueueCount();

            return [
                'users' => $users,
                'groups' => $groups,
                'documents' => $documents,
                'folders' => $folders,
                'emails' => $emails
            ];
        }, __METHOD__, [$app->userModel, $app->groupModel, $app->documentModel, $app->folderModel, $app->mailModel]);

        $code = '<div class="col-md">
                    <div class="row">
                        <div class="col-md" id="center">
                            <p class="page-title">Statistics</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md">
                            <p><b>Total users: </b>' . $counts['users'] . '</p>
                            <p><b>Total groups: </b>' . $counts['groups'] . '</p>
                            <p><b>Total documents: </b>' . $counts['documents'] . '</p>
                            <p><b>Total folders: </b>' . $counts['folders'] . '</p>
                            <p><b>Total emails in queue: </b>' . $counts['emails'] . '</p>
                        </div>
                    </div>
                </div>';

        return $code;
    }

    private function internalCreateMetadataGrid(int $page) {
        $code = '<script type="text/javascript">loadMetadata("' . $page . '");</script>';
        $code .= '<div id="grid-loading"><img src="img/loading.gif" width="32" height="32"></div><table border="1"></table>';
        return $code;
    }

    private function internalCreateFolderGrid() {
        global $app;

        $idFolder = null;

        if(isset($_GET['id_folder'])) {
            $idFolder = $this->get('id_folder');
        }

        if($idFolder == '0') {
            $idFolder = null;
        }

        $folders = $app->folderModel->getFoldersForIdParentFolder($idFolder, true);

        $maxOrder = 0;
        foreach($folders as $folder) {
            if($folder->getOrder() > $maxOrder) {
                $maxOrder = $folder->getOrder();
            }
        }

        $gb = new GridBuilder();

        $gb->addColumns([FolderMetadata::NAME => 'Name', FolderMetadata::DESCRIPTION => 'Description']);
        $gb->addDataSource($folders);
        $gb->addAction(function(Folder $folder) {
            return LinkBuilder::createAdvLink(array('page' => 'showFolders', 'id_folder' => $folder->getId()), 'Open');
        });
        $gb->addAction(function(Folder $folder) {
            return LinkBuilder::createAdvLink(array('page' => 'showEditFolderForm', 'id_folder' => $folder->getId()), 'Edit');
        });
        $gb->addAction(function(Folder $folder) {
            return LinkBuilder::createAdvLink(array('page' => 'askToDeleteFolder', 'id_folder' => $folder->getId()), 'Delete');
        });
        $gb->addAction(function(Folder $folder) {
            if($folder->getOrder() > 1) {
                return LinkBuilder::createAdvLink(['page' => 'moveFolderOrder', 'order' => ($folder->getOrder() - 1), 'old_order' => ($folder->getOrder()), 'id_folder' => $folder->getId(), 'id_parent_folder' => $folder->getIdParentFolder() ?? 0], '&uarr;');
            } else {
                return '-';
            }
        });
        $gb->addAction(function(Folder $folder) use ($maxOrder) {
            if($folder->getOrder() < $maxOrder) {
                return LinkBuilder::createAdvLink(['page' => 'moveFolderOrder', 'order' => ($folder->getOrder() + 1), 'old_order' => ($folder->getOrder()), 'id_folder' => $folder->getId(), 'id_parent_folder' => $folder->getIdParentFolder() ?? 0], '&darr;');
            } else {
                return '-';
            }
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

        $textArea = $fb->createTextArea()->setName(FolderMetadata::DESCRIPTION);
        if($folder->getDescription() !== NULL) {
            $textArea->setText($folder->getDescription());
        }

        $fb ->setMethod('POST')->setAction('?page=UserModule:Settings:processUpdateFolderForm&id_folder=' . $idFolder)

            ->addElement($fb->createLabel()->setFor(FolderMetadata::NAME)->setText('Name')->setRequired())
            ->addElement($fb->createInput()->setType('input')->setName(FolderMetadata::NAME)->setValue($folder->getName())->require())

            ->addElement($fb->createLabel()->setFor(FolderMetadata::ID_PARENT_FOLDER)->setText('Parent folder'))
            ->addElement($fb->createSelect()->setName(FolderMetadata::ID_PARENT_FOLDER)->addOptionsBasedOnArray($foldersArr))

            ->addElement($fb->createLabel()->setFor(FolderMetadata::DESCRIPTION)->setText('Description'))
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

        $foldersArr = DocumentFolderListHelper::getSelectFolderList($app->folderModel, $idParentFolder);

        $fb ->setMethod('POST')->setAction('?page=UserModule:Settings:createNewFolder')

            ->addElement($fb->createLabel()->setFor(FolderMetadata::NAME)->setText('Name')->setRequired())
            ->addElement($fb->createInput()->setType('input')->setName(FolderMetadata::NAME)->require())

            ->addElement($fb->createLabel()->setFor(FolderMetadata::ID_PARENT_FOLDER)->setText('Parent folder'))
            ->addElement($fb->createSelect()->setName(FolderMetadata::ID_PARENT_FOLDER)->addOptionsBasedOnArray($foldersArr))

            ->addElement($fb->createLabel()->setFor(FolderMetadata::DESCRIPTION)->setText('Description'))
            ->addElement($fb->createTextArea()->setName(FolderMetadata::DESCRIPTION))

            ->addElement($fb->createSubmit('Create'))
        ;

        return $fb->build();
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
}

?>