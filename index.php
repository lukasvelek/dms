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
        
        echo $app->redirect('AnonymModule:LoginPage:showForm');
        
        ?>
    </body>
</html>