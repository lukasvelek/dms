<?php

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
use DMS\Constants\CacheCategories;
use DMS\Core\AppConfiguration;
use DMS\Core\CacheManager;
use DMS\Core\DB\Database;
use DMS\Core\FileManager;
use DMS\Core\Logger\Logger;
use DMS\Core\MailManager;
use DMS\Models\ArchiveModel;
use DMS\Models\DocumentCommentModel;
use DMS\Models\DocumentLockModel;
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

session_start();

$dependencies = array();

/**
 * Creates a list of dependencies with their paths in a given directory
 * 
 * @param array $dependencies Array of dependencies
 * @param string $dir Directory to search in
 */
function loadDependencies2(array &$dependencies, string $dir) {
    $content = scandir($dir);

    unset($content[0]);
    unset($content[1]);

    $skip = array(
        $dir . '\\dms_loader.php',
        $dir . '\\install',
        $dir . '\\Modules',
        $dir . '\\Ajax',
        $dir . '\\PHPMailer'
    );

    $extensionsToSkip = array(
        'html',
        'md',
        'js',
        'png',
        'gif',
        'jpg',
        'svg'
    );

    foreach($content as $c) {
        /* SKIP TEMPLATES (html files) */
        $filenameParts = explode('.', $c);

        /* SKIP CERTAIN EXTENSIONS */
        if(in_array($filenameParts[count($filenameParts) - 1], $extensionsToSkip)) {
            continue;
        }

        $c = $dir . '\\' . $c;

        if(!in_array($c, $skip)) {
            if(!is_dir($c)) {
                // je soubor

                $dependencies[] = $c;
            } else {
                // je slozka

                loadDependencies2($dependencies, $c);
            }
        }
    }
}

/**
 * Sorts dependencies based on their type:
 *  1. Interfaces
 *  2. Abstract classes
 *  3. General classes
 * 
 * @param array $dependencies Array of dependencies
 */
function sortDependencies2(array &$dependencies) {
    $interfaces = [];
    $classes = [];
    $abstractClasses = [];

    foreach($dependencies as $dependency) {
        $filenameArr = explode('\\', $dependency);
        $filename = $filenameArr[count($filenameArr) - 1];

        if($filename[0] == 'A') {
            $abstractClasses[] = $dependency;
        } else if($filename[0] == 'I') {
            if(getNestLevel2($dependency) > 5) {
                $interfaces[] = $dependency;
            } else {
                $interfaces = array_merge([$dependency], $interfaces);
            }
        } else {
            $classes[] = $dependency;
        }
    }

    $dependencies = array_merge($interfaces, $abstractClasses, $classes);
}

/**
 * Returns the nest level of the dependency
 * 
 * @param string $dependecyPath Dependency path
 * @return int Nest level
 */
function getNestLevel2(string $dependencyPath) {
    return count(explode('\\', $dependencyPath));
}

loadDependencies2($dependencies, '..\\');
sortDependencies2($dependencies);

foreach($dependencies as $dependency) {
    require_once($dependency);
}

// VENDOR DEPENDENCIES

require_once('../Core/Vendor/PHPMailer/OAuthTokenProvider.php');
require_once('../Core/Vendor/PHPMailer/OAuth.php');
require_once('../Core/Vendor/PHPMailer/DSNConfigurator.php');
require_once('../Core/Vendor/PHPMailer/Exception.php');
require_once('../Core/Vendor/PHPMailer/PHPMailer.php');
require_once('../Core/Vendor/PHPMailer/POP3.php');
require_once('../Core/Vendor/PHPMailer/SMTP.php');

// END OF VENDOR DENEPENDENCIES

if(!file_exists('../../config.local.php')) {
    die('Config file does not exist!');
}

$user = null;

$fm = new FileManager('../../' . AppConfiguration::getLogDir(), '../../' . AppConfiguration::getCacheDir());

$logger = new Logger($fm);
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
    'documentLockModel' => $documentLockModel
);

if(isset($_SESSION['id_current_user'])) {
    $ucm = CacheManager::getTemporaryObject(CacheCategories::USERS, true);

    $valFromCache = $ucm->loadUserByIdFromCache($_SESSION['id_current_user']);

    if($valFromCache === NULL) {
        $user = $userModel->getUserById($_SESSION['id_current_user']);
        $ucm->saveUserToCache($user);
    } else {
        $user = $valFromCache;
    }

}

$bulkActionAuthorizator = new BulkActionAuthorizator($db, $logger, $userRightModel, $groupUserModel, $groupRightModel, $user);
$actionAuthorizator = new ActionAuthorizator($db, $logger, $userRightModel, $groupUserModel, $groupRightModel, $user);
$metadataAuthorizator = new MetadataAuthorizator($db, $logger, $user, $userModel, $groupUserModel);

$notificationComponent = new NotificationComponent($db, $logger, $notificationModel);
$processComponent = new ProcessComponent($db, $logger, $models, $notificationComponent);
$sharingComponent = new SharingComponent($db, $logger, $documentModel);
$documentLockComponent = new DocumentLockComponent($db, $logger, $documentLockModel);

$archiveAuthorizator = new ArchiveAuthorizator($db, $logger, $archiveModel, $user, $processComponent);
$documentAuthorizator = new DocumentAuthorizator($db, $logger, $documentModel, $userModel, $processModel, $user, $processComponent);
$documentBulkActionAuthorizator = new DocumentBulkActionAuthorizator($db, $logger, $user, $documentAuthorizator, $bulkActionAuthorizator);

$documentCommentRepository = new DocumentCommentRepository($db, $logger, $documentCommentModel, $documentModel);
$documentRepository = new DocumentRepository($db, $logger, $documentModel, $documentAuthorizator);

$mailManager = new MailManager();

$gridSize = AppConfiguration::getGridSize();

?>