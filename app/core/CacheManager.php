<?php

namespace DMS\Core;

use DMS\Constants\CacheCategories;
use DMS\Entities\Folder;
use DMS\Entities\Ribbon;
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

    public function saveFlashMessage(array $data) {
        $cacheData = $this->loadFromCache();

        if($cacheData === FALSE) {
            $cacheData = [];
        }

        $cacheData[] = $data;

        $this->saveToCache($cacheData);
    }

    public function loadFlashMessage() {
        $cacheData = $this->loadFromCache();

        if($cacheData === FALSE) {
            return null;
        } else {
            return $cacheData;
        }

        return null;
    }

    public function loadRibbonById(int $idRibbon) {
        $cacheData = $this->loadFromCache();

        if($cacheData === FALSE) {
            return null;
        } else {
            foreach($cacheData as $cd) {
                if(is_array($cd)) {
                    foreach($cd as $cdcd) {
                        if($cdcd instanceof Ribbon) {
                            if($cdcd->getId() == $idRibbon) {
                                return $cdcd;
                            }
                        }
                    }
                } else if($cd instanceof Ribbon) {
                    if($cd->getId() == $idRibbon) {
                        return $cd;
                    }
                }
            }
        }

        return null;
    }

    public function saveRibbon(Ribbon $ribbon) {
        $cacheData = $this->loadFromCache();

        if($ribbon->hasParent()) {
            $cacheData[$ribbon->getIdParentRibbon()][] = $ribbon;
        } else {
            $cacheData['null'][] = $ribbon;
        }

        $this->saveToCache($cacheData);
    }

    public function loadRibbons() {
        $cacheData = $this->loadFromCache();

        if($cacheData === FALSE) {
            return null;
        }

        return $cacheData;
    }

    public function loadTopRibbons() {
        $cacheData = $this->loadFromCache();

        if($cacheData === FALSE) {
            return null;
        }

        if(array_key_exists('null', $cacheData)) {
            return $cacheData['null'];
        } else {
            return null;
        }
    }

    public function loadChildrenRibbons(int $idParentRibbon) {
        $cacheData = $this->loadFromCache();

        if($cacheData === FALSE) {
            return null;
        }

        if(array_key_exists($idParentRibbon, $cacheData)) {
            return $cacheData[$idParentRibbon];
        } else {
            return null;
        }
    }

    public function loadSiblingRibbons(int $idRibbon) {
        $cacheData = $this->loadFromCache();

        if($cacheData === FALSE) {
            return null;
        }

        foreach($cacheData as $k => $cd) {
            if($k == $idRibbon) {
                echo('1');
                return $cd;
            } else {
                foreach($cacheData[$k] as $cdk => $cdcd) {
                    if($cdk == $idRibbon) {
                        echo('2');
                        return $cdcd;
                    }
                }
            }
        }

        return null;
    }

    public function saveUserRibbonRight(int $idRibbon, int $idUser, string $category, bool $result) {
        $cacheData = $this->loadFromCache();

        $cacheData[$idRibbon][$idUser][$category] = $result;

        $this->saveToCache($cacheData);
    }

    public function loadUserRibbonRight(int $idRibbon, int $idUser, string $category) {
        $cacheData = $this->loadFromCache();

        if($cacheData === FALSE) {
            return null;
        }

        if(array_key_exists($idRibbon, $cacheData)) {
            if(array_key_exists($idUser, $cacheData[$idRibbon])) {
                if(array_key_exists($category, $cacheData[$idRibbon][$idUser])) {
                    return $cacheData[$idRibbon][$idUser][$category];
                }
            }
        }

        return null;
    }

    public function saveGroupRibbonRight(int $idRibbon, int $idGroup, string $category, bool $result) {
        $cacheData = $this->loadFromCache();

        $cacheData[$idRibbon][$idGroup][$category] = $result;

        $this->saveToCache($cacheData);
    }

    public function loadGroupRibbonRight(int $idRibbon, int $idGroup, string $category) {
        $cacheData = $this->loadFromCache();

        if($cacheData === FALSE) {
            return null;
        }

        if(array_key_exists($idRibbon, $cacheData)) {
            if(array_key_exists($idGroup, $cacheData[$idRibbon])) {
                if(array_key_exists($category, $cacheData[$idRibbon][$idGroup])) {
                    return $cacheData[$idRibbon][$idGroup][$category];
                }
            }
        }

        return null;
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
        $name = date('Y-m-d') . $this->category;

        $dirname = 'dmsCache';

        if(!is_dir(AppConfiguration::getCacheDir() . $dirname)) {
            mkdir(AppConfiguration::getCacheDir() . $dirname);
        }

        if(!is_dir(AppConfiguration::getCacheDir() . $dirname . '/' . $this->category . '/')) {
            mkdir(AppConfiguration::getCacheDir() . $dirname . '/' . $this->category . '/');
        }

        $file = $dirname . '/' . $this->category . '/' . md5($name) . '.tmp';

        return $file;
    }

    /**
     * Loads data from cache
     * 
     * @return array|false $data Cache data or false if no data exists
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
    public static function getTemporaryObject(string $category, bool $isAjax = false) {
        if($isAjax) {
            return new self(AppConfiguration::getSerializeCache(), $category, '../../' . AppConfiguration::getLogDir(), '../../' . AppConfiguration::getCacheDir());
        } else {
            return new self(AppConfiguration::getSerializeCache(), $category);
        }
    }

    /**
     * Invalidates all types of cache
     */
    public static function invalidateAllCache() {
        foreach(CacheCategories::$all as $cc) {
            $cm = new self(AppConfiguration::getSerializeCache(), $cc);

            $cm->invalidateCache();
        }
    }
}

?>