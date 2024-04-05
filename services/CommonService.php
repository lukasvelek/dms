<?php

use DMS\Core\AppConfiguration;
use DMS\Core\DB\Database;
use DMS\Core\FileManager;
use DMS\Core\Logger\Logger;
use DMS\Models\ServiceModel;

require_once('App/dms_loader.php');

$fm = new FileManager(AppConfiguration::getLogDir(), AppConfiguration::getCacheDir());
$logger = new Logger($fm);
$db = new Database(AppConfiguration::getDbServer(), AppConfiguration::getDbUser(), AppConfiguration::getDbPass(), AppConfiguration::getDbName(), $logger);

$serviceModel = new ServiceModel($db, $logger);

function start(string $name) {
    global $serviceModel;

    $service = $serviceModel->getServiceByName($name);
    $serviceModel->updateService($service->getId(), ['status' => '1', 'pid' => getmypid()]);
}

function stop(string $name) {
    global $serviceModel;

    $service = $serviceModel->getServiceByName($name);
    $serviceModel->updateService($service->getId(), ['status' => '0', 'pid' => NULL]);
    $serviceModel->insertServiceLog(['name' => SERVICE_NAME, 'text' => 'Service ' . SERVICE_NAME . ' finished running.']);
}

?>