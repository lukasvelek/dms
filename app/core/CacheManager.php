<?php

namespace DMS\Core;

class CacheManager {
    /**
     * @var FileManager
     */
    private $fm;

    public function __construct() {
        $this->fm = new FileManager('logs/', 'cache/');
    }

    public function saveToCache(array $data) {
        $file = $this->createFilename();

        $cacheData = $this->fm->readCache($file);

        $cacheData = unserialize($cacheData);

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

    public static function getTemporaryObject() {
        return new self();
    }

    private function createFilename() {
        global $app;

        $name = $app->user->getId() . date('Y-m-d');

        $file = md5($name);

        return $file;
    }
}

?>