<?php

namespace DMS\Modules\AnonymModule;

use DMS\Core\Logger\LogCategoryEnum;
use \DMS\Modules\IModule;
use \DMS\Modules\IPresenter;
use \DMS\Core\TemplateManager;
use \DMS\UI\FormBuilder\FormBuilder;

class LoginPage implements IPresenter {
    /**
     * @var string
     */
    private $name;

    /**
     * @var TemplateManager
     */
    private $templateManager;

    private $module;

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

    public function performAction(string $name) {
        if(method_exists($this, $name)) {
            return $this->$name();
        } else {
            die('Method does not exist!');
        }
    }

    public function showForm() {
        $template = $this->templateManager->loadTemplate('app/modules/AnonymModule/presenters/templates/LoginForm.html');

        $data = array(
            '$PAGE_TITLE$' => 'Login form',
            '$LOGIN_FORM$' => $this->internalRenderForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
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

    public function tryLogin() {
        $username = htmlspecialchars($_POST['username']);
        $password = htmlspecialchars($_POST['password']);

        $userAuthenticator = $this->module->getComponent('userAuthenticator');

        die($userAuthenticator->authUser($username, $password));
    }
}

?>