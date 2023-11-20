<?php

/**
 * The default DMS appplication loader.
 * 
 * It loads all dependencies that are sorted by importance:
 * 1. Interfaces
 * 2. Abstract classes
 * 3. Classes
 * 
 * After loading dependencies it create an instance of the Application.
 * 
 * It also checks for presence of 'config.local.php' config script.
 * It also loads all UI modules and registers them in the application.
 * 
 * @author Lukas Velek
 * @version 1.0
 */

$dependencies = array();

function loadDependencies(array &$dependencies, string $dir) {
    $content = scandir($dir);

    unset($content[0]);
    unset($content[1]);

    $skip = array(
        $dir . '\\dependencies.php',
        $dir . '\\dms_loader.php',
        $dir . '\\install',
        $dir . '\\ajax'
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

if(!DMS\Core\FileManager::fileExists('config.local.php')) {
    die('Config file does not exist!');
}

include('config.local.php');
include('modules/modules.php');

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