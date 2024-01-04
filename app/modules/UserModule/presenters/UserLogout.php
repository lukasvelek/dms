<?php

namespace DMS\Modules\UserModule;

use DMS\Modules\APresenter;

class UserLogout extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('UserLogout', 'Logout user');
    }

    protected function logoutUser() {
        global $app;
        if($app->userAuthenticator->logoutCurrentUser()) {
            $app->clearFlashMessage();
            $app->redirect($app::URL_LOGIN_PAGE);
        }
    }
}

?>