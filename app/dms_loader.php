<?php

$app = null;

// dependency loader
include('app/dependencies.php');

require_once('app/core/FileManager.php');

$fm = DMS\Core\FileManager::getTemporaryObject();

$toBeLoaded = array();

foreach($dependencies as $dependencyName => $dependencyValues) {
    $filename = '';
    $dependencyDependencies = array();

    foreach($dependencyValues as $dependencyValueKey => $dependencyValueValue) {
        if($dependencyValueKey == 'path') {
            $filename = $dependencyValueValue;
        } else if($dependencyValueKey == 'dependency') {
            $dependencyDependencies = $dependencyValueValue;
        }
    }

    if(!$fm->fileExists($filename)) {
        die('File \'' . $filename . '\' does not exist!');
    }

    require_once($filename);
}

?>