<?php

use DMS\Core\CacheManager;
use DMS\Services\NotificationManagerService;

require_once('CommonService.php');

define('SERVICE_NAME', 'NotificationManagerService');

start(SERVICE_NAME);

$nms = new NotificationManagerService($logger, $serviceModel, CacheManager::getTemporaryObject('ppp'), $notificationModel);

$nms->run();

stop(SERVICE_NAME);

exit;

?>