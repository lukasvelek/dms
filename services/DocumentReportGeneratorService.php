<?php

use DMS\Components\DocumentReportGeneratorComponent;
use DMS\Components\ExternalEnumComponent;
use DMS\Core\CacheManager;
use DMS\Core\FileStorageManager;
use DMS\Services\DocumentReportGeneratorService;

require_once('CommonService.php');

define('SERVICE_NAME', 'DocumentReportGeneratorService');

start(SERVICE_NAME);

$fsm = new FileStorageManager($fm, $logger, $fileStorageModel);
$eec = new ExternalEnumComponent($models);
$drgc = new DocumentReportGeneratorComponent($models, $fm, $eec, $fsm);

$service = new DocumentReportGeneratorService($logger, $serviceModel, CacheManager::getTemporaryObject('notific'), $documentModel, $drgc, $notificationComponent);
$service->run();

stop(SERVICE_NAME);

exit;

?>