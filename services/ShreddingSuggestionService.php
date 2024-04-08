<?php

use DMS\Core\CacheManager;
use DMS\Services\ShreddingSuggestionService;

require_once('CommonService.php');

define('SERVICE_NAME', 'ShreddingSuggestionService');

start(SERVICE_NAME);

$sss = new ShreddingSuggestionService($logger, $serviceModel, CacheManager::getTemporaryObject('ppp'), $documentAuthorizator, $documentModel, $processComponent);

$sss->run();

stop(SERVICE_NAME);

exit;

?>