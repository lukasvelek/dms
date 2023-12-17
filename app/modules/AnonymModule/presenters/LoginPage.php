<?php

namespace DMS\Modules\AnonymModule;

use DMS\Constants\UserStatus;
use DMS\Core\CryptManager;
use DMS\Core\Logger\LogCategoryEnum;
use DMS\Core\ScriptLoader;
use \DMS\Modules\IModule;
use \DMS\Core\TemplateManager;
use DMS\Modules\APresenter;
use \DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\LinkBuilder;

class LoginPage extends APresenter {
    private string $name;
    private TemplateManager $templateManager;
    private IModule $module;

    public const DRAW_TOPPANEL = true;

    public function __construct() {
        $this->name = 'LoginPage';

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

    protected function showForm() {
        $template = $this->templateManager->loadTemplate('app/modules/AnonymModule/presenters/templates/GeneralForm.html');

        $data = array(
            '$PAGE_TITLE$' => 'Login form',
            '$FIRST_LOGIN_LINK$' => LinkBuilder::createLink('AnonymModule:LoginPage:showFirstLoginForm', 'First login'),
            '$FORM$' => $this->internalRenderForm()
        );

        $this->templateManager->fill($data, $template);

        $_SESSION['login_in_process'] = true;

        return $template;
    }

    protected function tryLogin() {
        $username = htmlspecialchars($_POST['username']);
        $password = htmlspecialchars($_POST['password']);
        
        global $app;

        $userAuthenticator = $app->getComponent('userAuthenticator');

        $authResult = $userAuthenticator->authUser($username, $password);

        if($authResult != false) {
            $_SESSION['id_current_user'] = $authResult;
            $_SESSION['session_end_date'] = date('Y-m-d H:i:s', (time() + (24 * 60 * 60))); // 1 day

            unset($_SESSION['login_in_process']);

            $app->redirect('UserModule:HomePage:showHomepage');
        }
    }

    protected function showFirstLoginForm() {
        $template = $this->templateManager->loadTemplate('app/modules/AnonymModule/presenters/templates/GeneralForm.html');

        $data = array(
            '$PAGE_TITLE$' => 'First login',
            '$FIRST_LOGIN_LINK$' => LinkBuilder::createLink('AnonymModule:LoginPage:showForm', 'General login'),
            '$FORM$' => $this->internalCreateFirstLoginForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function findUser() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/AnonymModule/presenters/templates/GeneralForm.html');

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

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function savePassword() {
        global $app;

        $id = htmlspecialchars($_GET['id']);

        $password1 = htmlspecialchars($_POST['password1']);
        $password2 = htmlspecialchars($_POST['password2']);
        $suggestedPassword = htmlspecialchars($_POST['suggested_password']);
        $username = htmlspecialchars($_POST['username']);

        if(empty($password1) && empty($password2)) {
            $password1 = $suggestedPassword;
            $password2 = $suggestedPassword;
        }

        if($app->userAuthenticator->checkPasswordMatch(array($password1, $password2))) {
            die('Passwords do not match');
        }

        $password = CryptManager::hashPassword($password1, $username);

        $app->userModel->updateUserPassword($id, $password);
        $app->userModel->updateUserStatus($id, UserStatus::ACTIVE);

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
}

?>