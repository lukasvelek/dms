<?php

use DMS\Core\AppConfiguration;
use DMS\Exceptions\AException;
use DMS\Exceptions\ValueIsNullException;
use DMS\Helpers\DatetimeFormatHelper;

require_once('Ajax.php');

$action = null;

if(isset($_GET['action'])) {
    $action = htmlspecialchars($_GET['action']);
} else if(isset($_POST['action'])) {
    $action = htmlspecialchars($_POST['action']);
}

if($action == null) {
    throw new ValueIsNullException('$action');
}

try {
    echo($action());
} catch(AException $e) {
    echo('<b>Exception: </b>' . $e->getMessage() . '<br><b>Stack trace: </b>' . $e->getTraceAsString());
    exit;
}

function loadCount() {
    global $notificationModel, $user, $logger;

    if($user == null) {
        exit;
    }

    if(!isset($_SESSION['user_notification_count']) || !isset($_SESSION['user_notification_count_timestamp'])) {
        $_SESSION['user_notification_count'] = count($notificationModel->getNotificationsForUser($user->getId()));
        $_SESSION['user_notification_count_timestamp'] = time();

        $logger->info('Notifications for user #' . $user->getId() . ' loaded.', __METHOD__);

        echo($_SESSION['user_notification_count']);
    } else {
        $timeLength = 10; // seconds
        $difference = time() - $_SESSION['user_notification_count_timestamp'];
        $remaining = ($timeLength) - $difference;

        if($difference > ($timeLength)) {
            unset($_SESSION['user_notification_count']);
            unset($_SESSION['user_notification_count_timestamp']);

            $logger->info('Loaded notifications for user #' . $user->getId() . ' are too old. Reloading...', __METHOD__);

            loadCount();
        } else {
            $logger->info('Notifications for user #' . $user->getId() . ' loaded from cache. Remaining age: ' . $remaining . 's');
            
            echo $_SESSION['user_notification_count'];
        }
    }
}

function hideNotification() {
    global $notificationModel;

    $id = htmlspecialchars($_GET['id']);

    $notificationModel->setSeen($id);

    unset($_SESSION['user_notification_count']);
    unset($_SESSION['user_notification_count_timestamp']);
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

        $code .= '<span>';
        $code .= '<a class="general-link" onclick="deleteAllNotifications()" style="cursor: pointer">Delete all notifications</a>';
        $code .= '</span>';
        $code .= '<hr>';
    
        $i = 0;
        foreach($notifications as $notification) {
            $actionLink = '<a class="general-link" onclick="useNotification(\'' . $notification->getId() . '\', \'' . $notification->getAction() . '\')" style="cursor: pointer">Open</a>';

            $dateCreated = $notification->getDateCreated();
            if(!is_null($user)) {
                $dateCreated = DatetimeFormatHelper::formatDateByUserDefaultFormat($dateCreated, $user);
            } else {
                $dateCreated = DatetimeFormatHelper::formatDateByFormat($dateCreated, AppConfiguration::getDefaultDatetimeFormat());
            }

            $code .= '<span>';
            $code .= $notification->getText();
            $code .= '<br>';
            $code .= $actionLink;
            $code .= '<br>';
            $code .= $dateCreated;
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

function deleteAll() {
    global $notificationModel, $user;

    if($user == null) {
        exit;
    }

    $notifications = $notificationModel->getNotificationsForUser($user->getId());

    foreach($notifications as $notification) {
        $notificationModel->setSeen($notification->getId());
    }

    unset($_SESSION['user_notification_count']);
    unset($_SESSION['user_notification_count_timestamp']);
}

exit;

?>