<?php

use DMS\Core\CacheManager;
use DMS\Core\FileStorageManager;
use DMS\Services\FileManagerService;

require_once('CommonService.php');

define('SERVICE_NAME', 'FileManagerService');

start(SERVICE_NAME);

$fileStorageManager = new FileStorageManager($fm, $logger, $fileStorageModel);

$fms = new FileManagerService($logger, $serviceModel, $fileStorageManager, $documentModel, CacheManager::getTemporaryObject('ppp'), $fileStorageModel);

run(function() use ($fms) { $fms->run(); });

//$fms->run();

stop(SERVICE_NAME);

exit;

?>