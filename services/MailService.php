<?php

use DMS\Core\CacheManager;
use DMS\Core\MailManager;
use DMS\Services\MailService;

require_once('CommonService.php');

define('SERVICE_NAME', 'MailService');

start(SERVICE_NAME);

$mailManager = new MailManager();

$ms = new MailService($logger, $serviceModel, CacheManager::getTemporaryObject('ppp'), $mailModel, $mailManager);

$ms->run();

stop(SERVICE_NAME);

exit;

?>