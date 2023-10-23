<?php

namespace DMS\Core;

class CacheManager {
    private FileManager $fm;
    private bool $serialize;

    public function __construct(bool $serialize) {
        $this->fm = new FileManager('logs/', 'cache/');

        $this->serialize = $serialize;
    }

    public function saveToCache(array $data) {
        $file = $this->createFilename();

        if($this->serialize) {
            $cacheData = unserialize($this->fm->readCache($file));    
        } else {
            $cacheData = $this->fm->readCache($file, !$this->serialize);
        }

        foreach($data as $key => $value) {
            $cacheData[$key] = $value;
        }

        if($this->serialize) {
            $cacheData = serialize($cacheData);
        }

        $this->fm->writeCache($file, $cacheData);
    }

    public function loadFromCache(string $key) {
        $file = $this->createFilename();

        if($this->serialize) {
            $data = unserialize($this->fm->readCache($file));    
        } else {
            $data = $this->fm->readCache($file, !$this->serialize);
        }

        if($data === FALSE) {
            return null;
        }

        if(array_key_exists($key, $data)) {
            return $data[$key];
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

    public static function getTemporaryObject() {
        global $app;

        return new self($app->cfg['serialize_cache']);
    }

    private function createFilename() {
        global $app;

        $name = $app->user->getId() . date('Y-m-d');

        $file = md5($name) . '.tmp';

        return $file;
    }
}

?>