<?php

require_once('CommonService.php');

define('SERVICE_NAME', 'LogRotateService');

start(SERVICE_NAME);
$logger->info('Service start', SERVICE_NAME);
sleep(100);
stop(SERVICE_NAME);

exit;

?>