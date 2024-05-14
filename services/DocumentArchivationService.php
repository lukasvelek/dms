<?php

use DMS\Core\CacheManager;
use DMS\Services\DocumentArchivationService;

require_once('CommonService.php');

define('SERVICE_NAME', 'DocumentArchivationService');

start(SERVICE_NAME);

$das = new DocumentArchivationService($logger, $serviceModel, CacheManager::getTemporaryObject('ppp'), $documentModel, $documentAuthorizator, $documentMetadataHistoryModel, $documentBulkActionAuthorizator);

run(function() use ($das) { $das->run(); });

//$das->run();

stop(SERVICE_NAME);

exit;

?>