<?php

namespace App\Core;

class AppLoader {
    private array $files;
    private array $modules;
    private array $cfg;
    private bool $useContainer;

    private function __construct(array $cfg) {
        $this->cfg = $cfg;
        $this->useContainer = $this->cfg['CORE_USE_CONTAINER'];;

        $this->files = [];
        $this->modules = [];
    }

    public static function loadApplication(array $cfg) {
        $obj = new self($cfg);

        if($obj->useContainer) {
            $obj->getContainer();
        }

        if(empty($obj->files)) {
            $obj->getFilesInFolderRecursively(__DIR__, [], ['AppLoader.php'], ['html', 'distrib', 'bak']);
            $obj->sortFilesByPriority();
            if($obj->useContainer) {
                $obj->createContainer();
            }
        }

        $obj->requireFiles();

        if(empty($obj->modules)) {
            $obj->getModules();
        }
    }

    private function getModules() {
        $folders = scandir($this->cfg['APP_REAL_DIR'] . 'app\\ui\\');

        unset($folders[0], $folders[1]);

        foreach($folders as $folder) {
            $realPath = $this->cfg['APP_REAL_DIR'] . '\\app\\ui\\' . $folder;

            if(is_dir($realPath)) {
                $this->modules[] = $folder;
            }
        }
    }

    private function getFilesInFolderRecursively(string $folder, array $skipFolders = [], array $skipFiles = [], array $skipExtensions = []) {
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
                        $this->files[$realObject] = $object;
                    }
                }
            } else {
                if(!in_array($object . '\\', $skipFolders)) {
                    $this->getFilesInFolderRecursively($folder . '\\' . $object, $this->files, $skipFolders, $skipFiles, $skipExtensions);
                }
            }
        }
    }

    private function sortFilesByPriority() {
        $interfaces = [];
        $abstractClasses = [];
        $classes = [];

        foreach($this->files as $realPath => $file) {
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

        $this->files = array_merge($interfaces, $abstractClasses, $classes);
    }

    private function createContainer() {
        $data = serialize(['files' => $this->files, 'modules' => $this->modules, 'created_on' => date('Y-m-d H:i:s')]);

        file_put_contents($this->cfg['CACHE_DIR'] . '\\Container_' . md5(date('Y-m-d')) . '.tmp', $data);
    }

    private function getContainer() {
        if(file_exists($this->cfg['CACHE_DIR'] . '\\Container_' . md5(date('Y-m-d')) . '.tmp')) {
            $c = unserialize(file_get_contents($this->cfg['CACHE_DIR'] . '\\Container_' . md5(date('Y-m-d')) . '.tmp'))['files'];
        } else {
            $c = [];
        }

        $this->files = $c;
    }

    private function requireFiles() {
        foreach($this->files as $realPath => $file) {
            require_once($realPath);
        }
    }
}

?>