<?php

namespace DMS\Modules\UserModule;

use DMS\Helpers\FormDataHelper;
use DMS\Modules\APresenter;

class AjaxHelperPresenter extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('AjaxHelper');

        $this->getActionNamesFromClass($this);
    }

    protected function flashMessage() {
        global $app;

        $app->flashMessageIfNotIsset(array('message', 'type', 'redirect'), false);

        $message = FormDataHelper::get('message');
        $type = FormDataHelper::get('type');
        $redirect = FormDataHelper::get('redirect');

        $toUnset = ['message', 'type', 'redirect', 'page'];

        foreach($toUnset as $tu) {
            unset($_GET[$tu]);
        }

        $special = $_GET;

        $app->flashMessage($message, $type);

        if(!empty($special)) {
            $app->redirect($redirect, $special);
        } else {
            $app->redirect($redirect);
        }
    }
}

?>