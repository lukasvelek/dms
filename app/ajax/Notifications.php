<?php

require_once('Ajax.php');

$action = null;

if(isset($_GET['action'])) {
    $action = htmlspecialchars($_GET['action']);
} else if(isset($_POST['action'])) {
    $action = htmlspecialchars($_POST['action']);
}

if($action == null) {
    //echo 'No action defined!';
    exit;
}

echo($action());

function loadCount() {
    global $notificationModel;
    global $user;

    if($user == null) {
        exit;
    }
    
    $notifications = $notificationModel->getNotificationsForUser($user->getId());

    echo count($notifications);
}

?>