<?php

use DMS\Constants\CacheCategories;
use DMS\Core\CacheManager;
use DMS\Services\LogRotateService;

require_once('CommonService.php');

define('SERVICE_NAME', 'LogRotateService');

start(SERVICE_NAME);

$lrs = new LogRotateService($logger, $serviceModel, CacheManager::getTemporaryObject(CacheCategories::SERVICE_CONFIG));

$lrs->run();

stop(SERVICE_NAME);

exit;

?>