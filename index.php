<?php

use DMS\Constants\CacheCategories;
use DMS\Constants\FlashMessageTypes;
use DMS\Constants\UserPasswordChangeStatus;
use DMS\Core\AppConfiguration;
use DMS\Core\CacheManager;
use DMS\Panels\Panels;

session_start();

include('app/dms_loader.php');

if(isset($_GET['page'])) {
    $page = htmlspecialchars($_GET['page']);

    $app->currentUrl = $page;
} else {
    $app->redirect($app::URL_HOME_PAGE);
}

if(isset($_SESSION['id_current_user'])) {
    if(strtotime($_SESSION['session_end_date']) < time() ||
       !isset($_SESSION['last_login_hash'])) {
        unset($_SESSION['id_current_user']);
        unset($_SESSION['session_end_date']);
        unset($_SESSION['last_login_hash']);

        $app->flashMessage('You must login again.', 'error');

        if($app->currentUrl != $app::URL_LOGIN_PAGE) {
            $app->redirect($app::URL_LOGIN_PAGE);
        }
    } else {
        $ucm = new CacheManager(true, CacheCategories::USERS, AppConfiguration::getLogDir(), AppConfiguration::getCacheDir());

        $user = null;

        $cacheUser = $ucm->loadUserByIdFromCache($_SESSION['id_current_user']);

        if(is_null($cacheUser)) {
            $user = $app->userModel->getUserById($_SESSION['id_current_user']);

            $ucm->saveUserToCache($user);
        } else {
            $user = $cacheUser;
        }

        if(isset($_SESSION['last_login_hash'])) {
            $loginHash = $app->userModel->getLastLoginHashForIdUser($_SESSION['id_current_user']);

            if($loginHash === NULL) {
                if($app->currentUrl != $app::URL_LOGIN_PAGE) {
                    $app->redirect($app::URL_LOGIN_PAGE);
                }
            }

            if($_SESSION['last_login_hash'] == $loginHash) { // ok
                $app->logger->info('Successfully authenticated user #' . $user->getId());
                $app->setCurrentUser($app->userModel->getUserById($_SESSION['id_current_user']));
            } else { // hash mismatch
                $app->logger->warn('Login hash mismatch for user #' . $user->getId() . '. Requesting relogin!');
                $app->flashMessage('Auto authentication failed, please log in again.', 'warn');
                unset($_SESSION['last_login_hash']);
                unset($_SESSION['id_current_user']);
                unset($_SESSION['session_end_date']);

                if($app->currentUrl != $app::URL_LOGIN_PAGE) {
                    $app->redirect($app::URL_LOGIN_PAGE);
                }
            }
        } else { // user has not been logged in yet
            if($app->currentUrl != $app::URL_LOGIN_PAGE) {
                $app->redirect($app::URL_LOGIN_PAGE);
            }
        }
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

if(isset($_GET['id_ribbon']) && $_GET['id_ribbon'] != '') {
    $app->currentIdRibbon = htmlspecialchars($_GET['id_ribbon']);
    $_SESSION['id_current_ribbon'] = $app->currentIdRibbon;
} else if(isset($_SESSION['id_current_ribbon'])) {
    $app->currentIdRibbon = $_SESSION['id_current_ribbon'];
}

if($app->user !== NULL) {
    $rcm = CacheManager::getTemporaryObject(CacheCategories::RIBBONS);

    if($rcm->loadRibbons() === NULL) {
        // generate ribbons to cache
        Panels::generateRibbons($app->ribbonAuthorizator, $app->ribbonModel, $app->user);
    }
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

        ?>
    </body>
</html>