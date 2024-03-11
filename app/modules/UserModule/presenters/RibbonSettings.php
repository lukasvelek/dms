<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\CacheCategories;
use DMS\Constants\UserActionRights;
use DMS\Core\CacheManager;
use DMS\Entities\Group;
use DMS\Entities\Ribbon;
use DMS\Entities\User;
use DMS\Models\AModel;
use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;

class RibbonSettings extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('RibbonSettings', 'Ribbon settings');

        $this->getActionNamesFromClass($this);
    }

    protected function showDropdownItems() {
        global $app;

        $app->flashMessageIfNotIsset(array('id'), true, array('page' => 'showAll'));

        $id = $this->get('id');
        $ribbon = $app->ribbonModel->getRibbonById($id);

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/settings/settings-grid.html');

        $data = array(
            '$PAGE_TITLE$' => 'Ribbon #' . $id . ' dropdown items',
            '$SETTINGS_GRID$' => $this->internalCreateRibbonDropdownItemsGrid($ribbon),
            '$LINKS$' => []
        );

        if($app->actionAuthorizator->checkActionRight(UserActionRights::CREATE_RIBBONS)) {
            $data['$LINKS$'][] = '&nbsp;&nbsp;' . LinkBuilder::createAdvLink(array('page' => 'showNewForm', 'is_dropdown' => '1', 'id_parent_ribbon' => $ribbon->getId()), 'New ribbon item');
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function deleteRibbon() {
        global $app;

        $app->flashMessageIfNotIsset(array('id'), true, array('page' => 'showAll'));
        
        $id = $this->get('id');
        
        $app->ribbonRightsModel->deleteAllGroupRibbonRights($id);
        $app->ribbonRightsModel->deleteAllUserRibbonRights($id);
        $app->ribbonModel->deleteRibbon($id);
        
        $app->flashMessage('Ribbon #' . $id . ' successfully deleted', 'success');

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

        $app->flashMessage('Ribbon and ribbon rights cache cleared');

        $app->redirect('showAll');
    }

    protected function revokeRibbonRightToGroup() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_ribbon', 'id_group', 'name'), true, array('page' => 'showAll'));

        $idRibbon = $this->get('id_ribbon');
        $idGroup = $this->get('id_group');
        $name = $this->get('name');

        $rights = array($name => '0');

        $app->ribbonRightsModel->updateGroupRights($idRibbon, $idGroup, $rights);

        $app->flashMessage('Updated rights for group #' . $idGroup . ' and ribbon #' . $idRibbon, 'success');
        $app->redirect('showEditGroupRightsForm', array('id' => $idRibbon));
    }

    protected function grantRibbonRightToGroup() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_ribbon', 'id_group', 'name'), true, array('page' => 'showAll'));

        $idRibbon = $this->get('id_ribbon');
        $idGroup = $this->get('id_group');
        $name = $this->get('name');

        $rights = array($name => '1');

        $app->ribbonRightsModel->updateGroupRights($idRibbon, $idGroup, $rights);

        $app->flashMessage('Updated rights for group #' . $idGroup . ' and ribbon #' . $idRibbon, 'success');
        $app->redirect('showEditGroupRightsForm', array('id' => $idRibbon));
    }

    protected function grantAllRibbonRightsToGroup() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_ribbon', 'id_group'), true, array('page' => 'showAll'));

        $idRibbon = $this->get('id_ribbon');
        $idGroup = $this->get('id_group');

        $rights = array(
            AModel::VIEW => '1',
            AModel::EDIT => '1',
            AModel::DELETE => '1'
        );

        $app->ribbonRightsModel->updateGroupRights($idRibbon, $idGroup, $rights);

        $app->flashMessage('Updated rights for group #' . $idGroup . ' and ribbon #' . $idRibbon, 'success');
        $app->redirect('showEditGroupRightsForm', array('id' => $idRibbon));
    }

    protected function revokeAllRibbonRightsToGroup() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_ribbon', 'id_group'), true, array('page' => 'showAll'));

        $idRibbon = $this->get('id_ribbon');
        $idGroup = $this->get('id_group');

        $rights = array(
            AModel::VIEW => '0',
            AModel::EDIT => '0',
            AModel::DELETE => '0'
        );

        $app->ribbonRightsModel->updateGroupRights($idRibbon, $idGroup, $rights);

        $app->flashMessage('Updated rights for group #' . $idGroup . ' and ribbon #' . $idRibbon, 'success');
        $app->redirect('showEditGroupRightsForm', array('id' => $idRibbon));
    }

    protected function revokeRibbonRightToUser() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_ribbon', 'id_user', 'name'));

        $idRibbon = $this->get('id_ribbon');
        $idUser = $this->get('id_user');
        $name = $this->get('name');

        $rights = array($name => '0');

        $app->ribbonRightsModel->updateUserRights($idRibbon, $idUser, $rights);

        $app->flashMessage('Updated rights for user #' . $idUser . ' and ribbon #' . $idRibbon, 'success');
        $app->redirect('showEditUserRightsForm', array('id' => $idRibbon));
    }

    protected function grantRibbonRightToUser() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_ribbon', 'id_user', 'name'));

        $idRibbon = $this->get('id_ribbon');
        $idUser = $this->get('id_user');
        $name = $this->get('name');

        $rights = array($name => '1');

        $app->ribbonRightsModel->updateUserRights($idRibbon, $idUser, $rights);

        $app->flashMessage('Updated rights for user #' . $idUser . ' and ribbon #' . $idRibbon, 'success');
        $app->redirect('showEditUserRightsForm', array('id' => $idRibbon));
    }

    protected function grantAllRibbonRightsToUser() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_ribbon', 'id_user'), true, array('page' => 'showAll'));

        $idRibbon = $this->get('id_ribbon');
        $idUser = $this->get('id_user');

        $rights = array(
            AModel::VIEW => '1',
            AModel::EDIT => '1',
            AModel::DELETE => '1'
        );

        $app->ribbonRightsModel->updateUserRights($idRibbon, $idUser, $rights);

        $app->flashMessage('Updated rights for user #' . $idUser . ' and ribbon #' . $idRibbon, 'success');
        $app->redirect('showEditUserRightsForm', array('id' => $idRibbon));
    }

    protected function revokeAllRibbonRightsToUser() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_ribbon', 'id_user'), true, array('page' => 'showAll'));

        $idRibbon = $this->get('id_ribbon');
        $idUser = $this->get('id_user');

        $rights = array(
            AModel::VIEW => '0',
            AModel::EDIT => '0',
            AModel::DELETE => '0'
        );

        $app->ribbonRightsModel->updateUserRights($idRibbon, $idUser, $rights);

        $app->flashMessage('Updated rights for user #' . $idUser . ' and ribbon #' . $idRibbon, 'success');
        $app->redirect('showEditUserRightsForm', array('id' => $idRibbon));
    }

    protected function showEditGroupRightsForm() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-grid.html');

        $app->flashMessageIfNotIsset(array('id'), true, array('page' => 'showAll'));
        $id = $this->get('id');

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

        $data['$LINKS$'][] = LinkBuilder::createLink('showAll', '&larr;');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showEditUserRightsForm() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-grid.html');

        $app->flashMessageIfNotIsset(array('id'), true, array('page' => 'showAll'));
        $id = $this->get('id');

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

        $data['$LINKS$'][] = LinkBuilder::createLink('showAll', '&larr;');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function processEditForm() {
        global $app;

        $app->flashMessageIfNotIsset(array('id', 'name', 'code', 'page_url', 'parent'), true, array('page' => 'showAll'));
        $id = $this->get('id');

        $data = [];
        $data['name'] = $this->post('name');
        $data['code'] = $this->post('code');
        $data['page_url'] = $this->post('page_url');

        if($_POST['parent'] != '0') {
            $data['id_parent_ribbon'] = $this->post('parent');
        }

        if(isset($_POST['title']) && $_POST['title'] != '') {
            $data['title'] = $this->post('title');
        }

        if(isset($_POST['image'])) {
            $data['image'] = $this->post('image');
        }

        if(isset($_POST['is_visible'])) {
            $data['is_visible'] = '1';
        } else {
            $data['is_visible'] = '0';
        }

        $app->ribbonModel->updateRibbon($id, $data);

        $app->flashMessage('Successfully edited ribbon #' . $id);

        $app->redirect('showAll');
    }

    protected function showEditForm() {
        global $app;

        $app->flashMessageIfNotIsset(array('id'), true, array('page' => 'showAll'));
        $id = $this->get('id');

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $ribbon = $app->ribbonModel->getRibbonById($id);

        $data = array(
            '$PAGE_TITLE$' => 'Ribbon #' . $id . ' edit form',
            '$FORM$' => $this->internalCreateEditRibbonForm($ribbon)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function processNewForm() {
        global $app;

        $app->flashMessageIfNotIsset(array('name', 'code', 'parent', 'page_url'), true, array('page' => 'showNewForm'));

        $data = [];
        $data['name'] = $this->post('name');
        $data['code'] = $this->post('code');
        $data['page_url'] = $this->post('page_url');
        $data['is_system'] = '0';

        if(isset($_POST['parent'])) {
            if($_POST['parent'] != '0') {
                $data['id_parent_ribbon'] = $this->post('parent');
            }
        } else if(isset($_GET['parent'])) {
            if($_GET['parent'] != '0') {
                $data['id_parent_ribbon'] = $this->get('parent');
            }
        }

        if(isset($_POST['title'])) {
            $data['title'] = $this->post('title');
        }

        if(isset($_POST['image'])) {
            $data['image'] = $this->post('image');
        }

        if(isset($_POST['is_visible'])) {
            $data['is_visible'] = '1';
        } else {
            $data['is_visible'] = '0';
        }

        if(isset($_POST['is_dropdown'])) {
            $data['page_url'] = 'js.showDropdownMenu(&apos;$ID_PARENT_RIBBON$&apos;, &apos;$ID_RIBBON$&apos;);';
        }

        $app->ribbonModel->insertNewRibbon($data);
        $idRibbon = $app->ribbonModel->getLastInsertedRibbonId();

        if($idRibbon === FALSE) {
            die();
        }

        $admGroup = $app->groupModel->getGroupByCode('ADMINISTRATORS');
        $app->ribbonRightsModel->insertAllGrantedRightsForGroup($idRibbon, $admGroup->getId());

        // current user
        $admin = $app->userModel->getUserByUsername('admin');
        $app->ribbonRightsModel->insertAllGrantedRightsForUser($idRibbon, $admin->getId());
        $app->ribbonRightsModel->insertAllGrantedRightsForUser($idRibbon, $app->user->getId());

        $app->flashMessage('Created new ribbon', 'success');

        $app->redirect('showAll');
    }

    protected function processNewSplitterForm() {
        global $app;

        $app->flashMessageIfNotIsset(['parent']);

        $idParent = $this->post('parent');
        $parent = $app->ribbonModel->getRibbonById($idParent);

        $splitterCount = $app->ribbonModel->getSplitterCountForIdParent($idParent);

        $data = [];
        $data['id_parent_ribbon'] = $idParent;
        $data['name'] = 'SPLITTER';
        $data['code'] = $parent->getCode() . '.splitter' . $splitterCount;
        $data['is_system'] = '0';
        $data['page_url'] = '#';

        $app->ribbonModel->insertNewRibbon($data);
        $idRibbon = $app->ribbonModel->getLastInsertedRibbonId();

        if($idRibbon === FALSE) {
            die();
        }

        $admGroup = $app->groupModel->getGroupByCode('ADMINISTRATORS');
        $app->ribbonRightsModel->insertAllGrantedRightsForGroup($idRibbon, $admGroup->getId());

        $admin = $app->userModel->getUserByUsername('admin');
        $app->ribbonRightsModel->insertAllGrantedRightsForUser($idRibbon, $admin->getId());
        $app->ribbonRightsModel->insertAllGrantedRightsForUser($idRibbon, $app->user->getId());

        $app->flashMessage('Successfully created a new splitter', 'success');
        $app->redirect('showAll');
    }

    protected function showNewForm() {
        global $app;

        $isDropdown = false;
        $idParentRibbon = null;

        if(isset($_GET['is_dropdown'])) {
            $app->flashMessageIfNotIsset(['id_parent_ribbon']);
            $idParentRibbon = $this->get('id_parent_ribbon');

            $isDropdown = true;
        }

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'New ' . ($isDropdown ? 'dropdown ' : '') . 'ribbon form',
            '$FORM$' => $this->internalCreateNewRibbonForm($isDropdown, $idParentRibbon)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showNewSplitterForm() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'New splitter form',
            '$FORM$' => $this->internalCreateNewSplitterForm()
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
        
        $app->redirect('showAll');
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

        if($app->actionAuthorizator->checkActionRight(UserActionRights::CREATE_RIBBONS)) {
            $data['$LINKS$'][] = LinkBuilder::createAdvLink(array('page' => 'showNewForm'), 'New ribbon');
            $data['$LINKS$'][] = '&nbsp;&nbsp;' . LinkBuilder::createAdvLink(array('page' => 'showNewSplitterForm'), 'New splitter');
        }

        if($app->actionAuthorizator->checkActionRight(UserActionRights::DELETE_RIBBON_CACHE)) {
            $data['$LINKS$'][] = '&nbsp;&nbsp;' . LinkBuilder::createAdvLink(array('page' => 'clearCache'), 'Clear cache');
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateRibbonGrid() {
        global $app;

        $ribbonModel = $app->ribbonModel;
        $idUser = $app->user->getId();

        $editableRibbons = $app->ribbonAuthorizator->getEditableRibbonsForIdUser($idUser);
        $deletableRibbons = $app->ribbonAuthorizator->getDeletableRibbonsForIdUser($idUser);

        $canUserEdit = $app->actionAuthorizator->checkActionRight(UserActionRights::EDIT_RIBBONS);
        $canUserDelete = $app->actionAuthorizator->checkActionRight(UserActionRights::DELETE_RIBBONS);
        $canUserEditRights = $app->actionAuthorizator->checkActionRight(UserActionRights::EDIT_RIBBON_RIGHTS);

        $data = function() use ($ribbonModel) {
            $ribbons = $ribbonModel->getAllRibbons(true);

            return $ribbons;
        };

        $gb = new GridBuilder();

        $gb->addColumns(['name' => 'Name', 'code' => 'Code', 'isVisible' => 'Visible', 'url' => 'URL']);
        $gb->addDataSourceCallback($data);
        $gb->addOnColumnRender('isVisible', function(Ribbon $ribbon) {
            return $ribbon->isVisible() ? '<span style="color: green">Yes</span>' : '<span style="color: red">No</span>';
        });
        $gb->addOnColumnRender('url', function(Ribbon $ribbon) {
            return $ribbon->getPageUrl();
        });
        $gb->addAction(function(Ribbon $ribbon) use ($editableRibbons, $canUserEdit) {
            $link = '-';
            if(in_array($ribbon->getId(), $editableRibbons) &&
               $canUserEdit &&
               $ribbon->getName() != 'SPLITTER') {
                $link = LinkBuilder::createAdvLink(array('page' => 'showEditForm', 'id' => $ribbon->getId()), 'Edit');
            }
            return $link;
        });
        $gb->addAction(function(Ribbon $ribbon) use ($editableRibbons, $canUserEdit) {
            $link = '-';
            if(in_array($ribbon->getId(), $editableRibbons) &&
               $canUserEdit &&
               $ribbon->getName() != 'SPLITTER') {
                if($ribbon->isJS()) {
                    $link = LinkBuilder::createAdvLink(array('page' => 'showDropdownItems', 'id' => $ribbon->getId()), 'Edit dropdown items');
                }
            }
            return $link;
        });
        $gb->addAction(function(Ribbon $ribbon) use ($deletableRibbons, $canUserDelete) {
            $link = '-';
            if(in_array($ribbon->getId(), $deletableRibbons) &&
               $canUserDelete &&
               $ribbon->isSystem() === FALSE) {
                $link = LinkBuilder::createAdvLink(array('page' => 'deleteRibbon', 'id' => $ribbon->getId()), 'Delete');
            }
            return $link;
        });
        $gb->addAction(function(Ribbon $ribbon) use ($canUserEditRights) {
            $link = '-';
            if($canUserEditRights &&
               $ribbon->getName() != 'SPLITTER') {
                $link = LinkBuilder::createAdvLink(array('page' => 'showEditUserRightsForm', 'id' => $ribbon->getId()), 'Edit user rights');
            }
            return $link;
        });
        $gb->addAction(function(Ribbon $ribbon) use ($canUserEditRights) {
            $link = '-';
            if($canUserEditRights &&
               $ribbon->getName() != 'SPLITTER') {
                $link = LinkBuilder::createAdvLink(array('page' => 'showEditGroupRightsForm', 'id' => $ribbon->getId()), 'Edit group rights');
            }
            return $link;
        });

        return $gb->build();
    }

    private function internalCreateNewSplitterForm() {
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

        $fb ->setMethod('POST')->setAction('?page=UserModule:RibbonSettings:processNewSplitterForm')
            
            ->addElement($fb->createLabel()->setText('Parent')->setFor('parent'))
            ->addElement($fb->createSelect()->setName('parent')->addOptionsBasedOnArray($parentRibbonsArr))

            ->addElement($fb->createSubmit('Create'));

        return $fb->build();
    }

    private function internalCreateNewRibbonForm(bool $isDropdown, ?int $idParentRibbon) {
        global $app;

        $fb = FormBuilder::getTemporaryObject();

        $parentRibbons = null;
        $parentRibbonsArr = [];

        if(!$isDropdown) {
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
        } else {
            if($idParentRibbon != null) {
                $parentRibbons = $app->ribbonModel->getRibbonById($idParentRibbon);

                $parentRibbonsArr[] = array(
                    'value' => $parentRibbons->getId(),
                    'text' => $parentRibbons->getName() . ' (' . $parentRibbons->getCode() . ')'
                );
            }
        }

        $fb
            ->setAction('?page=UserModule:RibbonSettings:processNewForm' . ($isDropdown ? '&parent=' . $idParentRibbon : ''))
            ->setMethod('POST')

            ->addElement($fb->createLabel()->setText('Name')->setFor('name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->setMaxLength('256')->require())

            ->addElement($fb->createLabel()->setText('Title (display name, can be same as Name)'))
            ->addElement($fb->createInput()->setType('text')->setName('title')->setMaxLength('256'))

            ->addElement($fb->createLabel()->setText('Code')->setFor('code'))
            ->addElement($fb->createInput()->setType('text')->setName('code')->setMaxLength('256')->require())

            ->addElement($fb->createLabel()->setText('Page URL')->setFor('page_url'))
            ->addElement($fb->createInput()->setType('text')->setName('page_url')->setMaxLength('256')->require());

        if(!$isDropdown) {
            $fb ->addElement($fb->createLabel()->setText('Parent')->setFor('parent'))
                ->addElement($fb->createSelect()->setName('parent')->addOptionsBasedOnArray($parentRibbonsArr))

                ->addElement($fb->createLabel()->setText('Image')->setFor('image'))
                ->addElement($fb->createInput()->setType('text')->setName('image')->setMaxLength('256'))
            ;
        } else {
            $fb ->addElement($fb->createLabel()->setText('Parent')->setFor('parent'))
                ->addElement($fb->createSelect()->setName('parent')->addOptionsBasedOnArray($parentRibbonsArr)->readonly());
        }

        $fb ->addElement($fb->createLabel()->setText('Is visible')->setFor('is_visible'))
            ->addElement($fb->createInput()->setType('checkbox')->setName('is_visible')->setSpecial('checked'));

        if(!$isDropdown) {
            $fb ->addElement($fb->createLabel()->setText('Is dropdown (will override <i>Page URL</i>)')->setFor('is_dropdown'))
                ->addElement($fb->createInput()->setType('checkbox')->setName('is_dropdown'))
            ;
        }

        $fb->addElement($fb->createSubmit('Create'));

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

        $pageUrl = $fb->createInput()->setType('text')->setName('page_url')->setMaxLength('256')->require()->setValue($ribbon->getPageUrl());

        if($ribbon->isJS()) {
            $pageUrl->readonly();
        }

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
            ->addElement($pageUrl)
            
            ->addElement($fb->createLabel()->setText('Parent'))
            ->addElement($fb->createSelect()->setName('parent')->addOptionsBasedOnArray($parentRibbonsArr))
            
            ->addElement($fb->createLabel()->setText('Image'))
            ->addElement($fb->createInput()->setType('text')->setName('image')->setMaxLength('256')->setValue($ribbon->getImage() ?? ''))
            
            ->addElement($fb->createLabel()->setText('Is visible'))
            ->addElement($fb->createInput()->setType('checkbox')->setName('is_visible')->setSpecial($visible))
            
            ->addElement($fb->createSubmit('Save'))
            ;

        return $fb->build();
    }

    private function internalCreateUserRightsGrid(Ribbon $ribbon) {
        global $app;

        $userModel = $app->userModel;
        $ribbonRightsModel = $app->ribbonRightsModel;

        $data = function() use ($userModel) {
            return $userModel->getAllUsers();
        };

        $grant = function(string $name, User $user) use ($ribbon) {
            return '<a class="general-link" style="color: red" href="?page=UserModule:RibbonSettings:grantRibbonRightToUser&id_ribbon=' . $ribbon->getId() . '&id_user=' . $user->getId() . '&name=' . $name . '">No</a>';
        };

        $revoke = function(string $name, User $user) use ($ribbon) {
            return '<a class="general-link" style="color: green" href="?page=UserModule:RibbonSettings:revokeRibbonRightToUser&id_ribbon=' . $ribbon->getId() . '&id_user=' . $user->getId() . '&name=' . $name . '">Yes</a>';
        };

        $userRightsArr = [];
        foreach($data() as $user) {
            $userRights = $ribbonRightsModel->getRibbonRightsForIdUser($ribbon->getId(), $user->getId());

            if($userRights === NULL) {
                continue;
            }

            $canSee = 0;
            $canEdit = 0;
            $canDelete = 0;
            foreach($userRights as $ur) {
                $canSee = $ur['can_see'];
                $canEdit = $ur['can_edit'];
                $canDelete = $ur['can_delete'];
                break;
            }

            $userRightsArr[$user->getId()] = array(
                'can_see' => $canSee,
                'can_edit' => $canEdit,
                'can_delete' => $canDelete
            );
        }

        $gb = new GridBuilder();

        $gb->addColumns(['user' => 'User', 'view' => 'View', 'edit' => 'Edit', 'delete' => 'Delete']);
        $gb->addDataSourceCallback($data);
        $gb->addOnColumnRender('user', function(User $user) {
            return $user->getFullname();
        });
        $gb->addOnColumnRender('view', function(User $user) use ($userRightsArr, $grant, $revoke) {
            $link = $grant('can_see', $user);
            if(array_key_exists($user->getId(), $userRightsArr) && $userRightsArr[$user->getId()]['can_see'] == '1') {
                $link = $revoke('can_see', $user);
            }
            return $link;
        });
        $gb->addOnColumnRender('edit', function(User $user) use ($userRightsArr, $grant, $revoke) {
            $link = $grant('can_edit', $user);
            if(array_key_exists($user->getId(), $userRightsArr) && $userRightsArr[$user->getId()]['can_edit'] == '1') {
                $link = $revoke('can_edit', $user);
            }
            return $link;
        });
        $gb->addOnColumnRender('delete', function(User $user) use ($userRightsArr, $grant, $revoke) {
            $link = $grant('can_delete', $user);;
            if(array_key_exists($user->getId(), $userRightsArr) && $userRightsArr[$user->getId()]['can_delete'] == '1') {
                $link = $revoke('can_delete', $user);
            }
            return $link;
        });
        
        return $gb->build();
    }

    private function internalCreateGroupRightsGrid(Ribbon $ribbon) {
        global $app;

        $groupModel = $app->groupModel;
        $ribbonRightsModel = $app->ribbonRightsModel;

        $data = function() use ($groupModel) {
            return $groupModel->getAllGroups();
        };

        $grant = function(string $name, Group $group) use ($ribbon) {
            return '<a class="general-link" style="color: red" href="?page=UserModule:RibbonSettings:grantRibbonRightToGroup&id_ribbon=' . $ribbon->getId() . '&id_group=' . $group->getId() . '&name=' . $name . '">No</a>';
        };

        $revoke = function(string $name, Group $group) use ($ribbon) {
            return '<a class="general-link" style="color: green" href="?page=UserModule:RibbonSettings:revokeRibbonRightToGroup&id_ribbon=' . $ribbon->getId() . '&id_group=' . $group->getId() . '&name=' . $name . '">Yes</a>';
        };

        $groupRightsArr = [];
        foreach($data() as $group) {
            $groupRights = $ribbonRightsModel->getRibbonRightsForIdGroup($ribbon->getId(), $group->getId());

            if($groupRights === NULL) {
                continue;
            }

            $canSee = 0;
            $canEdit = 0;
            $canDelete = 0;
            foreach($groupRights as $gr) {
                $canSee = $gr['can_see'];
                $canEdit = $gr['can_edit'];
                $canDelete = $gr['can_delete'];
                break;
            }

            $groupRightsArr[$group->getId()] = array(
                'can_see' => $canSee,
                'can_edit' => $canEdit,
                'can_delete' => $canDelete
            );
        }

        $gb = new GridBuilder();

        $gb->addColumns(['group' => 'Group', 'view' => 'View', 'edit' => 'Edit', 'delete' => 'Delete']);
        $gb->addDataSourceCallback($data);
        $gb->addOnColumnRender('group', function(Group $group) {
            return $group->getName();
        });
        $gb->addOnColumnRender('view', function(Group $group) use ($groupRightsArr, $grant, $revoke) {
            $link = $grant('can_see', $group);
            if(array_key_exists($group->getId(), $groupRightsArr) && $groupRightsArr[$group->getId()]['can_see'] == '1') {
                $link = $revoke('can_see', $group);
            }
            return $link;
        });
        $gb->addOnColumnRender('edit', function(Group $group) use ($groupRightsArr, $grant, $revoke) {
            $link = $grant('can_edit', $group);
            if(array_key_exists($group->getId(), $groupRightsArr) && $groupRightsArr[$group->getId()]['can_edit'] == '1') {
                $link = $revoke('can_edit', $group);
            }
            return $link;
        });
        $gb->addOnColumnRender('delete', function(Group $group) use ($groupRightsArr, $grant, $revoke) {
            $link = $grant('cen_delete', $group);
            if(array_key_exists($group->getId(), $groupRightsArr) && $groupRightsArr[$group->getId()]['can_delete'] == '1') {
                $link = $revoke('can_delete', $group);
            }
            return $link;
        });

        return $gb->build();
    }

    private function internalCreateRibbonDropdownItemsGrid(Ribbon $ribbon) {
        global $app;

        $ribbonModel = $app->ribbonModel;
        $ribbonAuthorizator = $app->ribbonAuthorizator;
        $actionAuthorizator = $app->actionAuthorizator;
        $idUser = $app->user->getId();

        $data = function() use ($ribbonModel, $ribbon) {
            $ribbons = $ribbonModel->getRibbonsForIdParentRibbon($ribbon->getId());

            return $ribbons;
        };

        $gb = new GridBuilder();

        $gb->addColumns(['name' => 'Name', 'code' => 'Code', 'isVisible' => 'Visible', 'url' => 'URL']);
        $gb->addDataSourceCallback($data);
        $gb->addOnColumnRender('isVisible', function(Ribbon $ribbon) {
            return $ribbon->isVisible() ? '<span style="color: green">Yes</span>' : '<span style="color: red">No</span>';
        });
        $gb->addOnColumnRender('url', function(Ribbon $ribbon) {
            return $ribbon->getPageUrl();
        });
        $gb->addAction(function(Ribbon $ribbon) use ($ribbonAuthorizator, $actionAuthorizator, $idUser) {
            $link = '-';
            if($ribbonAuthorizator->checkRibbonEditable($idUser, $ribbon) &&
               $actionAuthorizator->checkActionRight(UserActionRights::EDIT_RIBBONS) &&
               $ribbon->getName() != 'SPLITTER') {
                $link = LinkBuilder::createAdvLink(array('page' => 'showEditForm', 'id' => $ribbon->getId()), 'Edit');
            }
            return $link;
        });
        $gb->addAction(function(Ribbon $ribbon) use ($ribbonAuthorizator, $actionAuthorizator, $idUser) {
            $link = '-';
            if($ribbonAuthorizator->checkRibbonDeletable($idUser, $ribbon) &&
               $actionAuthorizator->checkActionRight(UserActionRights::DELETE_RIBBONS)) {
                $link = LinkBuilder::createAdvLink(array('page' => 'deleteRibbon', 'id' => $ribbon->getId()), 'Delete');
            }
            return $link;
        });
        $gb->addAction(function(Ribbon $ribbon) use ($actionAuthorizator) {
            $link = '-';
            if($actionAuthorizator->checkActionRight(UserActionRights::EDIT_RIBBON_RIGHTS) &&
               $ribbon->getName() != 'SPLITTER') {
                $link = LinkBuilder::createAdvLink(array('page' => 'showEditUserRightsForm', 'id' => $ribbon->getId()), 'Edit user rights');
            }
            return $link;
        });
        $gb->addAction(function(Ribbon $ribbon) use ($actionAuthorizator) {
            $link = '-';
            if($actionAuthorizator->checkActionRight(UserActionRights::EDIT_RIBBON_RIGHTS) &&
               $ribbon->getName() != 'SPLITTER') {
                $link = LinkBuilder::createAdvLink(array('page' => 'showEditGroupRightsForm', 'id' => $ribbon->getId()), 'Edit group rights');
            }
            return $link;
        });

        return $gb->build();
    }
}

?>