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
 * @version 1.2
 */

use DMS\Exceptions\ClassDoesNotImplementInterfaceException;
use DMS\Exceptions\SystemFileDoesNotExistException;
use DMS\Modules\IModule;

$dependencies = array();

/**
 * Creates a list of dependencies with their paths in a given directory
 * 
 * @param array $dependencies Array of dependencies
 * @param string $dir Directory to search in
 */
function loadDependencies(array &$dependencies, string $dir) {
    $content = scandir($dir);

    unset($content[0]);
    unset($content[1]);

    $skip = array(
        $dir . '\\dms_loader.php',
        $dir . '\\install',
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
        'svg',
        'sql'
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

/**
 * Sorts dependencies based on their type:
 *  1. Interfaces
 *  2. Abstract classes
 *  3. General classes
 * 
 * @param array $dependencies Array of dependencies
 */
function sortDependencies(array &$dependencies) {
    $interfaces = [];
    $classes = [];
    $abstractClasses = [];

    foreach($dependencies as $dependency) {
        $filenameArr = explode('\\', $dependency);
        $filename = $filenameArr[count($filenameArr) - 1];

        if($filename[0] == 'A' && ctype_upper($filename[1])) {
            $abstractClasses[] = $dependency;
        } else if($filename[0] == 'I' && ctype_upper($filename[1])) {
            if(getNestLevel($dependency) > 5) {
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
function getNestLevel(string $dependencyPath) {
    return count(explode('\\', $dependencyPath));
}

loadDependencies($dependencies, __DIR__);
sortDependencies($dependencies);

foreach($dependencies as $dependency) {
    require_once($dependency);
}

// VENDOR DEPENDENCIES

require_once('Core/Vendor/PHPMailer/OAuthTokenProvider.php');
require_once('Core/Vendor/PHPMailer/OAuth.php');
require_once('Core/Vendor/PHPMailer/DSNConfigurator.php');
require_once('Core/Vendor/PHPMailer/Exception.php');
require_once('Core/Vendor/PHPMailer/PHPMailer.php');
require_once('Core/Vendor/PHPMailer/POP3.php');
require_once('Core/Vendor/PHPMailer/SMTP.php');

// END OF VENDOR DENEPENDENCIES

if(!DMS\Core\FileManager::fileExists('config.local.php')) {
    throw new SystemFileDoesNotExistException('config.local.php');
}

if(!DMS\Core\FileManager::fileExists('app/Modules/modules.php')) {
    throw new SystemFileDoesNotExistException('app/Modules/modules.php');
}

include('Modules/modules.php');

$app = new DMS\Core\Application();

foreach($modules as $moduleName => $modulePresenters) {
    $moduleUrl = 'DMS\\Modules\\' . $moduleName . '\\' . $moduleName;

    $module = new $moduleUrl();

    if(!($module instanceof IModule)) {
        throw new ClassDoesNotImplementInterfaceException($moduleUrl, 'DMS\Modules\IModule');
    }
    
    foreach($modulePresenters as $modulePresenter) {
        $presenterUrl = 'DMS\\Modules\\' . $moduleName . '\\' . $modulePresenter;

        $presenter = new $presenterUrl();

        $module->registerPresenter($presenter);
    }

    $app->registerModule($module);
}

?>