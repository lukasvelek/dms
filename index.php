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

                $app->redirect($app::URL_LOGIN_PAGE);
            }

            $app->user = $app->userModel->getUserById($_SESSION['id_current_user']);
        } else {
            if(!isset($_SESSION['login_in_process'])) {
                $app->redirect($app::URL_LOGIN_PAGE);
            } else {
                if($app->currentUrl != $app::URL_LOGIN_PAGE) {
                    $app->redirect($app::URL_LOGIN_PAGE);
                }
            }
        }

        $app->showPage();

        ?>
    </body>
</html>