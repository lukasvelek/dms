<?php

use DMS\Core\CacheManager;
use DMS\Services\DeclinedDocumentRemoverService;

require_once('CommonService.php');

define('SERVICE_NAME', 'DeclinedDocumentRemoverService');

start(SERVICE_NAME);

$ddrs = new DeclinedDocumentRemoverService($logger, $serviceModel, CacheManager::getTemporaryObject('ppp'), $documentModel, $documentAuthorizator, $documentMetadataHistoryModel, $documentLockComponent);

$ddrs->run();

stop(SERVICE_NAME);

exit;

?>