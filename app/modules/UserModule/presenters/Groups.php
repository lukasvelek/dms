<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\BulkActionRights;
use DMS\Constants\CacheCategories;
use DMS\Constants\PanelRights;
use DMS\Constants\UserActionRights;
use DMS\Constants\UserStatus;
use DMS\Core\CacheManager;
use DMS\Entities\EntityRight;
use DMS\Entities\Group;
use DMS\Entities\GroupUser;
use DMS\Entities\Ribbon;
use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;

class Groups extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('Groups');

        $this->getActionNamesFromClass($this);
    }

    protected function showRibbonRights() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);

        $template = $this->loadTemplate(__DIR__ . '/templates/groups/group-rights-grid.html');

        $idGroup = $this->get('id');
        $group = $app->groupModel->getGroupById($idGroup);

        $data = [
            '$PAGE_TITLE$' => 'Ribbon rights for group <i>' . $group->getName() . '</i>',
            '$BACK_LINK$' => '',
            '$LINKS$' => [LinkBuilder::createAdvLink(['page' => 'Settings:showGroups'], '&larr;')],
            '$GROUP_RIGHTS_GRID$' => $this->internalCreateRibbonRights($group)
        ];

        $this->fill($data, $template);

        return $template;
    }

    protected function enableRibbonRight() {
        global $app;

        $app->flashMessageIfNotIsset(['id_ribbon_update', 'id_group', 'action']);
        $idRibbon = $this->get('id_ribbon_update');
        $idGroup = $this->get('id_group');
        $action = $this->get('action');

        $app->ribbonRightsModel->updateGroupRights($idRibbon, $idGroup, [$action => '1']);

        $app->logger->info('Enabled ribbon right for ribbon #' . $idRibbon . ' to group #' . $idGroup, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::RIBBON_GROUP_RIGHTS);
        $cm->invalidateCache();

        $app->redirect('showRibbonRights', ['id' => $idGroup]);
    }

    protected function disableRibbonRight() {
        global $app;

        $app->flashMessageIfNotIsset(['id_ribbon_update', 'id_group', 'action']);
        $idRibbon = $this->get('id_ribbon_update');
        $idGroup = $this->get('id_group');
        $action = $this->get('action');

        $app->ribbonRightsModel->updateGroupRights($idRibbon, $idGroup, [$action => '0']);

        $app->logger->info('Enabled ribbon right for ribbon #' . $idRibbon . ' to group #' . $idGroup, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::RIBBON_GROUP_RIGHTS);
        $cm->invalidateCache();

        $app->redirect('showRibbonRights', ['id' => $idGroup]);
    }

    protected function showNewUserForm() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);
        
        $idGroup = $this->get('id');
        $group = $app->groupModel->getGroupById($idGroup);

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/groups/group-new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'Add user to group <i>' . $group->getName() . '</i>',
            '$FORM$' => $this->internalCreateNewUserForm($idGroup)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showUsers() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/groups/groups-grid.html');

        $app->flashMessageIfNotIsset(['id']);
        
        $id = $this->get('id');
        $group = $app->groupModel->getGroupById($id);

        $data = array(
            '$PAGE_TITLE$' => 'Users in group <i>' . $group->getName() . '</i>',
            '$GROUP_GRID$' => $this->internalCreateGroupGrid($id),
            '$LINKS$' => []
        );

        $data['$LINKS$'][] = LinkBuilder::createLink('Settings:showGroups', '&larr;') . '&nbsp;&nbsp;';
        $data['$LINKS$'][] = LinkBuilder::createAdvLink(array('page' => 'showNewUserForm', 'id' => $id), 'Add user');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showGroupRights() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/groups/group-rights-grid.html');

        $app->flashMessageIfNotIsset(['id', 'filter']);

        $id = $this->get('id');
        $filter = $this->get('filter');
        $group = $app->groupModel->getGroupById($id);

        $data = array(
            '$PAGE_TITLE$' => '<i>' . $group->getName() . '</i> rights',
            '$GROUP_RIGHTS_GRID$' => $this->internalCreateGroupRightsGrid($id, $filter),
            '$BACK_LINK$' => LinkBuilder::createAdvLink(array('page' => 'Settings:showGroups'), '&larr;')
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function allowActionRight() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'name']);

        $name = $this->get('name');
        $idGroup = $this->get('id');

        if($app->groupRightModel->checkActionRightExists($idGroup, $name) === TRUE) {
            $app->groupRightModel->updateActionRight($idGroup, $name, true);
        } else {
            $app->groupRightModel->insertActionRightForIdGroup($idGroup, $name, true);
        }

        $app->logger->info('Allowed action right \'' . $name . '\' to group #' . $idGroup, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::ACTIONS);
        $cm->invalidateCache();

        $app->redirect('showGroupRights', array('id' => $idGroup, 'filter' => 'actions'), $name);
    }

    protected function denyActionRight() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'name']);

        $name = $this->get('name');
        $idGroup = $this->get('id');

        if($app->groupRightModel->checkActionRightExists($idGroup, $name) === TRUE) {
            $app->groupRightModel->updateActionRight($idGroup, $name, false);
        } else {
            $app->groupRightModel->insertActionRightForIdGroup($idGroup, $name, false);
        }

        $app->logger->info('Denied action right \'' . $name . '\' to group #' . $idGroup, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::ACTIONS);
        $cm->invalidateCache();

        $app->redirect('showGroupRights', array('id' => $idGroup, 'filter' => 'actions'), $name);
    }

    protected function allowPanelRight() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'name']);

        $name = $this->get('name');
        $idGroup = $this->get('id');

        if($app->groupRightModel->checkPanelRightExists($idGroup, $name) === TRUE) {
            $app->groupRightModel->updatePanelRight($idGroup, $name, true);
        } else {
            $app->groupRightModel->insertPanelRightForIdGroup($idGroup, $name, true);
        }

        $app->logger->info('Allowed panel right \'' . $name . '\' to group #' . $idGroup, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::PANELS);
        $cm->invalidateCache();

        $app->redirect('showGroupRights', array('id' => $idGroup, 'filter' => 'panels'), $name);
    }

    protected function denyPanelRight() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'name']);

        $name = $this->get('name');
        $idGroup = $this->get('id');

        if($app->groupRightModel->checkPanelRightExists($idGroup, $name) === TRUE) {
            $app->groupRightModel->updatePanelRight($idGroup, $name, false);
        } else {
            $app->groupRightModel->insertPanelRightForIdGroup($idGroup, $name, false);
        }

        $app->logger->info('Denied panel right \'' . $name . '\' to group #' . $idGroup, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::PANELS);
        $cm->invalidateCache();

        $app->redirect('showGroupRights', array('id' => $idGroup, 'filter' => 'panels'), $name);
    }

    protected function allowBulkActionRight() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'name']);

        $name = $this->get('name');
        $idGroup = $this->get('id');

        if($app->groupRightModel->checkBulkActionRightExists($idGroup, $name) === TRUE) {
            $app->groupRightModel->updateBulkActionRight($idGroup, $name, true);
        } else {
            $app->groupRightModel->insertBulkActionRightForIdGroup($idGroup, $name, true);
        }

        $app->logger->info('Allowed bulk action right \'' . $name . '\' to group #' . $idGroup, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::BULK_ACTIONS);
        $cm->invalidateCache();

        $app->redirect('showGroupRights', array('id' => $idGroup, 'filter' => 'bulk_actions'), $name);
    }

    protected function denyBulkActionRight() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'name']);

        $name = $this->get('name');
        $idGroup = $this->get('id');

        if($app->groupRightModel->checkBulkActionRightExists($idGroup, $name) === TRUE) {
            $app->groupRightModel->updateBulkActionRight($idGroup, $name, false);
        } else {
            $app->groupRightModel->insertBulkActionRightForIdGroup($idGroup, $name, false);
        }

        $app->logger->info('Denied bulk action right \'' . $name . '\' to group #' . $idGroup, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::BULK_ACTIONS);
        $cm->invalidateCache();

        $app->redirect('showGroupRights', array('id' => $idGroup, 'filter' => 'bulk_actions'), $name);
    }

    protected function addUserToGroup() {
        global $app;

        $app->flashMessageIfNotIsset(['id_group', 'user']);

        $idGroup = $this->get('id_group');
        $idUser = $this->post('user');

        $app->groupUserModel->insertUserToGroup($idGroup, $idUser);

        $app->logger->info('Added user #' . $idUser . ' to group #' . $idGroup, __METHOD__);

        CacheManager::invalidateAllCache();
        
        $app->redirect('showUsers', array('id' => $idGroup));
    }

    protected function removeUserFromGroup() {
        global $app;

        $app->flashMessageIfNotIsset(['id_group', 'id_user']);

        $idGroup = $this->get('id_group');
        $idUser = $this->get('id_user');

        $app->groupUserModel->removeUserFromGroup($idGroup, $idUser);
        
        $app->logger->info('Removed user #' . $idUser . ' from group #' . $idGroup, __METHOD__);

        CacheManager::invalidateAllCache();

        $app->redirect('showUsers', array('id' => $idGroup));
    }

    protected function setUserAsManager() {
        global $app;

        $app->flashMessageIfNotIsset(['id_group', 'id_user']);

        $idGroup = $this->get('id_group');
        $idUser = $this->get('id_user');

        $groupUsers = $app->groupUserModel->getGroupUsersByGroupId($idGroup);

        $app->logger->info('Set user #' . $idUser . ' as the manager of group #' . $idGroup, __METHOD__);

        $idGroupManager = 0;

        foreach($groupUsers as $gu) {
            if($gu->getIsManager()) {
                $idGroupManager = $gu->getId();
            }
        }

        if($idGroupManager != 0) {
            $app->groupUserModel->updateUserInGroup($idGroup, $idGroupManager, array('is_manager' => '0'));
        }

        $app->groupUserModel->updateUserInGroup($idGroup, $idUser, array('is_manager' => '1'));

        $app->redirect('showUsers', array('id' => $idGroup));
    }

    protected function unsetUserAsManager() {
        global $app;

        $app->flashMessageIfNotIsset(['id_group', 'id_user']);

        $idGroup = $this->get('id_group');
        $idUser = $this->get('id_user');

        $app->groupUserModel->updateUserInGroup($idGroup, $idUser, array('is_manager' => '0'));

        $app->logger->info('Unset user #' . $idUser . ' as the manager of group #' . $idGroup, __METHOD__);

        $app->redirect('showUsers', array('id' => $idGroup));
    }

    private function internalCreateNewUserForm(int $idGroup) {
        global $app;

        $fb = FormBuilder::getTemporaryObject();

        $users = $app->userModel->getAllUsers();
        $groupUsers = $app->groupUserModel->getGroupUsersByGroupId($idGroup);

        $groupUsersId = [];
        foreach($groupUsers as $gu) {
            $groupUsersId[] = $gu->getIdUser();
        }

        $userArr = [];
        foreach($users as $u) {
            if(in_array($u->getId(), $groupUsersId)) {
                continue;
            }

            $userArr[] = array(
                'value' => $u->getId(),
                'text' => $u->getFullname()
            );
        }

        $fb ->setMethod('POST')->setAction('?page=UserModule:Groups:addUserToGroup&id_group=' . $idGroup)
            ->addElement($fb->createLabel()->setText('User')
                                           ->setFor('user'))
            ->addElement($fb->createSelect()->setName('user')
                                            ->addOptionsBasedOnArray($userArr))
            ->addElement($fb->createSubmit('Add'));

        $form = $fb->build();

        return $form;
    }

    private function internalCreateGroupRightsGrid(int $idGroup, string $filter) {
        global $app;

        $groupRightModel = $app->groupRightModel;

        $dataSourceCallback = function() use ($groupRightModel, $filter, $idGroup) {
            $rights = [];
            switch($filter) {
                case 'actions':
                    $defaultActionRights = UserActionRights::$all;
                    $actionRights = $groupRightModel->getActionRightsForIdGroup($idGroup);
    
                    foreach($defaultActionRights as $dar)  {
                        $rights[$dar] = new EntityRight('action', $dar, false);
                    }
    
                    foreach($actionRights as $name => $value) {
                        if(array_key_exists($name, $rights)) {
                            $rights[$name]->setValue(($value == '1'));
                        }
                    }
    
                    break;
                
                case 'bulk_actions':
                    $defaultBulkActionRights = BulkActionRights::$all;
                    $bulkActionRights = $groupRightModel->getBulkActionRightsForIdGroup($idGroup);
    
                    foreach($defaultBulkActionRights as $dbar) {
                        $rights[$dbar] = new EntityRight('bulk', $dbar, false);
                    }
            
                    foreach($bulkActionRights as $name => $value) {
                        if(array_key_exists($name, $rights)) {
                            $rights[$name]->setValue(($value == '1'));
                        }
                    }
    
                    break;
    
                case 'panels':
                    $defaultPanelRights = PanelRights::$all;
                    $panelRights = $groupRightModel->getPanelRightsForIdGroup($idGroup);
    
                    foreach($defaultPanelRights as $dpr) {
                        $rights[$dpr] = new EntityRight('panel', $dpr, false);
                    }
    
                    foreach($panelRights as $name => $value) {
                        if(array_key_exists($name, $rights)) {
                            $rights[$name]->setValue(($value == '1'));
                        }
                    }
    
                    break;
            }

            return $rights;
        };

        $gb = new GridBuilder();

        $gb->addColumns(['status' => 'Status', 'rightName' => 'Right name', 'type' => 'Type']);
        $gb->addOnColumnRender('status', function(EntityRight $right) use ($idGroup) {
            $allowedText = '<span style="color: green">Allowed</span>';
            $deniedText = '<span style="color: red">Denied</span>';

            if($right->getValue()) {
                return $allowedText;
            } else {
                return $deniedText;
            }
        });
        $gb->addOnColumnRender('rightName', function(EntityRight $right) {
            return $right->getName();
        });
        $gb->addOnColumnRender('type', function(EntityRight $right) {
            return $right->getType();
        });
        $gb->addAction(function(EntityRight $right) use ($idGroup) {
            $allowLink = '-';
            $denyLink = '-';

            switch($right->getType()) {
                case 'action':
                    $allowLink = LinkBuilder::createAdvLink(array('page' => 'allowActionRight', 'name' => $right->getName(), 'id' => $idGroup), 'Allow');
                    $denyLink = LinkBuilder::createAdvLink(array('page' => 'denyActionRight', 'name' => $right->getName(), 'id' => $idGroup), 'Deny');
                    break;

                case 'panel':
                    $allowLink = LinkBuilder::createAdvLink(array('page' => 'allowPanelRight', 'name' => $right->getName(), 'id' => $idGroup), 'Allow');
                    $denyLink = LinkBuilder::createAdvLink(array('page' => 'denyPanelRight', 'name' => $right->getName(), 'id' => $idGroup), 'Deny');
                    break;
    
                case 'bulk':
                    $allowLink = LinkBuilder::createAdvLink(array('page' => 'allowBulkActionRight', 'name' => $right->getName(), 'id' => $idGroup), 'Allow');
                    $denyLink = LinkBuilder::createAdvLink(array('page' => 'denyBulkActionRight', 'name' => $right->getName(), 'id' => $idGroup), 'Deny');
                    break;
            }

            if($right->getValue()) {
                return $denyLink;
            } else {
                return $allowLink;
            }
        });
        $gb->addDataSourceCallback($dataSourceCallback);

        return $gb->build();
    }

    private function internalCreateGroupGrid(int $idGroup) {
        global $app;

        $groupUserModel = $app->groupUserModel;
        $userModel = $app->userModel;
        $ucm = CacheManager::getTemporaryObject(CacheCategories::USERS);

        $dataSourceCallback = function() use ($groupUserModel, $idGroup) {
            return $groupUserModel->getGroupUsersByGroupId($idGroup);
        };

        $gb = new GridBuilder();

        $gb->addColumns(['firstname' => 'Firstname', 'lastname' => 'Lastname', 'username' => 'Username', 'status' => 'Status', 'isManager' => 'Is manager']);
        $gb->addOnColumnRender('firstname', function(GroupUser $gu) use ($userModel, $ucm) {
            $user = $ucm->loadUserByIdFromCache($gu->getIdUser());

            if(is_null($user)) {
                $user = $userModel->getUserById($gu->getIdUser());

                $ucm->saveUserToCache($user);
            }

            return $user->getFirstname();
        });
        $gb->addOnColumnRender('lastname', function(GroupUser $gu) use ($userModel, $ucm) {
            $user = $ucm->loadUserByIdFromCache($gu->getIdUser());

            if(is_null($user)) {
                $user = $userModel->getUserById($gu->getIdUser());

                $ucm->saveUserToCache($user);
            }

            return $user->getLastname();
        });
        $gb->addOnColumnRender('username', function(GroupUser $gu) use ($userModel, $ucm) {
            $user = $ucm->loadUserByIdFromCache($gu->getIdUser());

            if(is_null($user)) {
                $user = $userModel->getUserById($gu->getIdUser());

                $ucm->saveUserToCache($user);
            }

            return $user->getUsername();
        });
        $gb->addOnColumnRender('status', function(GroupUser $gu) use ($userModel, $ucm) {
            $user = $ucm->loadUserByIdFromCache($gu->getIdUser());

            if(is_null($user)) {
                $user = $userModel->getUserById($gu->getIdUser());

                $ucm->saveUserToCache($user);
            }

            return UserStatus::$texts[$user->getStatus()];
        });
        $gb->addOnColumnRender('isManager', function(GroupUser $gu) {
            return $gu->getIsManager() ? 'Yes' : 'No';
        });
        $gb->addDataSourceCallback($dataSourceCallback);

        return $gb->build();
    }

    private function internalCreateRibbonRights(Group $group) {
        global $app;

        $ribbonModel = $app->ribbonModel;

        $dataSourceCallback = function() use ($ribbonModel) {
            return $ribbonModel->getAllRibbons(true);
        };

        $enableLink = function(int $idRibbon, int $idGroup, string $action) {
            return LinkBuilder::createAdvLink(['page' => 'enableRibbonRight', 'id_ribbon_update' => $idRibbon, 'id_group' => $idGroup, 'action' => $action], 'No', 'general-link', 'color: red');
        };

        $disableLink = function(int $idRibbon, int $idGroup, string $action) {
            return LinkBuilder::createAdvLink(['page' => 'disableRibbonRight', 'id_ribbon_update' => $idRibbon, 'id_group' => $idGroup, 'action' => $action], 'Yes', 'general-link', 'color: green');
        };

        $allRibbonRights = $app->ribbonRightsModel->getRibbonRightsForAllRibbonsAndIdGroup($group->getId());

        $gb = new GridBuilder();
        $gb->addDataSourceCallback($dataSourceCallback);
        $gb->addColumns(['name' => 'Name', 'code' => 'Code', 'isSystem' => 'System', 'can_see' => 'View', 'can_edit' => 'Edit', 'can_delete' => 'Delete']);
        $gb->addOnColumnRender('isSystem', function(Ribbon $ribbon) {
            if($ribbon->isSystem()) {
                return '<span style="color: green">Yes</span>';
            } else {
                return '<span style="color: red">No</span>';
            }
        });
        $gb->addOnColumnRender('can_see', function(Ribbon $ribbon) use ($allRibbonRights, $group, $enableLink, $disableLink) {
            $ok = false;
            foreach($allRibbonRights as $rr) {
                if($rr['id_ribbon'] == $ribbon->getId()) {
                    $ok = $rr['can_see'];
                }
            }
            return $ok ? $disableLink($ribbon->getId(), $group->getId(), 'can_see') : $enableLink($ribbon->getId(), $group->getId(), 'can_see');
        });
        $gb->addOnColumnRender('can_edit', function(Ribbon $ribbon) use ($allRibbonRights, $group, $enableLink, $disableLink) {
            $ok = false;
            foreach($allRibbonRights as $rr) {
                if($rr['id_ribbon'] == $ribbon->getId()) {
                    $ok = $rr['can_edit'];
                }
            }
            return $ok ? $disableLink($ribbon->getId(), $group->getId(), 'can_edit') : $enableLink($ribbon->getId(), $group->getId(), 'can_edit');
        });
        $gb->addOnColumnRender('can_delete', function(Ribbon $ribbon) use ($allRibbonRights, $group, $enableLink, $disableLink) {
            $ok = false;
            foreach($allRibbonRights as $rr) {
                if($rr['id_ribbon'] == $ribbon->getId()) {
                    $ok = $rr['can_delete'];
                }
            }
            return $ok ? $disableLink($ribbon->getId(), $group->getId(), 'can_delete') : $enableLink($ribbon->getId(), $group->getId(), 'can_delete');
        });

        return $gb->build();
    }
}

?>