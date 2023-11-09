<?php

$dependencies = array();

function loadDependencies(array &$dependencies, string $dir) {
    $content = scandir($dir);

    unset($content[0]);
    unset($content[1]);

    $skip = array(
        $dir . '\\dependencies.php',
        $dir . '\\dms_loader.php',
        $dir . '\\install',
        $dir . '\\dependencies.txt',
        $dir . '\\Ajax.php',
        $dir . '\\search.php',
        $dir . '\\send-comment.php',
        $dir . '\\get-comments.php',
        $dir . '\\delete-comment.php'
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

                loadDependencies($dependencies, $c);
            }
        }
    }
}

function sortDependencies(array &$dependencies) {
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

loadDependencies($dependencies, __DIR__);
sortDependencies($dependencies);

foreach($dependencies as $dependency) {
    require_once($dependency);
}

$fm = DMS\Core\FileManager::getTemporaryObject();

if(!$fm->fileExists('config.local.php')) {
    die('Config file does not exist!');
}

include('config.local.php');
include('modules/modules.php');

unset($fm);

$app = new DMS\Core\Application($cfg);

foreach($modules as $moduleName => $modulePresenters) {
    $moduleUrl = 'DMS\\Modules\\' . $moduleName . '\\' . $moduleName;

    $module = new $moduleUrl();
    
    foreach($modulePresenters as $modulePresenter) {
        $presenterUrl = 'DMS\\Modules\\' . $moduleName . '\\' . $modulePresenter;

        $presenter = new $presenterUrl();

        $module->registerPresenter($presenter);
    }

    $app->registerModule($module);
}

?>