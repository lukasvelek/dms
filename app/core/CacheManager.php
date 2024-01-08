<?php

namespace DMS\Core;

use DMS\Constants\CacheCategories;
use DMS\Entities\Folder;
use DMS\Entities\User;

/**
 * CacheManager allows the application to cache data
 * 
 * @author Lukas Velek
 */
class CacheManager {
    private FileManager $fm;
    private bool $serialize;
    private string $category;
    
    /**
     * The CacheManager constructor
     * 
     * @param bool $serialize True if cache should be serialized and false if not
     * @param string $category Cache category
     */
    public function __construct(bool $serialize, string $category, string $logdir = 'logs/', string $cachedir = 'cache/') {
        $this->fm = new FileManager($logdir, $cachedir);

        $this->serialize = $serialize;
        $this->category = $category;
    }

    public function saveArrayToCache(array $array) {
        $cacheData = $this->loadFromCache();

        $cacheData[$this->category] = $array;

        $this->saveToCache($cacheData);
    }

    public function saveStringToCache(string $text) {
        $cacheData = $this->loadFromCache();

        $cacheData[$this->category][] = $text;

        $this->saveToCache($cacheData);
    }

    public function loadStringsFromCache() {
        $cacheData = $this->loadFromCache();

        if($cacheData === FALSE) {
            return null;
        }

        return $cacheData[$this->category];
    }

    public function saveFolderToCache(Folder $folder) {
        $cacheData = $this->loadFromCache();

        $cacheData[$this->category][$folder->getId()] = $folder;

        $this->saveToCache($cacheData);
    }

    public function loadFolderByIdFromCache(int $id) {
        $cacheData = $this->loadFromCache();

        if($cacheData === FALSE) {
            return null;
        }

        if(array_key_exists($id, $cacheData[$this->category])) {
            return $cacheData[$this->category][$id];
        } else {
            return null;
        }
    }

    public function saveUserToCache(User $user) {
        $cacheData = $this->loadFromCache();

        $cacheData[$this->category][$user->getId()] = $user;

        $this->saveToCache($cacheData);
    }

    public function loadUserByIdFromCache(int $id) {
        $cacheData = $this->loadFromCache();

        if($cacheData === FALSE) {
            return null;
        }

        if(array_key_exists($id, $cacheData[$this->category])) {
            return $cacheData[$this->category][$id];
        } else {
            return null;
        }
    }

    /**
     * Saves a service config to cache
     * 
     * @param string $serviceName Service name
     * @param array $data Data
     */
    public function saveServiceConfig(string $serviceName, array $data) {
        $cacheData = $this->loadFromCache();

        foreach($data as $k => $v) {
            $cacheData[$serviceName][$k] = $v;
        }

        $this->saveToCache($cacheData);
    }

    /**
     * Loads the action right from cache
     * 
     * @param int $idUser ID user
     * @param string $key Action name
     * @return mixed|null True if action right is allowed, false if it is not allowed, null if the entry does not exist
     */
    public function loadServiceConfigForService(string $serviceName) {
        $cacheData = $this->loadFromCache();

        if($cacheData === FALSE) {
            return null;
        }

        if(!array_key_exists($serviceName, $cacheData)) {
            return null;
        }

        if(array_key_exists($serviceName, $cacheData)) {
            return $cacheData[$serviceName];
        } else {
            return null;
        }
    }

    /**
     * Invalidates cache of some category by deleting the file.
     */
    public function invalidateCache() {
        $filename = $this->createFilename();

        $this->fm->deleteFile($filename);
    }

    /**
     * Saves a action right to cache
     * 
     * @param int $idUser ID user
     * @param string $key Action name
     * @param int $value 1 if action right is allowed and 0 if not
     */
    public function saveActionRight(int $idUser, string $key, int $value) {
        $cacheData = $this->loadFromCache();

        $cacheData[$idUser][$key] = $value;

        $this->saveToCache($cacheData);
    }

    /**
     * Loads the action right from cache
     * 
     * @param int $idUser ID user
     * @param string $key Action name
     * @return bool|null True if action right is allowed, false if it is not allowed, null if the entry does not exist
     */
    public function loadActionRight(int $idUser, string $key) {
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

    /**
     * Saves a bulk action right to cache
     * 
     * @param int $idUser ID user
     * @param string $key Bulk action name
     * @param int $value 1 if bulk action right is allowed and 0 if not
     */
    public function saveBulkActionRight(int $idUser, string $key, int $value) {
        $cacheData = $this->loadFromCache();

        $cacheData[$idUser][$key] = $value;

        $this->saveToCache($cacheData);
    }

    /**
     * Loads the bulk action right from cache
     * 
     * @param int $idUser ID user
     * @param string $key Bulk action name
     * @return bool|null True if bulk action right is allowed, false if it is not allowed, null if the entry does not exist
     */
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

    /**
     * Saves a panel right to cache
     * 
     * @param int $idUser ID user
     * @param string $key Panel name
     * @param int $value 1 if panel right is allowed and 0 if not
     */
    public function savePanelRight(int $idUser, string $key, int $value) {
        $cacheData = $this->loadFromCache();

        $cacheData[$idUser][$key] = $value;

        $this->saveToCache($cacheData);
    }

    /**
     * Loads the panel right from cache
     * 
     * @param int $idUser ID user
     * @param string $key Panel name
     * @return bool|null True if panel right is allowed, false if it is not allowed, null if the entry does not exist
     */
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

    /**
     * Saves a metadata right to cache
     * 
     * @param int $idUser ID user
     * @param string $key Metadata name
     * @param int $value 1 if metadata right is allowed and 0 if not
     */
    public function saveMetadataRight(int $idUser, int $idMetadata, string $key, int $value) {
        $cacheData = $this->loadFromCache();

        $cacheData[$idUser][$idMetadata][$key] = $value;

        if($cacheData != null) {
            $this->saveToCache($cacheData);
        }
    }

    /**
     * Loads the metadata right from cache
     * 
     * @param int $idUser ID user
     * @param string $key Metadata name
     * @return bool|null True if metadata right is allowed, false if it is not allowed, null if the entry does not exist
     */
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

    /**
     * Generates a filename for the cache file
     * 
     * @return string Filename
     */
    public function createFilename() {
        global $app;

        $name = date('Y-m-d') . $this->category;

        $file = md5($name) . '.tmp';

        return $file;
    }

    /**
     * Loads data from cache
     * 
     * @return array $data Cache data
     */
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

    /**
     * Saves data to cache
     * 
     * @param array $data Data to be cached
     */
    private function saveToCache(array $data) {
        $filename = $this->createFilename();

        if($this->serialize) {
            $data = serialize($data);
        }

        $this->fm->writeCache($filename, $data);
    }

    /**
     * Returns a temporary object
     * 
     * @param string $category Cache category
     * @return CacheManager self
     */
    public static function getTemporaryObject(string $category, bool $isAjax = false, array $cfg = []) {
        if($isAjax) {
            if(empty($cfg)) {
                die();
            } else {
                return new self($cfg['serialize_cache'], $category, '../../logs/', '../../cache/');
            }
        } else {
            global $app;

            return new self($app->cfg['serialize_cache'], $category);
        }
    }

    /**
     * Invalidates all types of cache
     */
    public static function invalidateAllCache() {
        global $app;

        foreach(CacheCategories::$all as $cc) {
            $cm = new self($app->cfg['serialize_cache'], $cc);

            $cm->invalidateCache();
        }
    }
}

?>