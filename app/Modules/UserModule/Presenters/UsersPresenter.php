<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\BulkActionRights;
use DMS\Constants\CacheCategories;
use DMS\Constants\FlashMessageTypes;
use DMS\Constants\UserActionRights;
use DMS\Constants\UserPasswordChangeStatus;
use DMS\Constants\UserStatus;
use DMS\Constants\DatetimeFormats;
use DMS\Constants\Metadata\UserMetadata;
use DMS\Core\CacheManager;
use DMS\Core\CryptManager;
use DMS\Entities\EntityRight;
use DMS\Entities\Ribbon;
use DMS\Entities\User;
use DMS\Helpers\ArrayStringHelper;
use DMS\Helpers\GridDataHelper;
use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;

class UsersPresenter extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('Users');

        $this->getActionNamesFromClass($this);
    }

    protected function saveSettings() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);

        $id = $this->get('id');
        $defaultUserPageUrl = $this->post('default_user_page_url');
        $defaultUserDatetimeFormat = $this->post('default_user_datetime_format');

        $data = array();

        if($defaultUserPageUrl != 'null') {
            $data['default_user_page_url'] = $defaultUserPageUrl;
        }

        if($defaultUserDatetimeFormat != 'Y-m-d H:i:s') {
            $data['default_user_datetime_format'] = $defaultUserDatetimeFormat;
        }

        $app->userModel->updateUser($id, $data);

        $app->flashMessage('Successfully updated settings for user #' . $id, 'success');
        $app->redirect('showProfile', array('id' => $id));
    }

    protected function showSettingsForm() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/users/user-new-entity-form.html');

        $id = null;

        if(!$app->isset('id') || ($app->isset('id') && $this->get('id') == 'current_user')) {
            $id = $app->user->getId();
        } else {
            $id = $this->get('id');
        }

        $user = $app->userModel->getUserById($id);

        if(is_null($user)) {
            $app->flashMessage('User #' . $id . ' does not exist!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $data = array(
            '$PAGE_TITLE$' => 'Settings for user <i>' . $user->getFullname() . '</i>',
            '$LINKS$' => [],
            '$FORM$' => $this->internalCreateUserSettingsForm($user)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showChangePasswordForm() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);
        $id = $this->get('id');
        $user = $app->userModel->getUserById($id);

        if(is_null($user)) {
            $app->flashMessage('User #' . $id . ' does not exist!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/users/user-new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'Update password for user <i>' . $user->getFullname() . '</i>',
            '$FORM$' => $this->internalCreateChangePasswordForm($user),
            '$LINKS$' => []
        );

        $data['$LINKS$'][] = LinkBuilder::createAdvLink(['page' => 'showProfile', 'id' => $id], '&larr;');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function changePassword() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);
        $id = $this->get('id');
        $user = $app->userModel->getUserById($id);

        if(is_null($user)) {
            $app->flashMessage('User #' . $id . ' does not exist!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $currentPassword = $this->post('current_password');
        $password1 = $this->post('password1');
        $password2 = $this->post('password2');

        if($app->userAuthenticator->authUser($user->getUsername(), $currentPassword) == $id) {
            // password check ok

            if($app->userAuthenticator->checkPasswordMatch(array($password1, $password2)) && !$app->userAuthenticator->checkPasswordMatch(array($password1, $currentPassword))) {
                // new password check ok

                $password = CryptManager::hashPassword($password1);

                $data = array(
                    'password_change_status' => UserPasswordChangeStatus::OK,
                    'date_password_changed' => date('Y-m-d H:i:s')
                );

                $app->userModel->updateUser($id, $data);
                $app->userModel->updateUserPassword($id, $password);

                $app->redirect('HomePage:showHomepage');
            } else {
                $app->flashMessage('New passwords do not match or they match the current password used', FlashMessageTypes::ERROR);
                $app->redirect('showChangePasswordForm', array('id' => $id));
            }
        } else {
            $app->flashMessage('Entered current password does not match the one this account has!', FlashMessageTypes::ERROR);
            $app->redirect('showChangePasswordForm', array('id' => $id));
        }
    }

    protected function showProfile() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/users/user-profile-grid.html');

        if(!$app->isset('id') || ($app->isset('id') && $this->get('id') == 'current_user')) {
            $id = $app->user->getId();
        } else {
            $id = $this->get('id');
        }

        $user = $app->userRepository->getUserById($id);

        if(is_null($user)) {
            $app->flashMessage('User #' . $id . ' does not exist!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $editLink = '';

        if($app->actionAuthorizator->checkActionRight(UserActionRights::EDIT_USER)) {
            $editLink = LinkBuilder::createAdvLink(array(
                'page' => 'showEditForm',
                'id' => $id
            ), 'Edit user');
        }

        $data = array(
            '$PAGE_TITLE$' => '<i>' . $user->getFullname() . '</i>\'s profile',
            '$USER_PROFILE_GRID$' => $this->internalCreateUserProfileGrid($id),
            '$LINKS$' => array($editLink)
        );

        $requestPasswordChangeLink = '';
        $forcePasswordChangeLink = '';
        $changePasswordLink = '';

        if($app->actionAuthorizator->checkActionRight(UserActionRights::REQUEST_PASSWORD_CHANGE_USER)) {
            $requestPasswordChangeLink = '&nbsp;&nbsp;' . LinkBuilder::createAdvLink(array(
                'page' => 'requestPasswordChange',
                'id' => $id
            ), 'Request password change');

            $forcePasswordChangeLink = '&nbsp;&nbsp;' . LinkBuilder::createAdvLink(array(
                'page' => 'forcePasswordChange',
                'id' => $id
            ), 'Force password change');

            if($app->actionAuthorizator->checkActionRight(UserActionRights::EDIT_USER)) {
                $changePasswordLink = '&nbsp;&nbsp;' . LinkBuilder::createAdvLink(array(
                    'page' => 'showChangePasswordForm',
                    'id' => $id
                ), 'Change password');
            }
        }

        if($id == $app->user->getId()) {
            $changePasswordLink = '&nbsp;&nbsp;' . LinkBuilder::createAdvLink(array(
                'page' => 'showChangePasswordForm',
                'id' => $id
            ), 'Change password');

            $data['$LINKS$'][] = $changePasswordLink;
        } else {
            $data['$LINKS$'][] = $changePasswordLink;
            $data['$LINKS$'][] = $requestPasswordChangeLink;
            $data['$LINKS$'][] = $forcePasswordChangeLink;
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function forcePasswordChange() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);
        $id = $this->get('id');

        $data = array(
            'status' => UserStatus::PASSWORD_UPDATE_REQUIRED,
            'password_change_status' => UserPasswordChangeStatus::FORCE
        );

        $app->userModel->updateUser($id, $data);

        $app->flashMessage('Request password change for user #' . $id . ' successful.', 'success');
        $app->redirect('showProfile', array('id' => $id));
    }

    protected function requestPasswordChange() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);
        $id = $this->get('id');

        $data = array(
            'password_change_status' => UserPasswordChangeStatus::WARNING
        );

        $app->userModel->updateUser($id, $data);

        $app->flashMessage('Request password change for user #' . $id . ' successful.', 'success');
        $app->redirect('showProfile', array('id' => $id));
    }

    protected function showEditForm() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);
        $id = $this->get('id');

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/users/user-new-entity-form.html');

        $user = $app->userModel->getUserById($id);

        if(is_null($user)) {
            $app->flashMessage('User #' . $id . ' does not exist!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $data = array(
            '$PAGE_TITLE$' => 'Edit user \'' . $user->getFullname() . '\'',
            '$FORM$' => $this->internalCreateEditUserForm($user),
            '$LINKS$' => []
        );

        $data['$LINKS$'][] = LinkBuilder::createAdvLink(['page' => 'showProfile', 'id' => $id], '&larr;');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function saveUserEdit() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);
        $id = $this->get('id');
        
        $required = array('firstname', 'lastname', 'username');
        
        $data = [];
        foreach($required as $r) {
            $data[$r] = $this->post($r);
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

        $app->userModel->updateUser($id, $data);

        $app->flashMessage('Successfully edited user #' . $id, 'success');
        $app->redirect('showProfile', array('id' => $id));
    }

    protected function showRibbonRights() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);

        $template = $this->loadTemplate(__DIR__ . '/templates/users/user-rights-grid.html');

        $idUser = $this->get('id');
        $user = $app->userModel->getUserById($idUser);
        
        $data = [
            '$PAGE_TITLE$' => 'Ribbon rights for user <i>' . $user->getFullname() . '</i>',
            '$BACK_LINK$' => '',
            '$LINKS$' => [LinkBuilder::createAdvLink(['page' => 'Settings:showUsers'], '&larr;')],
            '$USER_RIGHTS_GRID$' => $this->internalCreateRibbonRights($user)
        ];

        $this->fill($data, $template);

        return $template;
    }

    protected function showUserRights() {
        global $app;

        if(!$app->actionAuthorizator->checkActionRight(UserActionRights::MANAGE_USER_RIGHTS)) {
            $app->redirect('Settings:showUsers');
        }

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/users/user-rights-grid.html');

        $app->flashMessageIfNotIsset(['id', 'filter']);

        $id = $this->get('id');
        $filter = $this->get('filter');
        $user = $app->userModel->getUserById($id);

        if(is_null($user)) {
            $app->flashMessage('User #' . $id . ' does not exist!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $userRights = '';

        $app->logger->logFunction(function() use (&$userRights, $id, $filter) {
            $userRights = $this->internalCreateUserRightsGrid($id, $filter);
        }, __METHOD__);

        $links = array(
            '<a class="general-link" href="?page=UserModule:Users:allowAllRights&id_user=' . $id . '&filter=' . $filter . '">Allow all</a>',
            '&nbsp;&nbsp;',
            '<a class="general-link" href="?page=UserModule:Users:denyAllRights&id_user=' . $id . '&filter=' . $filter . '">Deny all</a>'
        );

        $data = array(
            '$PAGE_TITLE$' => '<i>' . $user->getFullname() . '</i> rights',
            '$USER_RIGHTS_GRID$' => $userRights,
            '$LINKS$' => ArrayStringHelper::createUnindexedStringFromUnindexedArray($links),
            '$BACK_LINK$' => LinkBuilder::createAdvLink(array('page' => 'Settings:showUsers'), '&larr;')
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function allowAllRights() {
        global $app;

        $app->flashMessageIfNotIsset(['id_user', 'filter']);
        $idUser = $this->get('id_user');
        $filter = $this->get('filter');

        $allow = true;

        $app->getConn()->beginTransaction();

        switch($filter) {
            case 'actions':
                foreach(UserActionRights::$all as $ar) {
                    if($app->userRightModel->checkActionRightExists($idUser, $ar)) {
                        $app->userRightModel->updateActionRight($idUser, $ar, $allow);
                    } else {
                        $app->userRightModel->insertActionRightForIdUser($idUser, $ar, $allow);
                    }
                }

                $acm = CacheManager::getTemporaryObject(CacheCategories::ACTIONS);
                $acm->invalidateCache();

                break;

            case 'bulk_actions':
                foreach(BulkActionRights::$all as $bar) {
                    if($app->userRightModel->checkBulkActionRightExists($idUser, $bar)) {
                        $app->userRightModel->updateBulkActionRight($idUser, $bar, $allow);
                    } else {
                        $app->userRightModel->insertBulkActionRightForIdUser($idUser, $bar, $allow);
                    }
                }

                $bacm = CacheManager::getTemporaryObject(CacheCategories::BULK_ACTIONS);
                $bacm->invalidateCache();

                break;
        }

        $app->getConn()->commit();

        $app->redirect('showUserRights', array('id' => $idUser, 'filter' => $filter));
    }

    protected function denyAllRights() {
        global $app;

        $app->flashMessageIfNotIsset(['id_user', 'filter']);
        $idUser = $this->get('id_user');
        $filter = $this->get('filter');

        $allow = false;

        $app->getConn()->beginTransaction();

        switch($filter) {
            case 'actions':
                foreach(UserActionRights::$all as $ar) {
                    if($app->userRightModel->checkActionRightExists($idUser, $ar)) {
                        $app->userRightModel->updateActionRight($idUser, $ar, $allow);
                    } else {
                        $app->userRightModel->insertActionRightForIdUser($idUser, $ar, $allow);
                    }
                }

                $acm = CacheManager::getTemporaryObject(CacheCategories::ACTIONS);
                $acm->invalidateCache();

                break;

            case 'bulk_actions':
                foreach(BulkActionRights::$all as $bar) {
                    if($app->userRightModel->checkBulkActionRightExists($idUser, $bar)) {
                        $app->userRightModel->updateBulkActionRight($idUser, $bar, $allow);
                    } else {
                        $app->userRightModel->insertBulkActionRightForIdUser($idUser, $bar, $allow);
                    }
                }

                $bacm = CacheManager::getTemporaryObject(CacheCategories::BULK_ACTIONS);
                $bacm->invalidateCache();

                break;
        }

        $app->getConn()->commit();

        $app->redirect('showUserRights', array('id' => $idUser, 'filter' => $filter));
    }

    protected function allowActionRight() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'name']);

        $name = $this->get('name');
        $idUser = $this->get('id');

        if($app->userRightModel->checkActionRightExists($idUser, $name) === TRUE) {
            $app->userRightModel->updateActionRight($idUser, $name, true);
        } else {
            $app->userRightModel->insertActionRightForIdUser($idUser, $name, true);
        }

        $app->logger->info('Allowed action right to user #' . $idUser, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::ACTIONS);
        $cm->invalidateCache();

        $app->redirect('showUserRights', array('id' => $idUser, 'filter' => 'actions'), $name);
    }

    protected function denyActionRight() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'name']);

        $name = $this->get('name');
        $idUser = $this->get('id');

        if($app->userRightModel->checkActionRightExists($idUser, $name) === TRUE) {
            $app->userRightModel->updateActionRight($idUser, $name, false);
        } else {
            $app->userRightModel->insertActionRightForIdUser($idUser, $name, false);
        }

        $app->logger->info('Denied action right to user #' . $idUser, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::ACTIONS);
        $cm->invalidateCache();

        $app->redirect('showUserRights', array('id' => $idUser, 'filter' => 'actions'), $name);
    }

    protected function allowBulkActionRight() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'name']);

        $name = $this->get('name');
        $idUser = $this->get('id');

        if($app->userRightModel->checkBulkActionRightExists($idUser, $name) === TRUE) {
            $app->userRightModel->updateBulkActionRight($idUser, $name, true);
        } else {
            $app->userRightModel->insertBulkActionRightForIdUser($idUser, $name, true);
        }

        $app->logger->info('Allowed bulk action right to user #' . $idUser, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::BULK_ACTIONS);
        $cm->invalidateCache();

        $app->redirect('showUserRights', array('id' => $idUser, 'filter' => 'bulk_actions'), $name);
    }

    protected function denyBulkActionRight() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'name']);

        $name = $this->get('name');
        $idUser = $this->get('id');

        if($app->userRightModel->checkBulkActionRightExists($idUser, $name) === TRUE) {
            $app->userRightModel->updateBulkActionRight($idUser, $name, false);
        } else {
            $app->userRightModel->insertBulkActionRightForIdUser($idUser, $name, false);
        }

        $app->logger->info('Denied bulk action right to user #' . $idUser, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::BULK_ACTIONS);
        $cm->invalidateCache();

        $app->redirect('showUserRights', array('id' => $idUser, 'filter' => 'bulk_actions'), $name);
    }

    protected function enableRibbonRight() {
        global $app;

        $app->flashMessageIfNotIsset(['id_ribbon_update', 'id_user', 'action']);
        $idRibbon = $this->get('id_ribbon_update');
        $idUser = $this->get('id_user');
        $action = $this->get('action');

        $app->ribbonRightsModel->updateUserRights($idRibbon, $idUser, [$action => '1']);

        $app->logger->info('Enabled ribbon right for ribbon #' . $idRibbon . ' to user #' . $idUser, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::RIBBON_USER_RIGHTS);
        $cm->invalidateCache();

        $app->redirect('showRibbonRights', ['id' => $idUser]);
    }

    protected function disableRibbonRight() {
        global $app;

        $app->flashMessageIfNotIsset(['id_ribbon_update', 'id_user', 'action']);
        $idRibbon = $this->get('id_ribbon_update');
        $idUser = $this->get('id_user');
        $action = $this->get('action');

        $app->ribbonRightsModel->updateUserRights($idRibbon, $idUser, [$action => '0']);

        $app->logger->info('Disabled ribbon right for ribbon #' . $idRibbon . ' to user #' . $idUser, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::RIBBON_USER_RIGHTS);
        $cm->invalidateCache();

        $app->redirect('showRibbonRights', ['id' => $idUser]);
    }

    private function internalCreateRibbonRights(User $user) {
        global $app;

        $ribbonModel = $app->ribbonModel;

        $dataSourceCallback = function() use ($ribbonModel) {
            return $ribbonModel->getAllRibbons(true);
        };

        $enableLink = function(int $idRibbon, int $idUser, string $action) {
            return LinkBuilder::createAdvLink(['page' => 'enableRibbonRight', 'id_ribbon_update' => $idRibbon, 'id_user' => $idUser, 'action' => $action], 'No', 'general-link', 'color: red');
        };

        $disableLink = function(int $idRibbon, int $idUser, string $action) {
            return LinkBuilder::createAdvLink(['page' => 'disableRibbonRight', 'id_ribbon_update' => $idRibbon, 'id_user' => $idUser, 'action' => $action], 'Yes', 'general-link', 'color: green');
        };

        $allRibbonRights = $app->ribbonRightsModel->getRibbonRightsForAllRibbonsAndIdUser($user->getId());

        $gb = new GridBuilder();
        $gb->addDataSourceCallback($dataSourceCallback);
        $gb->addColumns(['name' => 'Name', 'code' => 'Code', 'isSystem' => 'System', 'can_see' => 'View', 'can_edit' => 'Edit', 'can_delete' => 'Delete']);
        $gb->addOnColumnRender('isSystem', function(Ribbon $ribbon) {
            return GridDataHelper::renderBooleanValueWithColors($ribbon->isSystem(), 'Yes', 'No');
        });
        $gb->addOnColumnRender('can_see', function(Ribbon $ribbon) use ($allRibbonRights, $user, $enableLink, $disableLink) {
            $ok = false;
            foreach($allRibbonRights as $rr) {
                if($rr['id_ribbon'] == $ribbon->getId()) {
                    $ok = $rr['can_see'];
                }
            }
            return $ok ? $disableLink($ribbon->getId(), $user->getId(), 'can_see') : $enableLink($ribbon->getId(), $user->getId(), 'can_see');
        });
        $gb->addOnColumnRender('can_edit', function(Ribbon $ribbon) use ($allRibbonRights, $user, $enableLink, $disableLink) {
            $ok = false;
            foreach($allRibbonRights as $rr) {
                if($rr['id_ribbon'] == $ribbon->getId()) {
                    $ok = $rr['can_edit'];
                }
            }
            return $ok ? $disableLink($ribbon->getId(), $user->getId(), 'can_edit') : $enableLink($ribbon->getId(), $user->getId(), 'can_edit');
        });
        $gb->addOnColumnRender('can_delete', function(Ribbon $ribbon) use ($allRibbonRights, $user, $enableLink, $disableLink) {
            $ok = false;
            foreach($allRibbonRights as $rr) {
                if($rr['id_ribbon'] == $ribbon->getId()) {
                    $ok = $rr['can_delete'];
                }
            }
            return $ok ? $disableLink($ribbon->getId(), $user->getId(), 'can_delete') : $enableLink($ribbon->getId(), $user->getId(), 'can_delete');
        });

        return $gb->build();
    }

    private function internalCreateUserRightsGrid(int $idUser, string $filter) {
        global $app;

        $userRightModel = $app->userRightModel;

        $dataSourceCallback = function() use ($userRightModel, $filter, $idUser) {
            $rights = [];
            switch($filter) {
                case 'actions':
                    $defaultActionRights = UserActionRights::$all;
                    $actionRights = $userRightModel->getActionRightsForIdUser($idUser);
    
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
                    $bulkActionRights = $userRightModel->getBulkActionRightsForIdUser($idUser);
    
                    foreach($defaultBulkActionRights as $dbar) {
                        $rights[$dbar] = new EntityRight('bulk', $dbar, false);
                    }
            
                    foreach($bulkActionRights as $name => $value) {
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
        $gb->addOnColumnRender('status', function(EntityRight $right) use ($idUser) {
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
        $gb->addAction(function(EntityRight $right) use ($idUser) {
            $allowLink = '-';
            $denyLink = '-';

            switch($right->getType()) {
                case 'action':
                    $allowLink = LinkBuilder::createAdvLink(array('page' => 'allowActionRight', 'name' => $right->getName(), 'id' => $idUser), 'Allow');
                    $denyLink = LinkBuilder::createAdvLink(array('page' => 'denyActionRight', 'name' => $right->getName(), 'id' => $idUser), 'Deny');
                    break;
    
                case 'bulk':
                    $allowLink = LinkBuilder::createAdvLink(array('page' => 'allowBulkActionRight', 'name' => $right->getName(), 'id' => $idUser), 'Allow');
                    $denyLink = LinkBuilder::createAdvLink(array('page' => 'denyBulkActionRight', 'name' => $right->getName(), 'id' => $idUser), 'Deny');
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

    private function internalCreateUserProfileGrid(int $idUser) {
        global $app;

        $user = $app->userModel->getUserById($idUser);

        if(is_null($user)) {
            $app->flashMessage('User #' . $idUser . ' does not exist!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $code = '';
        $code .= '<table border="1">';

        $data = array(
            'First name' => $user->getFirstname() ?? '-',
            'Last name' => $user->getLastname() ?? '-',
            'Username' => $user->getUsername() ?? '-',
            'Email' => $user->getEmail() ?? '-',
            'Status' => UserStatus::$texts[$user->getStatus()],
            'Address' => '',
            'House number' => $user->getAddressHouseNumber() ?? '-',
            'Street' => $user->getAddressStreet() ?? '-',
            'City' => $user->getAddressCity() ?? '-',
            'Zip code' => $user->getAddressZipCode() ?? '-',
            'Country' => $user->getAddressCountry() ?? '-'
        );

        foreach($data as $key => $value) {
            $code .= '<tr>';
            $code .= '<th>' . $key . '</th>';
            $code .= '<td>' . $value . '</td>';
            $code .= '</tr>';
        }

        $code .= '</table>';

        return $code;
    }

    private function internalCreateEditUserForm(User $user) {
        $fb = FormBuilder::getTemporaryObject();

        $fb ->setMethod('POST')->setAction('?page=UserModule:Users:saveUserEdit&id=' . $user->getId())
            ->addElement($fb->createLabel()->setFor('firstname')->setText('First name')->setRequired())
            ->addElement($fb->createInput()->setType('text')->setName('firstname')->require()->setValue($user->getFirstname() ?? ''))

            ->addElement($fb->createLabel()->setFor('lastname')->setText('Last name')->setRequired())
            ->addElement($fb->createInput()->setType('text')->setName('lastname')->require()->setValue($user->getLastname() ?? ''))

            ->addElement($fb->createlabel()->setFor('email')->setText('Email'))
            ->addElement($fb->createInput()->setType('email')->setName('email')->setValue($user->getEmail() ?? ''))

            ->addElement($fb->createlabel()->setFor('username')->setText('Username'))
            ->addElement($fb->createInput()->setType('text')->setName('username')->setValue($user->getUsername())->setSpecial('readonly'))

            ->addElement($fb->createlabel()->setText('Address'))
            ->addElement($fb->createlabel()->setFor('address_street')->setText('Street'))
            ->addElement($fb->createInput()->setType('text')->setName('address_street')->setValue($user->getAddressStreet() ?? ''))

            ->addElement($fb->createlabel()->setFor('address_house_number')->setText('House number'))
            ->addElement($fb->createInput()->setType('text')->setName('address_house_number')->setValue($user->getAddressHouseNumber() ?? ''))

            ->addElement($fb->createlabel()->setFor('address_city')->setText('City'))
            ->addElement($fb->createInput()->setType('text')->setName('address_city')->setValue($user->getAddressCity() ?? ''))

            ->addElement($fb->createlabel()->setFor('address_zip_code')->setText('Zip code'))
            ->addElement($fb->createInput()->setType('text')->setName('address_zip_code')->setValue($user->getAddressZipCode() ?? ''))

            ->addElement($fb->createlabel()->setFor('address_country')->setText('Country'))
            ->addElement($fb->createInput()->setType('text')->setName('address_country')->setValue($user->getAddressCountry() ?? ''))

            ->addElement($fb->createSubmit('Save'))
        ;

        $form = $fb->build();

        return $form;
    }

    private function internalCreateChangePasswordForm(User $user) {
        $fb = FormBuilder::getTemporaryObject();

        $fb
        ->setMethod('POST')
        ->setAction('?page=UserModule:Users:changePassword&id=' . $user->getId())

        ->addElement($fb->createLabel()->setFor('current_password')->setText('Current password')->setRequired())
        ->addElement($fb->createInput()->setType('password')->setName('current_password')->require())

        ->addElement($fb->createLabel()->setFor('password1')->setText('New password')->setRequired())
        ->addElement($fb->createInput()->setType('password')->setName('password1')->require())

        ->addElement($fb->createLabel()->setFor('password2')->setText('New password again')->setRequired())
        ->addElement($fb->createInput()->setType('password')->setName('password2')->require())

        ->addElement($fb->createSubmit('Save')->setId('submit'))
        ;

        $form = $fb->build();

        return $form;
    }

    private function internalCreateUserSettingsForm(User $user) {
        global $app;

        $fb = FormBuilder::getTemporaryObject();
        
        $pages = array(
            [
                'value' => 'null',
                'text' => '-'
            ]
        );

        foreach($app->pageList as $realLink => $fakeLink) {
            $page = array(
                'value' => $realLink,
                'text' => $fakeLink
            );

            if($realLink == $user->getDefaultUserPageUrl()) {
                $page['selected'] = 'selected';
            }

            $pages[] = $page;
        }

        $formats = DatetimeFormats::$formats;

        $datetimeFormats = [];
        foreach($formats as $format) {
            $datetimeFormat = array(
                'value' => $format,
                'text' => $format
            );

            if(($user->getDefaultUserDateTimeFormat() !== NULL) && ($format == $user->getDefaultUserDateTimeFormat()))  {
                $datetimeFormat['selected'] = 'selected';
            }

            $datetimeFormats[] = $datetimeFormat;
        }

        $fb
        ->setMethod('POST')
        ->setAction('?page=UserModule:Users:saveSettings&id=' . $user->getId())

        ->addElement($fb->createLabel()->setFor('default_user_page_url')->setText('Default page'))
        ->addElement($fb->createSelect()->setName('default_user_page_url')->addOptionsBasedOnArray($pages))

        ->addElement($fb->createLabel()->setFor('default_user_datetime_format')->setText('Datetime format'))
        ->addElement($fb->createSelect()->setName('default_user_datetime_format')->addOptionsBasedOnArray($datetimeFormats))

        ->addElement($fb->createSubmit('Save'))
        ;

        return $fb->build();
    }
}

?>