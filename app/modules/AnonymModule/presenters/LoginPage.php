<?php

namespace DMS\Modules\AnonymModule;

use DMS\Constants\FlashMessageTypes;
use DMS\Constants\UserPasswordChangeStatus;
use DMS\Constants\UserStatus;
use DMS\Core\CryptManager;
use DMS\Core\CypherManager;
use DMS\Core\ScriptLoader;
use DMS\Helpers\ArrayStringHelper;
use DMS\Modules\APresenter;
use \DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\LinkBuilder;

class LoginPage extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('LoginPage', 'Login page', true);

        $this->getActionNamesFromClass($this);
    }

    protected function showForm() {
        $template = $this->templateManager->loadTemplate('app/modules/AnonymModule/presenters/templates/GeneralForm.html');

        $data = array(
            '$PAGE_TITLE$' => 'Login form',
            '$FORM$' => $this->internalRenderForm()
        );

        $data['$LINKS$'][] = LinkBuilder::createLink('AnonymModule:LoginPage:showFirstLoginForm', 'First login');
        $data['$LINKS$'][] = '&nbsp;&nbsp;' . LinkBuilder::createLink('AnonymModule:LoginPage:showForgotPasswordForm', 'Forgot password');

        $this->templateManager->fill($data, $template);

        $_SESSION['login_in_process'] = true;

        return $template;
    }

    protected function showForgotPasswordForm() {
        $template = $this->templateManager->loadTemplate('app/modules/AnonymModule/presenters/templates/GeneralForm.html');

        $data = array(
            '$PAGE_TITLE$' => 'Forgot password form',
            '$FORM$' => $this->internalCreateForgotPasswordForm()
        );

        $data['$LINKS$'][] = LinkBuilder::createLink('AnonymModule:LoginPage:showForm', 'Login');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function forgotPassword() {
        global $app;

        if(!$app->isset('email', 'username')) {
            $app->flashMessage('These values: ' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($app->missingUrlValues, ',') . ' are missing!', 'error');
            $app->redirect('AnonymModule:LoginPage:showForgotPasswordForm');
        }

        $emailAddress = htmlspecialchars($_POST['email']);
        $username = htmlspecialchars($_POST['username']);

        $users = $app->userModel->getAllUsersMeetingCondition("WHERE `email` = '$emailAddress' AND `username` = '$username'");

        if(count($users) == 1) {
            // ok

            $user = $users[0];

            $data = array(
                'status' => UserStatus::PASSWORD_UPDATE_REQUIRED,
                'password_change_status' => UserPasswordChangeStatus::FORCE
            );

            $hash = CypherManager::createCypher(64);

            $app->userModel->updateUser($user->getId(), $data);
            $app->mailModel->insertNewQueueEntry($app->mailManager->composeForgottenPasswordEmail($user->getEmail(), $hash));
            $app->userModel->insertPasswordResetHash(array(
                'id_user' => $user->getId(),
                'hash' => $hash
            ));

            $app->flashMessage('An email has been sent to the email address you provided. The email contains a link to reset your password.');
            $app->redirect('AnonymModule:LoginPage:showForm');
        } else {
            $app->flashMessage('The information you provided does not meet anything in the database. Please try again!', FlashMessageTypes::ERROR);
            $app->redirect('AnonymModule:LoginPage:showForgotPasswordForm');
        }
    }

    protected function tryLogin() {
        global $app;
        
        if(!$app->isset('username', 'password')) {
            $app->flashMessage('These values: ' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($app->missingUrlValues, ',') . ' are missing!', 'error');
            $app->redirect('AnonymModule:LoginPage:showForm');
        }

        $username = htmlspecialchars($_POST['username']);
        $password = htmlspecialchars($_POST['password']);

        $authResult = $app->userAuthenticator->authUser($username, $password);

        if($authResult != false) {
            $user = $app->userModel->getUserById($authResult);

            if(!in_array($user->getStatus(), array(UserStatus::ACTIVE))) {
                $app->flashMessage('Password change for your account has been requested. Please create a new password!', FlashMessageTypes::WARNING);
                $app->redirect('AnonymModule:LoginPage:showFirstLoginForm');
            }

            $_SESSION['id_current_user'] = $authResult;
            $_SESSION['session_end_date'] = date('Y-m-d H:i:s', (time() + (24 * 60 * 60))); // 1 day

            unset($_SESSION['login_in_process']);

            $app->redirect('UserModule:HomePage:showHomepage');
            /*if(!is_null($user)) {
                if(!is_null($user->getDefaultUserPageUrl())) {
                    $app->redirect($user->getDefaultUserPageUrl());
                } else {
                    $app->redirect('UserModule:HomePage:showHomepage');
                }
            } else {
                $app->redirect('UserModule:HomePage:showHomepage');
            }*/
        }
    }

    protected function showFirstLoginForm() {
        $template = $this->templateManager->loadTemplate('app/modules/AnonymModule/presenters/templates/GeneralForm.html');

        $data = array(
            '$PAGE_TITLE$' => 'First login',
            '$LINKS$' => array(LinkBuilder::createLink('AnonymModule:LoginPage:showForm', 'General login')),
            '$FORM$' => $this->internalCreateFirstLoginForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function findUser() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/AnonymModule/presenters/templates/GeneralForm.html');

        if(!$app->isset('username')) {
            $app->flashMessage('These values: ' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($app->missingUrlValues, ',') . ' are missing!', 'error');
            $app->redirect('AnonymModule:LoginPage:showForm');
        }

        $username = htmlspecialchars($_POST['username']);

        $user = $app->userModel->getUserForFirstLoginByUsername($username);

        $data = [];
        if($user === NULL) {
            $data = array(
                '$PAGE_TITLE$' => '',
                '$FIRST_LOGIN_LINK$' => '',
                '$FORM$' => 'User with username \'' . $username . '\' does not exist!'
            );
        } else {
            $data = array(
                '$PAGE_TITLE$' => 'Create password for user \'' . $username . '\'',
                '$FIRST_LOGIN_LINK$' => '<p id="msg"></p>' . LinkBuilder::createLink('AnonymModule:LoginPage:showForm', 'General login'),
                '$FORM$' => $this->internalCreatePasswordForm($user->getId(), $username) . ScriptLoader::loadJSScript('/dms/js/FirstLoginPage.js')
            );
        }

        $data['$LINKS$'] = '';

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function savePassword() {
        global $app;

        if(!$app->isset('id', 'password1', 'password2', 'suggested_password', 'username')) {
            $app->flashMessage('These values: ' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($app->missingUrlValues, ',') . ' are missing!', 'error');
            $app->redirect('AnonymModule:LoginPage:findUser');
        }

        $id = htmlspecialchars($_GET['id']);

        $password1 = htmlspecialchars($_POST['password1']);
        $password2 = htmlspecialchars($_POST['password2']);
        $suggestedPassword = htmlspecialchars($_POST['suggested_password']);
        $username = htmlspecialchars($_POST['username']);

        if(empty($password1) && empty($password2)) {
            $password1 = $suggestedPassword;
            $password2 = $suggestedPassword;
        }

        if(!$app->userAuthenticator->checkPasswordMatch(array($password1, $password2))) {
            die('Passwords do not match');
        }

        $password = CryptManager::hashPassword($password1);

        $data = array(
            'password_change_status' => UserPasswordChangeStatus::OK,
            'status' => UserStatus::ACTIVE
        );

        $app->userModel->updateUser($id, $data);
        $app->userModel->updateUserPassword($id, $password);

        $app->redirect('AnonymModule:LoginPage:showForm');
    }

    private function internalCreatePasswordForm(int $id, string $username) {
        $fb = FormBuilder::getTemporaryObject();

        $suggestedPassword = CryptManager::suggestPassword();

        $fb ->setAction('?page=AnonymModule:LoginPage:savePassword&id=' . $id)->setMethod('POST')
            ->addElement($fb->createLabel()->setText('Password')
                                           ->setFor('password1'))

            ->addElement($fb->createInput()->setType('password')
                                           ->setName('password1')
                                           ->setMaxLength('256')
                                           ->setId('password1')
                                           ->setPlaceHolder($suggestedPassword))

            ->addElement($fb->createLabel()->setText('Password again')
                                           ->setFor('password2'))

            ->addElement($fb->createInput()->setType('password')
                                           ->setName('password2')
                                           ->setMaxLength('256')
                                           ->setId('password2')
                                           ->setPlaceHolder($suggestedPassword))

            ->addElement($fb->createLabel()->setText('To use suggested password, leave the input elements empty.'))

            ->addElement($fb->createSpecial('<input type="text" name="suggested_password" value="' . $suggestedPassword . '" hidden>'))
            
            ->addElement($fb->createSpecial('<input type="text" name="username" value="' . $username . '" hidden>'))

            ->addElement($fb->createSubmit()->setId('submit'))
        ;

        $form = $fb->build();

        return $form;
    }

    private function internalCreateFirstLoginForm() {
        $fb = FormBuilder::getTemporaryObject();

        $fb ->setAction('?page=AnonymModule:LoginPage:findUser')->setMethod('POST')
            ->addElement($fb->createLabel()->setText('Username')
                                           ->setFor('username'))
            ->addElement($fb->createInput()->setType('text')
                                           ->setName('username')
                                           ->setMaxLength('256')
                                           ->require())
            ->addElement($fb->createSubmit('Look up user'))
        ;

        $form = $fb->build();

        return $form;
    }

    private function internalRenderForm() {
        $fb = FormBuilder::getTemporaryObject();

        $fb = $fb->setAction('?page=AnonymModule:LoginPage:tryLogin')->setMethod('POST')
                 ->addElement($fb->createLabel()->setText('Username')
                                                ->setFor('username'))
                 ->addElement($fb->createInput()->setType('text')
                                                ->setName('username')
                                                ->setMaxLength('256')
                                                ->require())
                 ->addElement($fb->createLabel()->setText('Password')
                                                ->setFor('password'))
                 ->addElement($fb->createInput()->setType('password')
                                                ->setName('password')
                                                ->setMaxLength('256')
                                                ->require())
                 ->addElement($fb->createSubmit('Login'))
                ;

        $form = $fb->build();

        return $form;
    }

    private function internalCreateForgotPasswordForm() {
        $fb = FormBuilder::getTemporaryObject();

        $fb
        ->setAction('?page=AnonymModule:LoginPage:forgotPassword')->setMethod('POST')

        ->addElement($fb->createSpecial('<span>Enter the email address associated to your account. An email containing a link to reset your password will be sent to the email address provided.</span><br><br>'))

        ->addElement($fb->createLabel()->setText('Username')->setFor('username'))
        ->addElement($fb->createInput()->setType('text')->setName('username')->require())

        ->addElement($fb->createLabel()->setText('Email')->setFor('email'))
        ->addElement($fb->createInput()->setType('email')->setName('email')->require())

        ->addElement($fb->createSubmit('Send'))
        ;

        $form = $fb->build();
        return $form;
    }
}

?>