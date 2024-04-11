<?php

use DMS\Core\CacheManager;
use DMS\Services\UserSubstitutionProcessService;

require_once('CommonService.php');

define('SERVICE_NAME', 'UserSubstitutionProcessService');

start(SERVICE_NAME);

$usps = new UserSubstitutionProcessService($logger, $serviceModel, CacheManager::getTemporaryObject('services'), $processComponent, $userAbsenceRepository);
$usps->run();

stop(SERVICE_NAME);

?>