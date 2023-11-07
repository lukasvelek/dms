<?php

namespace DMS\Core;

class CacheManager {
    private FileManager $fm;
    private bool $serialize;
    private string $category;
    
    public function __construct(bool $serialize, string $category) {
        $this->fm = new FileManager('logs/', 'cache/');

        $this->serialize = $serialize;
        $this->category = $category;
    }

    public function saveBulkActionRight(int $idUser, string $key, int $value) {
        $cacheData = $this->loadFromCache();

        $cacheData[$idUser][$key] = $value;

        $this->saveToCache($cacheData);
    }

    public function loadBulkActionRight(int $idUser, string $key) {
        $cacheData = $this->loadFromCache();

        if($cacheData === FALSE) {
            return null;
        }

        if(!array_key_exists($idUser, $cacheData)) {
            return null;
        }

        foreach($cacheData as $idUser => $keys) {
            if(!array_key_exists($key, $keys)) {
                return null;
            } else {
                return $keys[$key] ? true : false;
            }
        }
    }

    public function savePanelRight(int $idUser, string $key, int $value) {
        $cacheData = $this->loadFromCache();

        $cacheData[$idUser][$key] = $value;

        $this->saveToCache($cacheData);
    }

    public function loadPanelRight(int $idUser, string $key) {
        $cacheData = $this->loadFromCache();

        if($cacheData === FALSE) {
            return null;
        }

        if(!array_key_exists($idUser, $cacheData)) {
            return null;
        }

        foreach($cacheData as $idUser => $keys) {
            if(!array_key_exists($key, $keys)) {
                return null;
            } else {
                return $keys[$key] ? true : false;
            }
        }
    }

    public function saveMetadataRight(int $idUser, int $idMetadata, string $key, int $value) {
        $cacheData = $this->loadFromCache();

        $cacheData[$idUser][$idMetadata][$key] = $value;

        if($cacheData != null) {
            $this->saveToCache($cacheData);
        }
    }

    public function loadMetadataRight(int $idUser, int $idMetadata, string $key) {
        $cacheData = $this->loadFromCache();

        if($cacheData === FALSE) {
            return null;
        }

        if(!array_key_exists($idUser, $cacheData)) {
            return null;
        }

        foreach($cacheData as $idUser => $metadata) {
            if(!array_key_exists($idMetadata, $metadata)) {
                return null;
            }

            foreach($metadata as $idMetadata => $keys) {
                if(!array_key_exists($key, $keys)) {
                    return null;
                } else {
                    return $keys[$key] ? true : false;
                }
            }
        }
    }

    public function createFilename() {
        global $app;

        $name = $app->user->getId() . date('Y-m-d') . $this->category;

        $file = md5($name) . '.tmp';

        return $file;
    }

    private function loadFromCache() {
        $filename = $this->createFilename();

        $data = $this->fm->readCache($filename);

        if($data === FALSE) {
            return false;
        }

        if($this->serialize) {
            $data = unserialize($data);
        }

        return $data;
    }

    private function saveToCache(array $data) {
        $filename = $this->createFilename();

        if($this->serialize) {
            $data = serialize($data);
        }

        $this->fm->writeCache($filename, $data);
    }

    public static function getTemporaryObject(string $category) {
        global $app;

        return new self($app->cfg['serialize_cache'], $category);
    }
}

?>