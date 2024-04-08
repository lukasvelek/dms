<?php

use DMS\Core\AppConfiguration;
use DMS\Core\DB\Database;
use DMS\Core\FileManager;
use DMS\Core\Logger\Logger;
use DMS\Authorizators\ActionAuthorizator;
use DMS\Authorizators\ArchiveAuthorizator;
use DMS\Authorizators\BulkActionAuthorizator;
use DMS\Authorizators\DocumentAuthorizator;
use DMS\Authorizators\DocumentBulkActionAuthorizator;
use DMS\Authorizators\MetadataAuthorizator;
use DMS\Components\DocumentLockComponent;
use DMS\Components\NotificationComponent;
use DMS\Components\ProcessComponent;
use DMS\Components\SharingComponent;
use DMS\Models\ArchiveModel;
use DMS\Models\DocumentCommentModel;
use DMS\Models\DocumentLockModel;
use DMS\Models\DocumentMetadataHistoryModel;
use DMS\Models\DocumentModel;
use DMS\Models\FileStorageModel;
use DMS\Models\FilterModel;
use DMS\Models\FolderModel;
use DMS\Models\GroupModel;
use DMS\Models\GroupRightModel;
use DMS\Models\GroupUserModel;
use DMS\Models\MailModel;
use DMS\Models\MetadataModel;
use DMS\Models\NotificationModel;
use DMS\Models\ProcessCommentModel;
use DMS\Models\ProcessModel;
use DMS\Models\RibbonModel;
use DMS\Models\ServiceModel;
use DMS\Models\TableModel;
use DMS\Models\UserModel;
use DMS\Models\UserRightModel;
use DMS\Models\WidgetModel;
use DMS\Repositories\DocumentCommentRepository;
use DMS\Repositories\DocumentRepository;

require_once('App/dms_loader.php');

$fm = new FileManager(AppConfiguration::getLogDir(), AppConfiguration::getCacheDir());
$logger = new Logger($fm);
$logger->setType('service');
$db = new Database(AppConfiguration::getDbServer(), AppConfiguration::getDbUser(), AppConfiguration::getDbPass(), AppConfiguration::getDbName(), $logger);

$userModel = new UserModel($db, $logger);
$userRightModel = new UserRightModel($db, $logger);
$documentModel = new DocumentModel($db, $logger);
$groupModel = new GroupModel($db, $logger);
$groupUserModel = new GroupUserModel($db, $logger, $groupModel);
$processModel = new ProcessModel($db, $logger);
$groupRightModel = new GroupRightModel($db, $logger);
$metadataModel = new MetadataModel($db, $logger);
$tableModel = new TableModel($db, $logger);
$folderModel = new FolderModel($db, $logger);
$serviceModel = new ServiceModel($db, $logger);
$documentCommentModel = new DocumentCommentModel($db, $logger);
$processCommentModel = new ProcessCommentModel($db, $logger);
$widgetModel = new WidgetModel($db, $logger);
$notificationModel = new NotificationModel($db, $logger);
$mailModel = new MailModel($db, $logger);
$filterModel = new FilterModel($db, $logger);
$ribbonModel = new RibbonModel($db, $logger);
$archiveModel = new ArchiveModel($db, $logger);
$fileStorageModel = new FileStorageModel($db, $logger);
$documentLockModel = new DocumentLockModel($db, $logger);
$documentMetadataHistoryModel = new DocumentMetadataHistoryModel($db, $logger);

$models = array(
    'userModel' => $userModel,
    'userRightModel' => $userRightModel,
    'documentModel' => $documentModel,
    'groupModel' => $groupModel,
    'groupUserModel' => $groupUserModel,
    'processModel' => $processModel,
    'groupRightModel' => $groupRightModel,
    'metadataModel' => $metadataModel,
    'tableModel' => $tableModel,
    'folderModel' => $folderModel,
    'serviceModel' => $serviceModel,
    'documentCommentModel' => $documentCommentModel,
    'processCommentModel' => $processCommentModel,
    'widgetModel' => $widgetModel,
    'notificationModel' => $notificationModel,
    'mailModel' => $mailModel,
    'ribbonModel' => $ribbonModel,
    'filterModel' => $filterModel,
    'archiveModel' => $archiveModel,
    'fileStorageModel' => $fileStorageModel,
    'documentLockModel' => $documentLockModel,
    'documentMetadataHistoryModel' => $documentMetadataHistoryModel
);

$documentLockComponent = new DocumentLockComponent($db, $logger, $documentLockModel, $userModel);

$bulkActionAuthorizator = new BulkActionAuthorizator($db, $logger, $userRightModel, $groupUserModel, $groupRightModel, $user);
$actionAuthorizator = new ActionAuthorizator($db, $logger, $userRightModel, $groupUserModel, $groupRightModel, $user);
$metadataAuthorizator = new MetadataAuthorizator($db, $logger, $user, $userModel, $groupUserModel);

$notificationComponent = new NotificationComponent($db, $logger, $notificationModel);
$processComponent = new ProcessComponent($db, $logger, $models, $notificationComponent, $documentLockComponent);
$sharingComponent = new SharingComponent($db, $logger, $documentModel);

$archiveAuthorizator = new ArchiveAuthorizator($db, $logger, $archiveModel, $user, $processComponent);
$documentAuthorizator = new DocumentAuthorizator($db, $logger, $documentModel, $userModel, $processModel, $user, $processComponent, $documentLockComponent);
$documentBulkActionAuthorizator = new DocumentBulkActionAuthorizator($db, $logger, $user, $documentAuthorizator, $bulkActionAuthorizator);

$documentCommentRepository = new DocumentCommentRepository($db, $logger, $documentCommentModel, $documentModel);
$documentRepository = new DocumentRepository($db, $logger, $documentModel, $documentAuthorizator, $documentCommentModel);

function start(string $name) {
    global $serviceModel, $logger;

    $service = $serviceModel->getServiceByName($name);
    $serviceModel->updateService($service->getId(), ['status' => '1', 'pid' => getmypid()]);
    $logger->info('Service ' . $name . ' start...');
}

function stop(string $name) {
    global $serviceModel, $logger;
    
    $service = $serviceModel->getServiceByName($name);
    $serviceModel->updateService($service->getId(), ['status' => '0', 'pid' => NULL]);
    $serviceModel->insertServiceLog(['name' => SERVICE_NAME, 'text' => 'Service ' . SERVICE_NAME . ' finished running.']);
    $logger->info('Service ' . $name . ' stop...');
}

?>