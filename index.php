<?php

session_start();

include('app/dms_loader.php');

?>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Document Management System</title>

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

                if($app->currentUrl != $app::URL_LOGIN_PAGE) {
                    $app->redirect($app::URL_LOGIN_PAGE);
                }
            }

            $app->setCurrentUser($app->userModel->getUserById($_SESSION['id_current_user']));
        } else {
            if(!isset($_SESSION['login_in_process'])) {
                if($app->currentUrl != $app::URL_LOGIN_PAGE && $app->currentUrl != 'AnonymModule:LoginPage:showFirstLoginForm') {
                    $app->redirect($app::URL_LOGIN_PAGE);
                }
            }
        }

        $app->showPage();

        ?>
    </body>
</html>