<?php

namespace App\Core\Managers;

use App\Core\VarTypes\DateTime;

class CacheManager {
    public const NS_USERS = 'users';

    private FileManager $fileManager;

    public function __construct(FileManager $fileManager) {
        $this->fileManager = $fileManager;
    }

    public function loadCache(mixed $key, callable $callback, string $namespace = 'default', ?string $method = null) {
        $path = $this->createCacheFilePath($namespace);

        $cacheData = $this->loadCacheFile($path);
        
        $result = null;

        if($cacheData === null) {
            // no cache exists
            
            $result = $callback();
            $cacheData[$key] = $result;

            $this->saveCacheFile($path, serialize($cacheData));
        } else {
            // cache exists
            $cacheData = unserialize($cacheData);

            if(!array_key_exists($key, $cacheData)) {
                $result = $callback();
                $cacheData[$key] = $result;

                $this->saveCacheFile($path, serialize($cacheData));
            } else {
                $result = $cacheData[$key];
            }
        }

        return $result;
    }

    private function saveCacheFile(string $path, string $content) {
        return $this->fileManager->saveFile($path, $content, true);
    }

    private function loadCacheFile(string $path) {
        $result = $this->fileManager->loadFile($path);

        if($result === false) {
            $result = null;
        }

        return $result;
    }

    private function createCacheFilePath(string $namespace) {
        $now = new DateTime();
        $now->format('Y-m-d');

        $hash = HashManager::hashString($now->getResult() . $namespace);

        $path = $this->fileManager->cfg['APP_REAL_DIR'] . $this->fileManager->cfg['CACHE_DIR'];
        $namespaceDir = $path . $namespace . '\\';
        $path .= $namespace . '\\' . $hash . '.tmp';

        if(!$this->fileManager->dirExists($namespaceDir)) {
            $this->fileManager->createDir($namespaceDir);
        }

        return $path;
    }
}

?>