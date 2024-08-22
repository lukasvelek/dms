<?php

function getFilesInFolderRecursively(string $folder, array &$files, array $skipFolders = [], array $skipFiles = [], array $skipExtensions = []) {
    $objects = scandir($folder);

    unset($objects[0], $objects[1]);

    foreach($objects as $object) {
        $realObject = $folder . '\\' . $object;

        if(!is_dir($realObject)) {
            if(in_array($object, $skipFiles)) {
                continue;
            }

            if(str_contains($object, '.')) {
                $filenameParts = explode('.', $object);
                $extension = $filenameParts[count($filenameParts) - 1];

                if(in_array($extension, $skipExtensions)) {
                    continue;
                } else {
                    $files[$realObject] = $object;
                }
            }
        } else {
            if(!in_array($object . '\\', $skipFolders)) {
                getFilesInFolderRecursively($folder . '\\' . $object, $files, $skipFolders, $skipFiles, $skipExtensions);
            }
        }
    }
}

function sortFilesByPriority(array &$files) {
    $interfaces = [];
    $abstractClasses = [];
    $classes = [];

    foreach($files as $realPath => $file) {
        if(str_starts_with($file, 'I')) {
            if(ctype_upper($file[1])) {
                $interfaces[$realPath] = $file;
            } else {
                $classes[$realPath] = $file;
            }
        } else if(str_starts_with($file, 'A')) {
            if(ctype_upper($file[1])) {
                $abstractClasses[$realPath] = $file;
            } else {
                $classes[$realPath] = $file;
            }
        } else {
            $classes[$realPath] = $file;
        }
    }

    $files = array_merge($interfaces, $abstractClasses, $classes);
}

function createContainer(array $cfg, array $files, array $modules) {
    $data = serialize(['files' => $files, 'modules' => $modules, 'created_on' => date('Y-m-d H:i:s')]);

    file_put_contents($cfg['CACHE_DIR'] . '\\Container_' . md5(date('Y-m-d')) . '.tmp', $data);
}

function getContainer(array $cfg, array &$files, array &$modules) {
    if(file_exists($cfg['CACHE_DIR'] . '\\Container_' . md5(date('Y-m-d')) . '.tmp')) {
        $c = unserialize(file_get_contents($cfg['CACHE_DIR'] . '\\Container_' . md5(date('Y-m-d')) . '.tmp'))['files'];
    } else {
        $c = [];
    }

    $files = $c['files'];
    $modules = $c['modules'];
}

function requireFiles(array $files) {
    foreach($files as $realPath => $file) {
        require_once($realPath);
    }
}

function getModules(array $cfg, array &$modules) {
    $folders = scandir($cfg['APP_REAL_DIR'] . 'app\\ui\\');

    unset($folders[0], $folders[1]);

    foreach($folders as $folder) {
        $realPath = $cfg['APP_REAL_DIR'] . '\\app\\ui\\' . $folder;

        if(is_dir($realPath)) {
            $modules[] = $folder;
        }
    }
}

global $cfg;

$files = [];
$modules = [];

if($cfg['CORE_USE_CONTAINER']) {
    getContainer($cfg, $files, $modules);
}

if(empty($files)) {
    getFilesInFolderRecursively(__DIR__, $files, [], ['app_loader.php'], ['html', 'distrib', 'bak']);
    sortFilesByPriority($files);
}

requireFiles($files);

if(empty($modules)) {
    getModules($cfg, $modules);
}

if($cfg['CORE_USE_CONTAINER']) {
    createContainer($cfg, $files, $modules);
}

?>