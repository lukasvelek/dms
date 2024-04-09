<?php

use DMS\Core\CacheManager;
use DMS\Core\FileManager;
use DMS\Core\FileStorageManager;
use DMS\Services\ExtractionService;

require_once('CommonService.php');

define('SERVICE_NAME', 'ExtractionService');

start(SERVICE_NAME);

$fsm = new FileStorageManager(FileManager::getTemporaryObject(), $logger, $fileStorageModel);

$es = new ExtractionService($logger, $serviceModel, CacheManager::getTemporaryObject('service'), $documentRepository, $documentCommentRepository, $fsm, $groupModel);
$es->run();

stop(SERVICE_NAME);

exit;

?>