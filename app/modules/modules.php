<?php

$modules = [];

$moduleDir = 'app/modules/';

$moduleDirFiles = scandir($moduleDir);

unset($moduleDirFiles[0]);
unset($moduleDirFiles[1]);

foreach($moduleDirFiles as $moduleDirFile) {
    if(is_dir($moduleDir . $moduleDirFile)) {
        $modules[$moduleDirFile] = [];
    }
}

foreach($modules as $module => $data) {
    $presenterDir = 'app/modules/' . $module . '/presenters/';

    $presenterFiles = scandir($presenterDir);

    unset($presenterFiles[0]);
    unset($presenterFiles[1]);

    foreach($presenterFiles as $presenterFile) {
        if(!is_dir($presenterDir . $presenterFile)) {
            $presenter = explode('.', $presenterFile)[0];

            $modules[$module][] = $presenter;
        }
    }
}

?>