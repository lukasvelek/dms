<?php

use DMS\Constants\CacheCategories;
use DMS\Constants\FlashMessageTypes;
use DMS\Constants\UserPasswordChangeStatus;
use DMS\Core\CacheManager;

session_start();

var_dump($_SESSION);
//var_dump($_SERVER);

include('app/dms_loader.php');

if(isset($_GET['page'])) {
    $page = htmlspecialchars($_GET['page']);

    $app->currentUrl = $page;
} else {
    $app->redirect($app::URL_HOME_PAGE);
}

if(isset($_SESSION['id_current_user'])) {
    if(strtotime($_SESSION['session_end_date']) < time()) {
        unset($_SESSION['id_current_user']);
        unset($_SESSION['session_end_date']);

        $app->flashMessage('You have exceeded login time. Please log in again.', FlashMessageTypes::ERROR);

        if($app->currentUrl != $app::URL_LOGIN_PAGE) {
            $app->redirect($app::URL_LOGIN_PAGE);
        }
    } else {
        $ucm = new CacheManager(true, CacheCategories::USERS);

        $user = null;

        $cacheUser = $ucm->loadUserByIdFromCache($_SESSION['id_current_user']);

        if(is_null($cacheUser)) {
            $user = $app->userModel->getUserById($_SESSION['id_current_user']);

            $ucm->saveUserToCache($user);
        } else {
            $user = $cacheUser;
        }

        $app->setCurrentUser($app->userModel->getUserById($_SESSION['id_current_user']));
    }
} else {
    if(!isset($_SESSION['login_in_process'])) {
        if($app->currentUrl != $app::URL_LOGIN_PAGE && $app->currentUrl != 'AnonymModule:LoginPage:showFirstLoginForm') {
            $app->redirect($app::URL_LOGIN_PAGE);
        }
    }
}

if(!is_null($app->user)) {
    if($app->user->getPasswordChangeStatus() == UserPasswordChangeStatus::WARNING) {
        $changeLink = '<a style="color: red; text-decoration: underline" href="?page=UserModule:Users:showChangePasswordForm&id=' . $app->user->getId() . '">here</a>';
        $app->flashMessage('Your password is outdated. You should update it! Click ' . $changeLink . ' to update password.', FlashMessageTypes::WARNING);
    } else if($app->user->getPasswordChangeStatus() == UserPasswordChangeStatus::FORCE) {
        $app->flashMessage('Your password is outdated. You must update it!', FlashMessageTypes::ERROR);
        
        if($app->currentUrl != 'UserModule:UserLogout:logoutUser') {
            $app->redirect($app::URL_LOGOUT_PAGE);
        }
    }
}

if(isset($_GET['id_ribbon'])) {
    $app->currentIdRibbon = htmlspecialchars($_GET['id_ribbon']);
}

$app->loadPages();
$app->renderPage();

$title = 'DMS | ' . $app->currentPresenter->getTitle();

?>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title><?php echo $title; ?></title>
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="css/bootstrap.css">
    </head>
    <body>
        <script type="text/javascript" src="js/jquery-3.7.1.js"></script>
        <script type="text/javascript" src="js/GeneralDMS.js"></script>
        <div id="cover">
            <img style="position: fixed; top: 50%; left: 49%;" src='img/loading.gif' width='32' height='32'>
        </div>
        <div id="notifications" style="display: none;">Notifications (0)</div>
        <?php

        $app->showPage();

        if(isset($_SESSION['flash_message'])) unset($_SESSION['flash_message']);

        ?>
    </body>
</html>