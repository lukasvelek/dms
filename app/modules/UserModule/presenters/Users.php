<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\BulkActionRights;
use DMS\Constants\CacheCategories;
use DMS\Constants\FlashMessageTypes;
use DMS\Constants\PanelRights;
use DMS\Constants\UserActionRights;
use DMS\Constants\UserPasswordChangeStatus;
use DMS\Constants\UserStatus;
use DMS\Core\CacheManager;
use DMS\Core\CryptManager;
use DMS\Entities\User;
use DMS\Helpers\ArrayStringHelper;
use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

class Users extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('Users');

        $this->getActionNamesFromClass($this);
    }

    protected function saveSettings() {
        global $app;

        if(!$app->isset('id')) {
            $app->flashMessage('These values: ' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($app->missingUrlValues, ',') . ' are missing!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $id = htmlspecialchars($_GET['id']);
        $defaultUserPageUrl = htmlspecialchars($_POST['default_user_page_url']);

        $app->userModel->updateUser($id, array('default_user_page_url' => $defaultUserPageUrl));

        $app->flashMessage('Successfully changed default page for user #' . $id, 'success');
        $app->redirect('UserModule:Users:showProfile', array('id' => $id));
    }

    protected function showSettingsForm() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/users/user-new-entity-form.html');

        if(!$app->isset('id')) {
            $app->flashMessage('These values: ' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($app->missingUrlValues, ',') . ' are missing!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $id = htmlspecialchars($_GET['id']);
        $user = $app->userModel->getUserById($id);

        $data = array(
            '$PAGE_TITLE$' => 'Settings for user <i>' . $user->getFullname() . '</i>',
            '$FORM$' => $this->internalCreateUserSettingsForm($user)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showChangePasswordForm() {
        global $app;

        if(!$app->isset('id')) {
            $app->flashMessage('These values: ' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($app->missingUrlValues, ',') . ' are missing!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $id = htmlspecialchars($_GET['id']);
        $user = $app->userModel->getUserById($id);

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/users/user-new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'Update password for user <i>' . $user->getFullname() . '</i>',
            '$FORM$' => $this->internalCreateChangePasswordForm($user)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function changePassword() {
        global $app;

        if(!$app->isset('id')) {
            $app->flashMessage('These values: ' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($app->missingUrlValues, ',') . ' are missing!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $id = htmlspecialchars($_GET['id']);
        $user = $app->userModel->getUserById($id);

        $currentPassword = htmlspecialchars($_POST['current_password']);
        $password1 = htmlspecialchars($_POST['password1']);
        $password2 = htmlspecialchars($_POST['password2']);

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

                $app->redirect('UserModule:HomePage:showHomepage');
            } else {
                $app->flashMessage('New passwords do not match or they match the current password used', FlashMessageTypes::ERROR);
                $app->redirect('UserModule:Users:showChangePasswordForm', array('id' => $id));
            }
        } else {
            $app->flashMessage('Entered current password does not match the one this account has!', FlashMessageTypes::ERROR);
            $app->redirect('UserModule:Users:showChangePasswordForm', array('id' => $id));
        }
    }

    protected function showProfile() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/users/user-profile-grid.html');

        if(!$app->isset('id')) {
            $app->flashMessage('These values: ' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($app->missingUrlValues, ',') . ' are missing!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $id = htmlspecialchars($_GET['id']);

        $user = $app->userModel->getUserById($id);

        $editLink = '';

        if($app->actionAuthorizator->checkActionRight(UserActionRights::EDIT_USER)) {
            $editLink = LinkBuilder::createAdvLink(array(
                'page' => 'UserModule:Users:showEditForm',
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

        if($app->actionAuthorizator->checkActionRight(UserActionRights::REQUEST_PASSWORD_CHANGE_USER)) {
            $requestPasswordChangeLink = '&nbsp;&nbsp;' . LinkBuilder::createAdvLink(array(
                'page' => 'UserModule:Users:requestPasswordChange',
                'id' => $id
            ), 'Request password change');

            $forcePasswordChangeLink = '&nbsp;&nbsp;' . LinkBuilder::createAdvLink(array(
                'page' => 'UserModule:Users:forcePasswordChange',
                'id' => $id
            ), 'Force password change');
        }

        if($id == $app->user->getId()) {
            // current user
            $changePasswordLink = '&nbsp;&nbsp;' . LinkBuilder::createAdvLink(array(
                'page' => 'UserModule:Users:showChangePasswordForm',
                'id' => $id
            ), 'Change password');

            $data['$LINKS$'][] = $changePasswordLink;
        } else {
            $data['$LINKS$'][] = $requestPasswordChangeLink;
            $data['$LINKS$'][] = $forcePasswordChangeLink;
        }

        $data['$LINKS$'][] = '&nbsp;&nbsp;' . LinKBuilder::createAdvLink(array('page' => 'UserModule:Users:showSettingsForm', 'id' => $id), 'Settings');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function forcePasswordChange() {
        global $app;

        if(!$app->isset('id')) {
            $app->flashMessage('These values: ' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($app->missingUrlValues, ',') . ' are missing!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $id = htmlspecialchars($_GET['id']);

        $data = array(
            'status' => UserStatus::PASSWORD_UPDATE_REQUIRED,
            'password_change_status' => UserPasswordChangeStatus::FORCE
        );

        $app->userModel->updateUser($id, $data);

        $app->flashMessage('Request password change for user #' . $id . ' successful.', 'success');
        $app->redirect('UserModule:Users:showProfile', array('id' => $id));
    }

    protected function requestPasswordChange() {
        global $app;

        if(!$app->isset('id')) {
            $app->flashMessage('These values: ' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($app->missingUrlValues, ',') . ' are missing!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $id = htmlspecialchars($_GET['id']);

        $data = array(
            'password_change_status' => UserPasswordChangeStatus::WARNING
        );

        $app->userModel->updateUser($id, $data);

        $app->flashMessage('Request password change for user #' . $id . ' successful.', 'success');
        $app->redirect('UserModule:Users:showProfile', array('id' => $id));
    }

    protected function showEditForm() {
        global $app;

        if(!$app->isset('id')) {
            $app->flashMessage('These values: ' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($app->missingUrlValues, ',') . ' are missing!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/users/user-new-entity-form.html');

        $id = htmlspecialchars($_GET['id']);
        $user = $app->userModel->getUserById($id);

        $data = array(
            '$PAGE_TITLE$' => 'Edit user \'' . $user->getFullname() . '\'',
            '$FORM$' => $this->internalCreateEditUserForm($user)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function saveUserEdit() {
        global $app;

        if(!$app->isset('id')) {
            $app->flashMessage('These values: ' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($app->missingUrlValues, ',') . ' are missing!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $id = htmlspecialchars($_GET['id']);
        
        $required = array('firstname', 'lastname', 'username');
        
        $data = [];
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

        $app->userModel->updateUser($id, $data);

        $app->flashMessage('Successfully edited user #' . $id, 'success');
        $app->redirect('UserModule:Users:showProfile', array('id' => $id));
    }

    protected function showUserRights() {
        global $app;

        if(!$app->actionAuthorizator->checkActionRight(UserActionRights::MANAGE_USER_RIGHTS)) {
            $app->redirect('UserModule:Settings:showUsers');
        }

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/users/user-rights-grid.html');

        if(!$app->isset('id')) {
            $app->flashMessage('These values: ' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($app->missingUrlValues, ',') . ' are missing!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $id = htmlspecialchars($_GET['id']);
        $user = $app->userModel->getUserById($id);

        $userRights = '';

        $app->logger->logFunction(function() use (&$userRights, $id) {
            $userRights = $this->internalCreateUserRightsGrid($id);
        }, __METHOD__);

        $links = array(
            '<a class="general-link" href="?page=UserModule:Users:allowAllRights&id_user=' . $id . '">Allow all</a>',
            '&nbsp;&nbsp;',
            '<a class="general-link" href="?page=UserModule:Users:denyAllRights&id_user=' . $id . '">Deny all</a>'
        );

        $data = array(
            '$PAGE_TITLE$' => '<i>' . $user->getFullname() . '</i> rights',
            '$USER_RIGHTS_GRID$' => $userRights,
            '$LINKS$' => '<div class="row"><div class="col-md">' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($links) . '</div></div>'
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function allowAllRights() {
        global $app;

        if(!$app->isset('id_user')) {
            $app->flashMessage('These values: ' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($app->missingUrlValues, ',') . ' are missing!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $idUser = htmlspecialchars($_GET['id_user']);

        $allow = true;

        $app->getConn()->beginTransaction();

        foreach(UserActionRights::$all as $ar) {
            if($app->userRightModel->checkActionRightExists($idUser, $ar)) {
                $app->userRightModel->updateActionRight($idUser, $ar, $allow);
            } else {
                $app->userRightModel->insertActionRightForIdUser($idUser, $ar, $allow);
            }
        }

        foreach(PanelRights::$all as $pr) {
            if($app->userRightModel->checkPanelRightExists($idUser, $pr)) {
                $app->userRightModel->updatePanelRight($idUser, $pr, $allow);
            } else {
                $app->userRightModel->insertPanelRightForIdUser($idUser, $pr, $allow);
            }
        }

        foreach(BulkActionRights::$all as $bar) {
            if($app->userRightModel->checkBulkActionRightExists($idUser, $bar)) {
                $app->userRightModel->updateBulkActionRight($idUser, $bar, $allow);
            } else {
                $app->userRightModel->insertBulkActionRightForIdUser($idUser, $bar, $allow);
            }
        }

        $app->getConn()->commit();

        $cms = array(
            CacheManager::getTemporaryObject(CacheCategories::ACTIONS),
            CacheManager::getTemporaryObject(CacheCategories::BULK_ACTIONS),
            CacheManager::getTemporaryObject(CacheCategories::PANELS)
        );

        foreach($cms as $cm) {
            $cm->invalidateCache();
        }

        $app->redirect('UserModule:Users:showUserRights', array('id' => $idUser));
    }

    protected function denyAllRights() {
        global $app;

        if(!$app->isset('id_user')) {
            $app->flashMessage('These values: ' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($app->missingUrlValues, ',') . ' are missing!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $idUser = htmlspecialchars($_GET['id_user']);

        $allow = false;

        $app->getConn()->beginTransaction();

        foreach(UserActionRights::$all as $ar) {
            if($app->userRightModel->checkActionRightExists($idUser, $ar)) {
                $app->userRightModel->updateActionRight($idUser, $ar, $allow);
            } else {
                $app->userRightModel->insertActionRightForIdUser($idUser, $ar, $allow);
            }
        }

        foreach(PanelRights::$all as $pr) {
            if($app->userRightModel->checkPanelRightExists($idUser, $pr)) {
                $app->userRightModel->updatePanelRight($idUser, $pr, $allow);
            } else {
                $app->userRightModel->insertPanelRightForIdUser($idUser, $pr, $allow);
            }
        }

        foreach(BulkActionRights::$all as $bar) {
            if($app->userRightModel->checkBulkActionRightExists($idUser, $bar)) {
                $app->userRightModel->updateBulkActionRight($idUser, $bar, $allow);
            } else {
                $app->userRightModel->insertBulkActionRightForIdUser($idUser, $bar, $allow);
            }
        }

        $app->getConn()->commit();

        $cms = array(
            CacheManager::getTemporaryObject(CacheCategories::ACTIONS),
            CacheManager::getTemporaryObject(CacheCategories::BULK_ACTIONS),
            CacheManager::getTemporaryObject(CacheCategories::PANELS)
        );

        foreach($cms as $cm) {
            $cm->invalidateCache();
        }

        $app->redirect('UserModule:Users:showUserRights', array('id' => $idUser));
    }

    protected function allowActionRight() {
        global $app;

        if(!$app->isset('name', 'id')) {
            $app->flashMessage('These values: ' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($app->missingUrlValues, ',') . ' are missing!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $name = htmlspecialchars($_GET['name']);
        $idUser = htmlspecialchars($_GET['id']);

        if($app->userRightModel->checkActionRightExists($idUser, $name) === TRUE) {
            $app->userRightModel->updateActionRight($idUser, $name, true);
        } else {
            $app->userRightModel->insertActionRightForIdUser($idUser, $name, true);
        }

        $app->logger->info('Allowed action right to user #' . $idUser, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::ACTIONS);
        $cm->invalidateCache();

        $app->redirect('UserModule:Users:showUserRights', array('id' => $idUser));
    }

    protected function denyActionRight() {
        global $app;

        if(!$app->isset('name', 'id')) {
            $app->flashMessage('These values: ' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($app->missingUrlValues, ',') . ' are missing!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $name = htmlspecialchars($_GET['name']);
        $idUser = htmlspecialchars($_GET['id']);

        if($app->userRightModel->checkActionRightExists($idUser, $name) === TRUE) {
            $app->userRightModel->updateActionRight($idUser, $name, false);
        } else {
            $app->userRightModel->insertActionRightForIdUser($idUser, $name, false);
        }

        $app->logger->info('Denied action right to user #' . $idUser, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::ACTIONS);
        $cm->invalidateCache();

        $app->redirect('UserModule:Users:showUserRights', array('id' => $idUser));
    }

    protected function allowPanelRight() {
        global $app;

        if(!$app->isset('name', 'id')) {
            $app->flashMessage('These values: ' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($app->missingUrlValues, ',') . ' are missing!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $name = htmlspecialchars($_GET['name']);
        $idUser = htmlspecialchars($_GET['id']);

        if($app->userRightModel->checkPanelRightExists($idUser, $name) === TRUE) {
            $app->userRightModel->updatePanelRight($idUser, $name, true);
        } else {
            $app->userRightModel->insertPanelRightForIdUser($idUser, $name, true);
        }

        $app->logger->info('Allowed panel right to user #' . $idUser, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::PANELS);
        $cm->invalidateCache();

        $app->redirect('UserModule:Users:showUserRights', array('id' => $idUser));
    }

    protected function denyPanelRight() {
        global $app;

        if(!$app->isset('name', 'id')) {
            $app->flashMessage('These values: ' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($app->missingUrlValues, ',') . ' are missing!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $name = htmlspecialchars($_GET['name']);
        $idUser = htmlspecialchars($_GET['id']);

        if($app->userRightModel->checkPanelRightExists($idUser, $name) === TRUE) {
            $app->userRightModel->updatePanelRight($idUser, $name, false);
        } else {
            $app->userRightModel->insertPanelRightForIdUser($idUser, $name, false);
        }

        $app->logger->info('Denied panel right to user #' . $idUser, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::PANELS);
        $cm->invalidateCache();

        $app->redirect('UserModule:Users:showUserRights', array('id' => $idUser));
    }

    protected function allowBulkActionRight() {
        global $app;

        if(!$app->isset('name', 'id')) {
            $app->flashMessage('These values: ' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($app->missingUrlValues, ',') . ' are missing!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $name = htmlspecialchars($_GET['name']);
        $idUser = htmlspecialchars($_GET['id']);

        if($app->userRightModel->checkBulkActionRightExists($idUser, $name) === TRUE) {
            $app->userRightModel->updateBulkActionRight($idUser, $name, true);
        } else {
            $app->userRightModel->insertBulkActionRightForIdUser($idUser, $name, true);
        }

        $app->logger->info('Allowed bulk action right to user #' . $idUser, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::BULK_ACTIONS);
        $cm->invalidateCache();

        $app->redirect('UserModule:Users:showUserRights', array('id' => $idUser));
    }

    protected function denyBulkActionRight() {
        global $app;

        if(!$app->isset('name', 'id')) {
            $app->flashMessage('These values: ' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($app->missingUrlValues, ',') . ' are missing!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $name = htmlspecialchars($_GET['name']);
        $idUser = htmlspecialchars($_GET['id']);

        if($app->userRightModel->checkBulkActionRightExists($idUser, $name) === TRUE) {
            $app->userRightModel->updateBulkActionRight($idUser, $name, false);
        } else {
            $app->userRightModel->insertBulkActionRightForIdUser($idUser, $name, false);
        }

        $app->logger->info('Denied bulk action right to user #' . $idUser, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::BULK_ACTIONS);
        $cm->invalidateCache();

        $app->redirect('UserModule:Users:showUserRights', array('id' => $idUser));
    }

    private function internalCreateUserRightsGrid(int $idUser) {
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

        $actionRights = $app->userRightModel->getActionRightsForIdUser($idUser);
        $panelRights = $app->userRightModel->getPanelRightsForIdUser($idUser);
        $bulkActionRights = $app->userRightModel->getBulkActionRightsForIdUser($idUser);

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
                    $allowLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:allowActionRight', 'name' => $name, 'id' => $idUser), 'Allow');
                    $denyLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:denyActionRight', 'name' => $name, 'id' => $idUser), 'Deny');
                    break;

                case 'panel':
                    $allowLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:allowPanelRight', 'name' => $name, 'id' => $idUser), 'Allow');
                    $denyLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:denyPanelRight', 'name' => $name, 'id' => $idUser), 'Deny');
                    break;

                case 'bulk':
                    $allowLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:allowBulkActionRight', 'name' => $name, 'id' => $idUser), 'Allow');
                    $denyLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:denyBulkActionRight', 'name' => $name, 'id' => $idUser), 'Deny');
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

    private function internalCreateUserProfileGrid(int $idUser) {
        global $app;

        $user = $app->userModel->getUserById($idUser);

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
            ->addElement($fb->createLabel()->setFor('firstname')->setText('First name'))
            ->addElement($fb->createInput()->setType('text')->setName('firstname')->require()->setValue($user->getFirstname() ?? ''))

            ->addElement($fb->createLabel()->setFor('lastname')->setText('Last name'))
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

        ->addElement($fb->createLabel()->setFor('current_password')->setText('Current password'))
        ->addElement($fb->createInput()->setType('password')->setName('current_password')->require())

        ->addElement($fb->createLabel()->setFor('password1')->setText('New password'))
        ->addElement($fb->createInput()->setType('password')->setName('password1')->require())

        ->addElement($fb->createLabel()->setFor('password2')->setText('New password again'))
        ->addElement($fb->createInput()->setType('password')->setName('password2')->require())

        ->addElement($fb->createSubmit('Save')->setId('submit'))
        ;

        $form = $fb->build();

        return $form;
    }

    private function internalCreateUserSettingsForm(User $user) {
        global $app;

        $fb = FormBuilder::getTemporaryObject();
        
        $pages = array();

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

        $fb
        ->setMethod('POST')
        ->setAction('?page=UserModule:Users:saveSettings&id=' . $user->getId())

        ->addElement($fb->createLabel()->setFor('default_user_page_url')->setText('Default page'))
        ->addElement($fb->createSelect()->setName('default_user_page_url')->addOptionsBasedOnArray($pages))

        ->addElement($fb->createSubmit('Save'))
        ;

        return $fb->build();
    }
}

?>