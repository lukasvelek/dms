<?php

require_once('Ajax.php');

$id = htmlspecialchars($_GET['id']);

$notificationModel->setSeen($id);

?>