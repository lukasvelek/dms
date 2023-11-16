<?php

use DMS\Authorizators\ActionAuthorizator;
use DMS\Authorizators\BulkActionAuthorizator;
use DMS\Authorizators\DocumentAuthorizator;
use DMS\Authorizators\DocumentBulkActionAuthorizator;
use DMS\Authorizators\MetadataAuthorizator;
use DMS\Authorizators\PanelAuthorizator;
use DMS\Components\ProcessComponent;
use DMS\Core\DB\Database;
use DMS\Core\FileManager;
use DMS\Core\Logger\Logger;
use DMS\Entities\ProcessComment;
use DMS\Models\DocumentCommentModel;
use DMS\Models\DocumentModel;
use DMS\Models\FolderModel;
use DMS\Models\GroupModel;
use DMS\Models\GroupRightModel;
use DMS\Models\GroupUserModel;
use DMS\Models\MetadataModel;
use DMS\Models\ProcessCommentModel;
use DMS\Models\ProcessModel;
use DMS\Models\ServiceModel;
use DMS\Models\TableModel;
use DMS\Models\UserModel;
use DMS\Models\UserRightModel;

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
        $dir . '\\ajax'
    );

    foreach($content as $c) {
        /* SKIP TEMPLATES (html files) */
        $filenameParts = explode('.', $c);

        if($filenameParts[count($filenameParts) - 1] == 'html') continue;

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

if(!file_exists('../../config.local.php')) {
    die('Config file does not exist!');
}

include('../../config.local.php');

//$app = new Application($cfg, '../', false);

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

if(isset($_SESSION['id_current_user'])) {
    $user = $userModel->getUserById($_SESSION['id_current_user']);
}

$processComponent = new ProcessComponent($db, $logger, $processModel, $groupModel, $groupUserModel, $documentModel);

$panelAuthorizator = new PanelAuthorizator($db, $logger, $userRightModel, $groupUserModel, $groupRightModel, $user);
$bulkActionAuthorizator = new BulkActionAuthorizator($db, $logger, $userRightModel, $groupUserModel, $groupRightModel, $user);
$documentAuthorizator = new DocumentAuthorizator($db, $logger, $documentModel, $userModel, $processModel, $user, $processComponent);
$actionAuthorizator = new ActionAuthorizator($db, $logger, $userRightModel, $groupUserModel, $groupRightModel, $user);
$metadataAuthorizator = new MetadataAuthorizator($db, $logger, $user, $userModel, $groupUserModel);
$documentBulkActionAuthorizator = new DocumentBulkActionAuthorizator($db, $logger, $user, $documentAuthorizator, $bulkActionAuthorizator);


?>