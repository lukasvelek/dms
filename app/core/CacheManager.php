<?php

namespace DMS\Core;

class CacheManager {
    private FileManager $fm;
    private bool $serialize;
    private string $special;

    public function __construct(bool $serialize, string $special = '') {
        $this->fm = new FileManager('logs/', 'cache/');

        $this->serialize = $serialize;
        $this->special = $special;
    }

    public function saveToCache(string $category, array $data) {
        $file = $this->createFilename();

        if($this->serialize) {
            $cacheData = unserialize($this->fm->readCache($file));    
        } else {
            $cacheData = $this->fm->readCache($file, !$this->serialize);
        }

        $cacheData[$category] = $data;

        if($this->serialize) {
            $cacheData = serialize($cacheData);
        }

        $this->fm->writeCache($file, $cacheData);
    }

    public function loadFromCache(string $category, string $key) {
        $file = $this->createFilename();

        if($this->serialize) {
            $data = unserialize($this->fm->readCache($file));    
        } else {
            $data = $this->fm->readCache($file, !$this->serialize);
        }

        if($data === FALSE) {
            return null;
        }

        if(array_key_exists($category, $data)) {
            $cacheData = $data[$category];

            if(array_key_exists($key, $cacheData)) {
                return $cacheData[$key];
            }
        } else {
            return null;
        }
    }

    public function invalidateCache() {
        $file = $this->createFilename();
        
        return $this->fm->invalidateCache($file);
    }

    public function loadCache() {
        $file = $this->createFilename();

        $data = unserialize($this->fm->readCache($file));

        return $data;
    }

    public static function getTemporaryObject(string $special = '') {
        global $app;

        return new self($app->cfg['serialize_cache'], $special);
    }

    public function createFilename() {
        global $app;

        $name = $app->user->getId() . date('Y-m-d');

        if($this->special != '') {
            $name .= $this->special;
        }

        $file = md5($name) . '.tmp';

        return $file;
    }
}

?>