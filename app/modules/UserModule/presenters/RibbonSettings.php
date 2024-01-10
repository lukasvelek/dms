<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\CacheCategories;
use DMS\Constants\UserActionRights;
use DMS\Core\CacheManager;
use DMS\Entities\Ribbon;
use DMS\Models\AModel;
use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

class RibbonSettings extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('RibbonSettings', 'Ribbon settings');

        $this->getActionNamesFromClass($this);
    }

    protected function deleteRibbon() {
        global $app;

        $app->flashMessageIfNotIsset(array('id'), true, array('page' => 'UserModule:RibbonSettings:showAll'));
        
        $id = htmlspecialchars($_GET['id']);
        
        $app->ribbonRightsModel->deleteAllGroupRibbonRights($id);
        $app->ribbonRightsModel->deleteAllUserRibbonRights($id);
        $app->ribbonModel->deleteRibbon($id);
        
        $app->flashMessage('Ribbon #' . $id . ' successfully deleted', 'success');
        $app->redirect('UserModule:RibbonSettings:showAll');
    }

    protected function revokeRibbonRightToGroup() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_ribbon', 'id_group', 'name'), true, array('page' => 'UserModule:RibbonSettings:showAll'));

        $idRibbon = htmlspecialchars($_GET['id_ribbon']);
        $idGroup = htmlspecialchars($_GET['id_group']);
        $name = htmlspecialchars($_GET['name']);

        $rights = array($name => '0');

        $app->ribbonRightsModel->updateGroupRights($idRibbon, $idGroup, $rights);

        $app->flashMessage('Updated rights for group #' . $idGroup . ' and ribbon #' . $idRibbon, 'success');
        $app->redirect('UserModule:RibbonSettings:showEditGroupRightsForm', array('id' => $idRibbon));
    }

    protected function grantRibbonRightToGroup() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_ribbon', 'id_group', 'name'), true, array('page' => 'UserModule:RibbonSettings:showAll'));

        $idRibbon = htmlspecialchars($_GET['id_ribbon']);
        $idGroup = htmlspecialchars($_GET['id_group']);
        $name = htmlspecialchars($_GET['name']);

        $rights = array($name => '1');

        $app->ribbonRightsModel->updateGroupRights($idRibbon, $idGroup, $rights);

        $app->flashMessage('Updated rights for group #' . $idGroup . ' and ribbon #' . $idRibbon, 'success');
        $app->redirect('UserModule:RibbonSettings:showEditGroupRightsForm', array('id' => $idRibbon));
    }

    protected function grantAllRibbonRightsToGroup() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_ribbon', 'id_group'), true, array('page' => 'UserModule:RibbonSettings:showAll'));

        $idRibbon = htmlspecialchars($_GET['id_ribbon']);
        $idGroup = htmlspecialchars($_GET['id_group']);

        $rights = array(
            AModel::VIEW => '1',
            AModel::EDIT => '1',
            AModel::DELETE => '1'
        );

        $app->ribbonRightsModel->updateGroupRights($idRibbon, $idGroup, $rights);

        $app->flashMessage('Updated rights for group #' . $idGroup . ' and ribbon #' . $idRibbon, 'success');
        $app->redirect('UserModule:RibbonSettings:showEditGroupRightsForm', array('id' => $idRibbon));
    }

    protected function revokeAllRibbonRightsToGroup() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_ribbon', 'id_group'), true, array('page' => 'UserModule:RibbonSettings:showAll'));

        $idRibbon = htmlspecialchars($_GET['id_ribbon']);
        $idGroup = htmlspecialchars($_GET['id_group']);

        $rights = array(
            AModel::VIEW => '0',
            AModel::EDIT => '0',
            AModel::DELETE => '0'
        );

        $app->ribbonRightsModel->updateGroupRights($idRibbon, $idGroup, $rights);

        $app->flashMessage('Updated rights for group #' . $idGroup . ' and ribbon #' . $idRibbon, 'success');
        $app->redirect('UserModule:RibbonSettings:showEditGroupRightsForm', array('id' => $idRibbon));
    }

    protected function revokeRibbonRightToUser() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_ribbon', 'id_user', 'name'));

        $idRibbon = htmlspecialchars($_GET['id_ribbon']);
        $idUser = htmlspecialchars($_GET['id_user']);
        $name = htmlspecialchars($_GET['name']);

        $rights = array($name => '0');

        $app->ribbonRightsModel->updateUserRights($idRibbon, $idUser, $rights);

        $app->flashMessage('Updated rights for user #' . $idUser . ' and ribbon #' . $idRibbon, 'success');
        $app->redirect('UserModule:RibbonSettings:showEditUserRightsForm', array('id' => $idRibbon));
    }

    protected function grantRibbonRightToUser() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_ribbon', 'id_user', 'name'));

        $idRibbon = htmlspecialchars($_GET['id_ribbon']);
        $idUser = htmlspecialchars($_GET['id_user']);
        $name = htmlspecialchars($_GET['name']);

        $rights = array($name => '1');

        $app->ribbonRightsModel->updateUserRights($idRibbon, $idUser, $rights);

        $app->flashMessage('Updated rights for user #' . $idUser . ' and ribbon #' . $idRibbon, 'success');
        $app->redirect('UserModule:RibbonSettings:showEditUserRightsForm', array('id' => $idRibbon));
    }

    protected function grantAllRibbonRightsToUser() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_ribbon', 'id_user'), true, array('page' => 'UserModule:RibbonSettings:showAll'));

        $idRibbon = htmlspecialchars($_GET['id_ribbon']);
        $idUser = htmlspecialchars($_GET['id_user']);

        $rights = array(
            AModel::VIEW => '1',
            AModel::EDIT => '1',
            AModel::DELETE => '1'
        );

        $app->ribbonRightsModel->updateUserRights($idRibbon, $idUser, $rights);

        $app->flashMessage('Updated rights for user #' . $idUser . ' and ribbon #' . $idRibbon, 'success');
        $app->redirect('UserModule:RibbonSettings:showEditUserRightsForm', array('id' => $idRibbon));
    }

    protected function revokeAllRibbonRightsToUser() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_ribbon', 'id_user'), true, array('page' => 'UserModule:RibbonSettings:showAll'));

        $idRibbon = htmlspecialchars($_GET['id_ribbon']);
        $idUser = htmlspecialchars($_GET['id_user']);

        $rights = array(
            AModel::VIEW => '0',
            AModel::EDIT => '0',
            AModel::DELETE => '0'
        );

        $app->ribbonRightsModel->updateUserRights($idRibbon, $idUser, $rights);

        $app->flashMessage('Updated rights for user #' . $idUser . ' and ribbon #' . $idRibbon, 'success');
        $app->redirect('UserModule:RibbonSettings:showEditUserRightsForm', array('id' => $idRibbon));
    }

    protected function showEditGroupRightsForm() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-grid.html');

        $app->flashMessageIfNotIsset(array('id'), true, array('page' => 'UserModule:RibbonSettings:showAll'));
        $id = htmlspecialchars($_GET['id']);

        $cm = CacheManager::getTemporaryObject(CacheCategories::RIBBONS);
        $valFromCache = $cm->loadRibbonById($id);
        $ribbon = null;

        if(!is_null($valFromCache)) {
            $ribbon = $valFromCache;
        } else {
            $ribbon = $app->ribbonModel->getRibbonById($id);

            $cm->saveRibbon($ribbon);
        }

        $rightsGrid = '';

        $app->logger->logFunction(function() use (&$rightsGrid, $ribbon) {
            $rightsGrid = $this->internalCreateGroupRightsGrid($ribbon);
        }, __METHOD__);

        $data = array(
            '$PAGE_TITLE$' => 'Ribbon <i>' . $ribbon->getName() . ' (' . $ribbon->getCode() . ')</i> group rights',
            '$LINKS$' => [],
            '$SETTINGS_GRID$' => $rightsGrid
        );

        $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:RibbonSettings:showAll', '<-');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showEditUserRightsForm() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-grid.html');

        $app->flashMessageIfNotIsset(array('id'), true, array('page' => 'UserModule:RibbonSettings:showAll'));
        $id = htmlspecialchars($_GET['id']);

        $cm = CacheManager::getTemporaryObject(CacheCategories::RIBBONS);
        $valFromCache = $cm->loadRibbonById($id);
        $ribbon = null;

        if(!is_null($valFromCache)) {
            $ribbon = $valFromCache;
        } else {
            $ribbon = $app->ribbonModel->getRibbonById($id);

            $cm->saveRibbon($ribbon);
        }

        $rightsGrid = '';

        $app->logger->logFunction(function() use (&$rightsGrid, $ribbon) {
            $rightsGrid = $this->internalCreateUserRightsGrid($ribbon);
        }, __METHOD__);

        $data = array(
            '$PAGE_TITLE$' => 'Ribbon <i>' . $ribbon->getName() . ' (' . $ribbon->getCode() . ')</i> user rights',
            '$LINKS$' => [],
            '$SETTINGS_GRID$' => $rightsGrid
        );

        $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:RibbonSettings:showAll', '<-');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function processEditForm() {
        global $app;

        $app->flashMessageIfNotIsset(array('id', 'name', 'code', 'page_url', 'parent'), true, array('page' => 'UserModule:RibbonSettings:showAll'));

        $id = htmlspecialchars($_GET['id']);

        $data = [];
        $data['name'] = htmlspecialchars($_POST['name']);
        $data['code'] = htmlspecialchars($_POST['code']);
        $data['page_url'] = htmlspecialchars($_POST['page_url']);

        if($_POST['parent'] != '0') {
            $data['id_parent_ribbon'] = htmlspecialchars($_POST['parent']);
        }

        if(isset($_POST['title']) && $_POST['title'] != '') {
            $data['title'] = htmlspecialchars($_POST['title']);
        }

        if(isset($_POST['image'])) {
            $data['image'] = htmlspecialchars($_POST['image']);
        }

        if(isset($_POST['is_visible'])) {
            $data['is_visible'] = '1';
        } else {
            $data['is_visible'] = '0';
        }

        $app->ribbonModel->updateRibbon($id, $data);

        $app->flashMessage('Successfully edited ribbon #' . $id);

        $app->redirect('UserModule:RibbonSettings:showAll');
    }

    protected function showEditForm() {
        global $app;

        $app->flashMessageIfNotIsset(array('id'), true, array('page' => 'UserModule:RibbonSettings:showAll'));

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $id = htmlspecialchars($_GET['id']);

        $ribbon = $app->ribbonModel->getRibbonById($id);

        $data = array(
            '$PAGE_TITLE$' => 'New ribbon form',
            '$FORM$' => $this->internalCreateEditRibbonForm($ribbon)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function processNewForm() {
        global $app;

        $app->flashMessageIfNotIsset(array('name', 'code', 'parent', 'page_url'), true, array('page' => 'UserModule:RibbonSettings:showNewForm'));

        $data = [];
        $data['name'] = htmlspecialchars($_POST['name']);
        $data['code'] = htmlspecialchars($_POST['code']);
        $data['page_url'] = htmlspecialchars($_POST['page_url']);

        if($_POST['parent'] != '0') {
            $data['id_parent_ribbon'] = htmlspecialchars($_POST['parent']);
        }

        if(isset($_POST['title'])) {
            $data['title'] = htmlspecialchars($_POST['title']);
        }

        if(isset($_POST['image'])) {
            $data['image'] = htmlspecialchars($_POST['image']);
        }

        if(isset($_POST['is_visible'])) {
            $data['is_visible'] = '1';
        } else {
            $data['is_visible'] = '0';
        }

        $app->ribbonModel->insertNewRibbon($data);
        $idRibbon = $app->ribbonModel->getLastInsertedRibbonId();

        if($idRibbon === FALSE) {
            die();
        }

        $admGroup = $app->groupModel->getGroupByCode('ADMINISTRATORS');
        $app->ribbonRightsModel->insertAllGrantedRightsForGroup($idRibbon, $admGroup->getId());

        // current user
        /*$admin = $app->userModel->getUserByUsername('admin');
        $app->ribbonRightsModel->insertAllGrantedRightsForUser($idRibbon, $admin->getId());*/
        $app->ribbonRightsModel->insertAllGrantedRightsForUser($idRibbon, $app->user->getId());

        $app->flashMessage('Created new ribbon', 'success');

        $app->redirect('UserModule:RibbonSettings:showAll');
    }

    protected function showNewForm() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'New ribbon form',
            '$FORM$' => $this->internalCreateNewRibbonForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function clearCache() {
        global $app;
        
        $rcm = CacheManager::getTemporaryObject(CacheCategories::RIBBONS);
        $rcm->invalidateCache();
        
        $rucm = CacheManager::getTemporaryObject(CacheCategories::RIBBON_USER_RIGHTS);
        $rucm->invalidateCache();
        
        $rgcm = CacheManager::getTemporaryObject(CacheCategories::RIBBON_GROUP_RIGHTS);
        $rgcm->invalidateCache();
        
        unset($rcm, $rucm, $rgcm);
        
        $rcm = CacheManager::getTemporaryObject(CacheCategories::RIBBONS);
        
        $ribbons = $app->ribbonModel->getAllRibbons();
        
        foreach($ribbons as $ribbon) {
            $rcm->saveRibbon($ribbon);
        }
        
        unset($rcm);
        
        $app->redirect('UserModule:RibbonSettings:showAll');
    }

    protected function showAll() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-grid-wider.html');

        $settingsGrid = '';

        $app->logger->logFunction(function() use (&$settingsGrid) {
            $settingsGrid = $this->internalCreateRibbonGrid();
        }, __METHOD__);

        $data = array(
            '$PAGE_TITLE$' => 'Ribbon settings',
            '$LINKS$' => [],
            '$SETTINGS_GRID$' => $settingsGrid
        );

        $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:Settings:showDashboard', '<-');

        if($app->actionAuthorizator->checkActionRight(UserActionRights::CREATE_RIBBONS)) {
            $data['$LINKS$'][] = '&nbsp;&nbsp;' . LinkBuilder::createAdvLink(array('page' => 'UserModule:RibbonSettings:showNewForm'), 'New ribbon');
        }

        $data['$LINKS$'][] = '&nbsp;&nbsp;' . LinkBuilder::createAdvLink(array('page' => 'UserModule:RibbonSettings:clearCache'), 'Clear cache');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateRibbonGrid() {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $headers = array(
            'Actions',
            'Name',
            'Code',
            'Visible',
            'URL'
        );

        $headerRow = null;
        $ribbons = [];

        $app->logger->logFunction(function() use ($app, &$ribbons) {
            $ribbons = $app->ribbonModel->getAllRibbons(true);
        }, __METHOD__);

        if(empty($ribbons)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($ribbons as $ribbon) {
                if(!($ribbon instanceof Ribbon)) {
                    continue;
                }

                $actionLinks = array(
                    'edit' => '-',
                    'edit_user_rights' => '-',
                    'edit_group_rights' => '-',
                    'delete' => '-'
                );

                if($app->ribbonAuthorizator->checkRibbonEditable($app->user->getId(), $ribbon) &&
                   $app->actionAuthorizator->checkActionRight(UserActionRights::EDIT_RIBBONS)) {
                    $actionLinks['edit'] = LinkBuilder::createAdvLink(array('page' => 'UserModule:RibbonSettings:showEditForm', 'id' => $ribbon->getId()), 'Edit');
                }

                if($app->ribbonAuthorizator->checkRibbonDeletable($app->user->getId(), $ribbon) &&
                   $app->actionAuthorizator->checkActionRight(UserActionRights::DELETE_RIBBONS)) {
                    $actionLinks['delete'] = LinkBuilder::createAdvLink(array('page' => 'UserModule:RibbonSettings:deleteRibbon', 'id' => $ribbon->getId()), 'Delete');
                }

                if($app->actionAuthorizator->checkActionRight(UserActionRights::EDIT_RIBBON_RIGHTS)) {
                    $actionLinks['edit_user_rights'] = LinkBuilder::createAdvLink(array('page' => 'UserModule:RibbonSettings:showEditUserRightsForm', 'id' => $ribbon->getId()), 'Edit user rights');
                    $actionLinks['edit_group_rights'] = LinkBuilder::createAdvLink(array('page' => 'UserModule:RibbonSettings:showEditGroupRightsForm', 'id' => $ribbon->getId()), 'Edit group rights');
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

                $ribbonRow = $tb->createRow();

                foreach($actionLinks as $actionLink) {
                    $ribbonRow->addCol($tb->createCol()->setText($actionLink));
                }

                $visible = $ribbon->isVisible() ? '<span style="color: green">Yes</span>' : '<span style="color: red">No</span>';

                $ribbonRow  ->addCol($tb->createCol()->setText($ribbon->getName()))
                            ->addCol($tb->createCol()->setText($ribbon->getCode()))
                            ->addCol($tb->createCol()->setText($visible))
                            ->addCol($tb->createCol()->setText($ribbon->getPageUrl()))
                ;

                $tb->addRow($ribbonRow);
            }
        }
        
        return $tb->build();
    }

    private function internalCreateNewRibbonForm() {
        global $app;

        $fb = FormBuilder::getTemporaryObject();

        $parentRibbons = null;

        $cm = CacheManager::getTemporaryObject(CacheCategories::RIBBONS);
        $valFromCache = $cm->loadTopRibbons();

        if(!is_null($valFromCache)) {
            $parentRibbons = $valFromCache;
        } else {
            $parentRibbons = $app->ribbonModel->getToppanelRibbons();
        }

        $parentRibbonsArr = [['value' => '0', 'text' => '- (root)']];
        foreach($parentRibbons as $ribbon) {
            $parentRibbonsArr[] = array(
                'value' => $ribbon->getId(),
                'text' => $ribbon->getName() . ' (' . $ribbon->getCode() . ')'
            );
        }

        $fb
            ->setAction('?page=UserModule:RibbonSettings:processNewForm')
            ->setMethod('POST')

            ->addElement($fb->createLabel()->setText('Name')->setFor('name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->setMaxLength('256')->require())

            ->addElement($fb->createLabel()->setText('Title (display name, can be same as Name)'))
            ->addElement($fb->createInput()->setType('text')->setName('title')->setMaxLength('256'))

            ->addElement($fb->createLabel()->setText('Code'))
            ->addElement($fb->createInput()->setType('text')->setName('code')->setMaxLength('256')->require())

            ->addElement($fb->createLabel()->setText('Page URL'))
            ->addElement($fb->createInput()->setType('text')->setName('page_url')->setMaxLength('256')->require())

            ->addElement($fb->createLabel()->setText('Parent'))
            ->addElement($fb->createSelect()->setName('parent')->addOptionsBasedOnArray($parentRibbonsArr))

            ->addElement($fb->createLabel()->setText('Image'))
            ->addElement($fb->createInput()->setType('text')->setName('title')->setMaxLength('256'))

            ->addElement($fb->createLabel()->setText('Is visible'))
            ->addElement($fb->createInput()->setType('checkbox')->setName('is_visible')->setSpecial('checked'))

            ->addElement($fb->createSubmit('Create'))
        ;

        return $fb->build();
    }

    private function internalCreateEditRibbonForm(Ribbon $ribbon) {
        global $app;

        $fb = FormBuilder::getTemporaryObject();

        $parentRibbons = null;

        $cm = CacheManager::getTemporaryObject(CacheCategories::RIBBONS);
        $valFromCache = $cm->loadTopRibbons();

        if(!is_null($valFromCache)) {
            $parentRibbons = $valFromCache;
        } else {
            $parentRibbons = $app->ribbonModel->getToppanelRibbons();
        }

        $parentRibbonsArr = [['value' => '0', 'text' => '- (root)']];
        foreach($parentRibbons as $parent) {
            $parentRibbon = array(
                'value' => $parent->getId(),
                'text' => $parent->getName() . ' (' . $parent->getCode() . ')'
            );

            if($parent->getId() == $ribbon->getIdParentRibbon()) {
                $parentRibbon['selected'] = 'selected';
            }

            $parentRibbonsArr[] = $parentRibbon;
        }

        $visible = $ribbon->isVisible() ? 'checked' : '';

        $fb
            ->setAction('?page=UserModule:RibbonSettings:processEditForm&id=' . $ribbon->getId())
            ->setMethod('POST')

            ->addElement($fb->createLabel()->setText('Name')->setFor('name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->setMaxLength('256')->require()->setValue($ribbon->getName()))

            ->addElement($fb->createLabel()->setText('Title (display name, can be same as Name)'))
            ->addElement($fb->createInput()->setType('text')->setName('title')->setMaxLength('256')->setValue($ribbon->getTitle(true) ?? ''))

            ->addElement($fb->createLabel()->setText('Code'))
            ->addElement($fb->createInput()->setType('text')->setName('code')->setMaxLength('256')->require()->setValue($ribbon->getCode()))

            ->addElement($fb->createLabel()->setText('Page URL'))
            ->addElement($fb->createInput()->setType('text')->setName('page_url')->setMaxLength('256')->require()->setValue($ribbon->getPageUrl()))

            ->addElement($fb->createLabel()->setText('Parent'))
            ->addElement($fb->createSelect()->setName('parent')->addOptionsBasedOnArray($parentRibbonsArr))

            ->addElement($fb->createLabel()->setText('Image'))
            ->addElement($fb->createInput()->setType('text')->setName('title')->setMaxLength('256')->setValue($ribbon->getImage() ?? ''))

            ->addElement($fb->createLabel()->setText('Is visible'))
            ->addElement($fb->createInput()->setType('checkbox')->setName('is_visible')->setSpecial($visible))

            ->addElement($fb->createSubmit('Create'))
        ;

        return $fb->build();
    }

    private function internalCreateUserRightsGrid(Ribbon $ribbon) {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $headers = array(
            'Actions',
            'User',
            'View',
            'Edit',
            'Delete'
        );
        
        $headerRow = null;
        
        $users = $app->userModel->getAllUsers();

        if(empty($users)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($users as $user) {
                $userRights = $app->ribbonRightsModel->getRibbonRightsForIdUser($ribbon->getId(), $user->getId());

                $actionLinks = array(
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:RibbonSettings:grantAllRibbonRightsToUser', 'id_ribbon' => $ribbon->getId(), 'id_user' => $user->getId()), 'Grant all'),
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:RibbonSettings:revokeAllRibbonRightsToUser', 'id_ribbon' => $ribbon->getId(), 'id_user' => $user->getId()), 'Revoke all')
                );

                if(is_null($headerRow)) {
                    $row = $tb->createRow();
    
                    foreach($headers as $header) {
                        $col = $tb->createCol()->setText($header)->setBold();
    
                        if($header == 'Actions') {
                            $col->setColspan(count($actionLinks));
                        }
    
                        $row->addCol($col);
                    }
    
                    $headerRow = $row;
                    $tb->addRow($row);
                }

                $rightRow = $tb->createRow();

                foreach($actionLinks as $actionLink) {
                    $rightRow->addCol($tb->createCol()->setText($actionLink));
                }

                $grant = function(string $name) use ($user, $ribbon) {
                    return '<a class="general-link" style="color: red" href="?page=UserModule:RibbonSettings:grantRibbonRightToUser&id_ribbon=' . $ribbon->getId() . '&id_user=' . $user->getId() . '&name=' . $name . '">No</a>';
                };

                $revoke = function(string $name) use ($user, $ribbon) {
                    return '<a class="general-link" style="color: green" href="?page=UserModule:RibbonSettings:revokeRibbonRightToUser&id_ribbon=' . $ribbon->getId() . '&id_user=' . $user->getId() . '&name=' . $name . '">Yes</a>';
                };

                $canSee = $grant('can_see');
                $canEdit = $grant('can_edit');
                $canDelete = $grant('can_delete');

                if($userRights !== FALSE && $userRights !== NULL) {
                    if($userRights['can_see'] == '1') {
                        $canSee = $revoke('can_see');
                    }

                    if($userRights['can_edit'] == '1') {
                        $canEdit = $revoke('can_edit');
                    }

                    if($userRights['can_delete'] == '1') {
                        $canDelete = $revoke('can_delete');
                    }
                }

                $data = array(
                    $user->getFullname(),
                    $canSee,
                    $canEdit, 
                    $canDelete
                );

                foreach($data as $d) {
                    $rightRow->addCol($tb->createCol()->setText($d));
                }

                $tb->addRow($rightRow);
            }
        }

        return $tb->build();
    }

    private function internalCreateGroupRightsGrid(Ribbon $ribbon) {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $headers = array(
            'Actions',
            'Group',
            'View',
            'Edit',
            'Delete'
        );

        $headerRow = null;

        $groups = $app->groupModel->getAllGroups();

        if(empty($groups)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($groups as $group) {
                $groupRights = $app->ribbonRightsModel->getRibbonRightsForIdGroup($ribbon->getId(), $group->getId());

                $actionLinks = array(
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:RibbonSettings:grantAllRibbonRightsToGroup', 'id_ribbon' => $ribbon->getId(), 'id_group' => $group->getId()), 'Grant all'),
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:RibbonSettings:revokeAllRibbonRightsToGroup', 'id_ribbon' => $ribbon->getId(), 'id_group' => $group->getId()), 'Revoke all')
                );

                if(is_null($headerRow)) {
                    $row = $tb->createRow();
    
                    foreach($headers as $header) {
                        $col = $tb->createCol()->setText($header)->setBold();
    
                        if($header == 'Actions') {
                            $col->setColspan(count($actionLinks));
                        }
    
                        $row->addCol($col);
                    }
    
                    $headerRow = $row;
                    $tb->addRow($row);
                }

                $rightRow = $tb->createRow();

                foreach($actionLinks as $actionLink) {
                    $rightRow->addCol($tb->createCol()->setText($actionLink));
                }

                $grant = function(string $name) use ($group, $ribbon) {
                    return '<a class="general-link" style="color: red" href="?page=UserModule:RibbonSettings:grantRibbonRightToGroup&id_ribbon=' . $ribbon->getId() . '&id_group=' . $group->getId() . '&name=' . $name . '">No</a>';
                };

                $revoke = function(string $name) use ($group, $ribbon) {
                    return '<a class="general-link" style="color: green" href="?page=UserModule:RibbonSettings:revokeRibbonRightToGroup&id_ribbon=' . $ribbon->getId() . '&id_group=' . $group->getId() . '&name=' . $name . '">Yes</a>';
                };

                $canSee = $grant('can_see');
                $canEdit = $grant('can_edit');
                $canDelete = $grant('can_delete');

                if($groupRights !== FALSE && $groupRights !== NULL) {
                    if($groupRights['can_see'] == '1') {
                        $canSee = $revoke('can_see');
                    }

                    if($groupRights['can_edit'] == '1') {
                        $canEdit = $revoke('can_edit');
                    }

                    if($groupRights['can_delete'] == '1') {
                        $canDelete = $revoke('can_delete');
                    }
                }

                $data = array(
                    $group->getName(),
                    $canSee,
                    $canEdit, 
                    $canDelete
                );

                foreach($data as $d) {
                    $rightRow->addCol($tb->createCol()->setText($d));
                }

                $tb->addRow($rightRow);
            }
        }

        return $tb->build();
    }
}

?>