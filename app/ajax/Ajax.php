<?php

use DMS\Authorizators\ActionAuthorizator;
use DMS\Authorizators\BulkActionAuthorizator;
use DMS\Authorizators\DocumentAuthorizator;
use DMS\Authorizators\DocumentBulkActionAuthorizator;
use DMS\Authorizators\MetadataAuthorizator;
use DMS\Authorizators\PanelAuthorizator;
use DMS\Components\ExternalEnumComponent;
use DMS\Components\NotificationComponent;
use DMS\Components\ProcessComponent;
use DMS\Components\SharingComponent;
use DMS\Components\WidgetComponent;
use DMS\Core\DB\Database;
use DMS\Core\FileManager;
use DMS\Core\FileStorageManager;
use DMS\Core\Logger\Logger;
use DMS\Core\MailManager;
use DMS\Models\DocumentCommentModel;
use DMS\Models\DocumentModel;
use DMS\Models\FolderModel;
use DMS\Models\GroupModel;
use DMS\Models\GroupRightModel;
use DMS\Models\GroupUserModel;
use DMS\Models\MailModel;
use DMS\Models\MetadataModel;
use DMS\Models\NotificationModel;
use DMS\Models\ProcessCommentModel;
use DMS\Models\ProcessModel;
use DMS\Models\ServiceModel;
use DMS\Models\TableModel;
use DMS\Models\UserModel;
use DMS\Models\UserRightModel;
use DMS\Models\WidgetModel;
use DMS\Repositories\DocumentCommentRepository;
use DMS\Repositories\DocumentRepository;

session_start();

$dependencies = array();

function loadDependencies2(array &$dependencies, string $dir) {
    $content = scandir($dir);

    unset($content[0]);
    unset($content[1]);

    $skip = array(
        $dir . '\\dependencies.php',
        $dir . '\\dms_loader.php',
        $dir . '\\install',
        $dir . '\\modules',
        $dir . '\\ajax',
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
            $interfaces[] = $dependency;
        } else {
            $classes[] = $dependency;
        }
    }

    $dependencies = array_merge($interfaces, $abstractClasses, $classes);
}

loadDependencies2($dependencies, '../');
sortDependencies2($dependencies);

foreach($dependencies as $dependency) {
    require_once($dependency);
}

// VENDOR DEPENDENCIES

require_once('../core/vendor/PHPMailer/OAuthTokenProvider.php');
require_once('../core/vendor/PHPMailer/OAuth.php');
require_once('../core/vendor/PHPMailer/DSNConfigurator.php');
require_once('../core/vendor/PHPMailer/Exception.php');
require_once('../core/vendor/PHPMailer/PHPMailer.php');
require_once('../core/vendor/PHPMailer/POP3.php');
require_once('../core/vendor/PHPMailer/SMTP.php');

// END OF VENDOR DENEPENDENCIES

if(!file_exists('../../config.local.php')) {
    die('Config file does not exist!');
}

include('../../config.local.php');

$user = null;

$fm = new FileManager('../../' . $cfg['log_dir'], '../../' . $cfg['cache_dir']);

$logger = new Logger($fm, $cfg);
$db = new Database($cfg['db_server'], $cfg['db_user'], $cfg['db_pass'], $cfg['db_name'], $logger);

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

if(isset($_SESSION['id_current_user'])) {
    $user = $userModel->getUserById($_SESSION['id_current_user']);
}

$panelAuthorizator = new PanelAuthorizator($db, $logger, $userRightModel, $groupUserModel, $groupRightModel, $user);
$bulkActionAuthorizator = new BulkActionAuthorizator($db, $logger, $userRightModel, $groupUserModel, $groupRightModel, $user);
$actionAuthorizator = new ActionAuthorizator($db, $logger, $userRightModel, $groupUserModel, $groupRightModel, $user);
$metadataAuthorizator = new MetadataAuthorizator($db, $logger, $user, $userModel, $groupUserModel);

$notificationComponent = new NotificationComponent($db, $logger, $notificationModel);
$processComponent = new ProcessComponent($db, $logger, $processModel, $groupModel, $groupUserModel, $documentModel, $notificationComponent, $processCommentModel);
$widgetComponent = new WidgetComponent($db, $logger, $documentModel, $processModel, $mailModel);
$sharingComponent = new SharingComponent($db, $logger, $documentModel);

$documentAuthorizator = new DocumentAuthorizator($db, $logger, $documentModel, $userModel, $processModel, $user, $processComponent);
$documentBulkActionAuthorizator = new DocumentBulkActionAuthorizator($db, $logger, $user, $documentAuthorizator, $bulkActionAuthorizator);

$documentCommentRepository = new DocumentCommentRepository($db, $logger, $documentCommentModel, $documentModel);
$documentRepository = new DocumentRepository($db, $logger, $documentModel, $documentAuthorizator);

$mailManager = new MailManager($cfg);

$externalEnumComponent = new ExternalEnumComponent($userModel);

$gridSize = $cfg['grid_size'];
$gridUseFastLoad = $cfg['grid_use_fast_load'];

?>