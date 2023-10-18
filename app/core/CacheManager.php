<?php

namespace DMS\Core;

class CacheManager {
    private FileManager $fm;

    public function __construct() {
        $this->fm = new FileManager('logs/', 'cache/');
    }

    public function saveToCache(array $data) {
        $file = $this->createFilename();

        $cacheData = unserialize($this->fm->readCache($file));

        foreach($data as $key => $value) {
            $cacheData[$key] = $value;
        }

        $cacheData = serialize($cacheData);

        $this->fm->writeCache($file, $cacheData);
    }

    public function loadFromCache(string $key) {
        $file = $this->createFilename();

        $data = unserialize($this->fm->readCache($file));

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
        return new self();
    }

    private function createFilename() {
        global $app;

        $name = $app->user->getId() . date('Y-m-d');

        $file = md5($name) . '.tmp';

        return $file;
    }
}

?>