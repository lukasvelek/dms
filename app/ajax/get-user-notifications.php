<?php

require_once('Ajax.php');

if(is_null($user)) {
    $data = ['code' => 'Error: No user logged in!', 'count' => 0];
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

$notifications = $notificationModel->getNotificationsForUser($user->getId());

if(empty($notifications)) {
    $data = ['code' => 'No notifications found', 'count' => 0];
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
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

?>