<?php

use DMS\Core\CacheManager;
use DMS\Services\UserLoginBlockingManagerService;

require_once('CommonService.php');

define('SERVICE_NAME', 'UserLoginBlockingManagerService');

start(SERVICE_NAME);

$ulbms = new UserLoginBlockingManagerService($logger, $serviceModel, CacheManager::getTemporaryObject('service'), $userRepository);
$ulbms->run();

stop(SERVICE_NAME);

?>