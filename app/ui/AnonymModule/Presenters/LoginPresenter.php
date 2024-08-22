<?php

namespace App\UI\AnonymModule\Presenters;

use App\Components\Form\FormFactory;
use App\Components\Form\Helpers\Form2StateListHelper;
use App\Components\Form\Helpers\StateList2FormHelper;
use App\UI\APresenter;

class LoginPresenter extends APresenter {
    public function __construct() {
        parent::__construct('LoginPresenter');
    }

    public function handleLoginForm() {
        $form = $this->createComponentLoginForm();
    
        $statelist = Form2StateListHelper::convertFormToStateList($form);

        var_dump($statelist);
        $form2 = StateList2FormHelper::convertStateList2Form($statelist);

        $this->template->form = $form;
    }

    private function createComponentLoginForm() {
        $form = new FormFactory();

        $form->addTextInput('username', 'Username:');

        return $form;
    }
}

?>