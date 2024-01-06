<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\BulkActionRights;
use DMS\Constants\CacheCategories;
use DMS\Constants\PanelRights;
use DMS\Constants\UserActionRights;
use DMS\Constants\UserStatus;
use DMS\Core\CacheManager;
use DMS\Helpers\ArrayStringHelper;
use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

class Groups extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('Groups');

        $this->getActionNamesFromClass($this);
    }

    protected function showNewUserForm() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);
        
        $idGroup = htmlspecialchars($_GET['id']);
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
        
        $id = htmlspecialchars($_GET['id']);
        $group = $app->groupModel->getGroupById($id);

        $data = array(
            '$PAGE_TITLE$' => 'Users in group <i>' . $group->getName() . '</i>',
            '$GROUP_GRID$' => $this->internalCreateGroupGrid($id),
            '$NEW_ENTITY_LINK$' => '<div class="row"><div class="col-md" id="right">' . LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:showNewUserForm', 'id' => $id), 'Add user') . '</div></div>'
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showGroupRights() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/groups/group-rights-grid.html');

        $app->flashMessageIfNotIsset(['id']);

        $id = htmlspecialchars($_GET['id']);
        $group = $app->groupModel->getGroupById($id);

        $data = array(
            '$PAGE_TITLE$' => '<i>' . $group->getName() . '</i> rights',
            '$GROUP_RIGHTS_GRID$' => $this->internalCreateGroupRightsGrid($id)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function allowActionRight() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'name']);

        $name = htmlspecialchars($_GET['name']);
        $idGroup = htmlspecialchars($_GET['id']);

        if($app->groupRightModel->checkActionRightExists($idGroup, $name) === TRUE) {
            $app->groupRightModel->updateActionRight($idGroup, $name, true);
        } else {
            $app->groupRightModel->insertActionRightForIdGroup($idGroup, $name, true);
        }

        $app->logger->info('Allowed action right \'' . $name . '\' to group #' . $idGroup, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::ACTIONS);
        $cm->invalidateCache();

        $app->redirect('UserModule:Groups:showGroupRights', array('id' => $idGroup));
    }

    protected function denyActionRight() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'name']);

        $name = htmlspecialchars($_GET['name']);
        $idGroup = htmlspecialchars($_GET['id']);

        if($app->groupRightModel->checkActionRightExists($idGroup, $name) === TRUE) {
            $app->groupRightModel->updateActionRight($idGroup, $name, false);
        } else {
            $app->groupRightModel->insertActionRightForIdGroup($idGroup, $name, false);
        }

        $app->logger->info('Denied action right \'' . $name . '\' to group #' . $idGroup, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::ACTIONS);
        $cm->invalidateCache();

        $app->redirect('UserModule:Groups:showGroupRights', array('id' => $idGroup));
    }

    protected function allowPanelRight() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'name']);

        $name = htmlspecialchars($_GET['name']);
        $idGroup = htmlspecialchars($_GET['id']);

        if($app->groupRightModel->checkPanelRightExists($idGroup, $name) === TRUE) {
            $app->groupRightModel->updatePanelRight($idGroup, $name, true);
        } else {
            $app->groupRightModel->insertPanelRightForIdGroup($idGroup, $name, true);
        }

        $app->logger->info('Allowed panel right \'' . $name . '\' to group #' . $idGroup, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::PANELS);
        $cm->invalidateCache();

        $app->redirect('UserModule:Groups:showGroupRights', array('id' => $idGroup));
    }

    protected function denyPanelRight() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'name']);

        $name = htmlspecialchars($_GET['name']);
        $idGroup = htmlspecialchars($_GET['id']);

        if($app->groupRightModel->checkPanelRightExists($idGroup, $name) === TRUE) {
            $app->groupRightModel->updatePanelRight($idGroup, $name, false);
        } else {
            $app->groupRightModel->insertPanelRightForIdGroup($idGroup, $name, false);
        }

        $app->logger->info('Denied panel right \'' . $name . '\' to group #' . $idGroup, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::PANELS);
        $cm->invalidateCache();

        $app->redirect('UserModule:Groups:showGroupRights', array('id' => $idGroup));
    }

    protected function allowBulkActionRight() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'name']);

        $name = htmlspecialchars($_GET['name']);
        $idGroup = htmlspecialchars($_GET['id']);

        if($app->groupRightModel->checkBulkActionRightExists($idGroup, $name) === TRUE) {
            $app->groupRightModel->updateBulkActionRight($idGroup, $name, true);
        } else {
            $app->groupRightModel->insertBulkActionRightForIdGroup($idGroup, $name, true);
        }

        $app->logger->info('Allowed bulk action right \'' . $name . '\' to group #' . $idGroup, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::BULK_ACTIONS);
        $cm->invalidateCache();

        $app->redirect('UserModule:Groups:showGroupRights', array('id' => $idGroup));
    }

    protected function denyBulkActionRight() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'name']);

        $name = htmlspecialchars($_GET['name']);
        $idGroup = htmlspecialchars($_GET['id']);

        if($app->groupRightModel->checkBulkActionRightExists($idGroup, $name) === TRUE) {
            $app->groupRightModel->updateBulkActionRight($idGroup, $name, false);
        } else {
            $app->groupRightModel->insertBulkActionRightForIdGroup($idGroup, $name, false);
        }

        $app->logger->info('Denied bulk action right \'' . $name . '\' to group #' . $idGroup, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::BULK_ACTIONS);
        $cm->invalidateCache();

        $app->redirect('UserModule:Groups:showGroupRights', array('id' => $idGroup));
    }

    protected function addUserToGroup() {
        global $app;

        $app->flashMessageIfNotIsset(['id_group', 'user']);

        $idGroup = htmlspecialchars($_GET['id_group']);
        $idUser = htmlspecialchars($_POST['user']);

        $app->groupUserModel->insertUserToGroup($idGroup, $idUser);

        $app->logger->info('Added user #' . $idUser . ' to group #' . $idGroup, __METHOD__);

        CacheManager::invalidateAllCache();
        
        $app->redirect('UserModule:Groups:showUsers', array('id' => $idGroup));
    }

    protected function removeUserFromGroup() {
        global $app;

        $app->flashMessageIfNotIsset(['id_group', 'id_user']);

        $idGroup = htmlspecialchars($_GET['id_group']);
        $idUser = htmlspecialchars($_GET['id_user']);

        $app->groupUserModel->removeUserFromGroup($idGroup, $idUser);
        
        $app->logger->info('Removed user #' . $idUser . ' from group #' . $idGroup, __METHOD__);

        CacheManager::invalidateAllCache();

        $app->redirect('UserModule:Groups:showUsers', array('id' => $idGroup));
    }

    protected function setUserAsManager() {
        global $app;

        $app->flashMessageIfNotIsset(['id_group', 'id_user']);

        $idGroup = htmlspecialchars($_GET['id_group']);
        $idUser = htmlspecialchars($_GET['id_user']);

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

        $app->redirect('UserModule:Groups:showUsers', array('id' => $idGroup));
    }

    protected function unsetUserAsManager() {
        global $app;

        $app->flashMessageIfNotIsset(['id_group', 'id_user']);

        $idGroup = htmlspecialchars($_GET['id_group']);
        $idUser = htmlspecialchars($_GET['id_user']);

        $app->groupUserModel->updateUserInGroup($idGroup, $idUser, array('is_manager' => '0'));

        $app->logger->info('Unset user #' . $idUser . ' as the manager of group #' . $idGroup, __METHOD__);

        $app->redirect('UserModule:Groups:showUsers', array('id' => $idGroup));
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

    private function internalCreateGroupRightsGrid(int $idGroup) {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $tb->showRowBorder();

        $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('Actions')->setBold()->setColspan('2'))
                                    ->addCol($tb->createCol()->setText('Status')->setBold())
                                    ->addCol($tb->createCol()->setText('Right name')->setBold())
                                    ->addCol($tb->createCol()->setText('Type')->setBold()))
        ;

        $rights = [];

        $defaultActionRights = UserActionRights::$all;
        $defaultPanelRights = PanelRights::$all;
        $defaultBulkActionRights = BulkActionRights::$all;

        $actionRights = $app->groupRightModel->getActionRightsForIdGroup($idGroup);
        $panelRights = $app->groupRightModel->getPanelRightsForIdGroup($idGroup);
        $bulkActionRights = $app->groupRightModel->getBulkActionRightsForIdGroup($idGroup);

        foreach($defaultActionRights as $dar)  {
            $rights[$dar] = array(
                'type' => 'action',
                'name' => $dar,
                'value' => 0
            );
        }

        foreach($defaultPanelRights as $dpr) {
            $rights[$dpr] = array(
                'type' => 'panel',
                'name' => $dpr,
                'value' => 0
            );
        }

        foreach($defaultBulkActionRights as $dbar) {
            $rights[$dbar] = array(
                'type' => 'bulk',
                'name' => $dbar,
                'value' => 0
            );
        }

        foreach($actionRights as $name => $value) {
            if(array_key_exists($name, $rights)) {
                $rights[$name] = array(
                    'type' => 'action',
                    'name' => $name,
                    'value' => $value
                );
            }
        }

        foreach($bulkActionRights as $name => $value) {
            if(array_key_exists($name, $rights)) {
                $rights[$name] = array(
                    'type' => 'bulk',
                    'name' => $name,
                    'value' => $value
                );
            }
        }

        foreach($panelRights as $name => $value) {
            if(array_key_exists($name, $rights)) {
                $rights[$name] = array(
                    'type' => 'panel',
                    'name' => $name,
                    'value' => $value
                );
            }
        }

        foreach($rights as $rightname => $right) {
            $type = $right['type'];
            $name = $right['name'];
            $value = $right['value'];

            $row = $tb->createRow();

            $allowLink = '';
            $denyLink = '';

            switch($type) {
                case 'action':
                    $allowLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:allowActionRight', 'name' => $name, 'id' => $idGroup), 'Allow');
                    $denyLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:denyActionRight', 'name' => $name, 'id' => $idGroup), 'Deny');
                    break;

                case 'panel':
                    $allowLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:allowPanelRight', 'name' => $name, 'id' => $idGroup), 'Allow');
                    $denyLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:denyPanelRight', 'name' => $name, 'id' => $idGroup), 'Deny');
                    break;

                case 'bulk':
                    $allowLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:allowBulkActionRight', 'name' => $name, 'id' => $idGroup), 'Allow');
                    $denyLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:denyBulkActionRight', 'name' => $name, 'id' => $idGroup), 'Deny');
                    break;
            }

            $allowedText = '<span style="color: green">Allowed</span>';
            $deniedText = '<span style="color: red">Denied</span>';

            $row->addCol($tb->createCol()->setText($allowLink))
                ->addCol($tb->createCol()->setText($denyLink))
                ->addCol($tb->createCol()->setText($value ? $allowedText : $deniedText))
                ->addCol($tb->createCol()->setText($name))
                ->addCol($tb->createCol()->setText($type))
            ;

            $tb->addRow($row);
        }

        $table = $tb->build();

        return $table;
    }

    private function internalCreateGroupGrid(int $idGroup) {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $headers = array(
            'Actions',
            'First name',
            'Last name',
            'User name',
            'Status',
            'Is manager'
        );

        $headerRow = null;

        $groupUsers = $app->groupUserModel->getGroupUsersByGroupId($idGroup);

        if(empty($groupUsers)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($groupUsers as $groupUser) {
                $user = $app->userModel->getUserById($groupUser->getIdUser());

                $actionLinks = array(
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showProfile', 'id' => $user->getId()), 'Profile')
                );

                if($groupUser->getIsManager()) {
                    $actionLinks[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:unsetUserAsManager', 'id_group' => $idGroup, 'id_user' => $user->getId()), 'Unset as Manager');
                } else {
                    $actionLinks[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:setUserAsManager', 'id_group' => $idGroup, 'id_user' => $user->getId()), 'Set as Manager');
                }

                if($idGroup != '2' && $user->getUsername() != 'admin') {
                    $actionLinks[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:removeUserFromGroup', 'id_group' => $idGroup, 'id_user' => $user->getId()), 'Remove');
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

                $userRow->addCol($tb->createCol()->setText($user->getFirstname()))
                        ->addCol($tb->createCol()->setText($user->getLastname()))
                        ->addCol($tb->createCol()->setText($user->getUsername()))
                        ->addCol($tb->createCol()->setText(UserStatus::$texts[$user->getStatus()]))
                        ->addCol($tb->createCol()->setText($groupUser->getIsManager() ? 'Yes' : 'No'))
                ;

                $tb->addRow($userRow);
            }
        }

        return $tb->build();
    }
}

?>