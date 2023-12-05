<?php

require_once('Ajax.php');

$action = null;

if(isset($_GET['action'])) {
    $action = htmlspecialchars($_GET['action']);
} else if(isset($_POST['action'])) {
    $action = htmlspecialchars($_POST['action']);
}

if($action == null) {
    exit;
}

echo($action());

function loadCount() {
    global $notificationModel, $user;

    if($user == null) {
        exit;
    }
    
    $notifications = $notificationModel->getNotificationsForUser($user->getId());

    echo count($notifications);
}

function hideNotification() {
    global $notificationModel;

    $id = htmlspecialchars($_GET['id']);

    $notificationModel->setSeen($id);
}

function getNotifications() {
    global $notificationModel, $user;

    if($user == null) {
        exit;
    }

    $notifications = $notificationModel->getNotificationsForUser($user->getId());

    if(empty($notifications)) {
        $data = ['code' => 'No notifications found', 'count' => 0];
        echo json_encode($data, JSON_UNESCAPED_SLASHES);
    } else {
        $code = '';
    
        $i = 0;
        foreach($notifications as $notification) {
            $actionLink = '<a class="general-link" onclick="useNotification(\'' . $notification->getId() . '\', \'' . $notification->getAction() . '\')" style="cursor: pointer">Open</a>';

            $code .= '<span>';
            $code .= $notification->getText();
            $code .= '<br>';
            $code .= $actionLink;
            $code .= '<br>';
            $code .= $notification->getDateCreated();
            $code .= '</span>';

            if(($i + 1) != count($notifications)) {
                $code .= '<hr>';
            }

            $i++;
        }

        $data = ['code' => $code, 'count' => count($notifications)];

        echo json_encode($data, JSON_UNESCAPED_SLASHES);
    }
}

?>