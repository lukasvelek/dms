<?php

namespace DMS\Modules\UserModule;

use DMS\Modules\APresenter;

class AjaxHelper extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('AjaxHelper');

        $this->getActionNamesFromClass($this);
    }

    protected function flashMessage() {
        global $app;

        $message = htmlspecialchars($_GET['message']);
        $type = htmlspecialchars($_GET['type']);
        $redirect = htmlspecialchars($_GET['redirect']);

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