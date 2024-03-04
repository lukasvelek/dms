<?php

namespace DMS\Core;

use DMS\Constants\CacheCategories;
use DMS\Entities\Folder;
use DMS\Entities\Group;
use DMS\Entities\Ribbon;
use DMS\Entities\User;

/**
 * CacheManager allows the application to cache data
 * 
 * @author Lukas Velek
 */
class CacheManager {
    private const SERIALIZE = true;
    private const ADVANCED_CACHE_PROTECTION = true;

    private FileManager $fm;
    private string $category;
    
    /**
     * The CacheManager constructor
     * 
     * @param bool $serialize True if cache should be serialized and false if not
     * @param string $category Cache category
     */
    public function __construct(string $category, string $logdir, string $cachedir) {
        $this->fm = new FileManager($logdir, $cachedir);

        $this->category = $category;
    }

    /**
     * Saves group to cache
     * 
     * @param Group $group Group instance
     * @return void
     */
    public function saveGroupToCache(Group $group) {
        $cacheData = $this->loadFromCache();

        $cacheData[$this->category][$group->getId()] = $group;

        $this->saveToCache($cacheData);
    }

    /**
     * Loads group from cache
     * 
     * @param int $id Group ID
     * @return null|Group Group instance or null
     */
    public function loadGroupByIdFromCache(int $id) {
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
     * Saves a service cache entry
     * 
     * @param string $name Service name
     * @param array $data Service data
     * @return void
     */
    public function saveServiceEntry(string $name, array $data) {
        $cacheData = $this->loadFromCache();

        if($cacheData === FALSE) {
            $cacheData = [];
        }

        $cacheData[$name] = $data;

        $this->saveToCache($cacheData);
    }

    /**
     * Loads a service cache entry
     * 
     * @param string $name Service name
     * @return null|array|string Service data or null
     */
    public function loadServiceEntry(string $name) {
        $cacheData = $this->loadFromCache();

        if($cacheData === FALSE) {
            return null;
        }

        if(!array_key_exists($name, $cacheData)) {
            return null;
        }

        return $cacheData[$name];
    }

    /**
     * Saves a flash message to cache
     * 
     * @param array $data Flash message data
     */
    public function saveFlashMessage(array $data) {
        $cacheData = $this->loadFromCache();

        if($cacheData === FALSE) {
            $cacheData = [];
        }

        $cacheData[] = $data;

        $this->saveToCache($cacheData);
    }

    /**
     * Loads a flash message from cache
     * 
     * @return null|array|string Flash message data
     */
    public function loadFlashMessage() {
        $cacheData = $this->loadFromCache();

        if($cacheData === FALSE) {
            return null;
        } else {
            return $cacheData;
        }

        return null;
    }

    /**
     * Loads ribbon by ID from cache
     * 
     * @param int $idRibbon Ribbon ID to be returned from cache
     * @return null|Ribbon Ribbon instance or null
     */
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

    /**
     * Saves ribbon to cache
     * 
     * @param Ribbon $ribbon Ribbon instance
     * @return void
     */
    public function saveRibbon(Ribbon $ribbon) {
        $cacheData = $this->loadFromCache();

        if($ribbon->hasParent()) {
            $cacheData[$ribbon->getIdParentRibbon()][] = $ribbon;
        } else {
            $cacheData['null'][] = $ribbon;
        }

        $this->saveToCache($cacheData);
    }

    /**
     * Loads ribbons from cache
     * 
     * @return null|array Cached ribbons
     */
    public function loadRibbons() {
        $cacheData = $this->loadFromCache();

        if($cacheData === FALSE) {
            return null;
        }

        return $cacheData;
    }

    /**
     * Loads top (root) ribbons from cache
     * 
     * @return null|array Cached ribbons
     */
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

    /**
     * Loads chidren ribbons for ID parent ribbon from cache
     * 
     * @param int $idParentRibbon Children ribbon ID
     * @return null|array Cached ribbons
     */
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

    /**
     * Loads sibling ribbons for ID ribbon from cache
     * 
     * @param int $idRibbon Ribbon ID
     * @return null|array Cached ribbons
     */
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

    /**
     * Saves user ribbon right to cache
     * 
     * @param int $idRibbon Ribbon ID
     * @param int $idUser User ID
     * @param string $category Right category
     * @param bool $result Result of the right evaluation
     * @return void
     */
    public function saveUserRibbonRight(int $idRibbon, int $idUser, string $category, bool $result) {
        $cacheData = $this->loadFromCache();

        $cacheData[$idRibbon][$idUser][$category] = $result;

        $this->saveToCache($cacheData);
    }

    /**
     * Loads user ribbon right from cache
     * 
     * @param int $idRibbon Ribbon ID
     * @param int $idUser User ID
     * @param string $category Right category
     * @return null|bool Result of the right evaluation
     */
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

    /**
     * Saves group ribbon right to cache
     * 
     * @param int $idRibbon Ribbon ID
     * @param int $idGroup Group ID
     * @param string $category Right category
     * @param bool $result Result of the right evaluation
     * @return void
     */
    public function saveGroupRibbonRight(int $idRibbon, int $idGroup, string $category, bool $result) {
        $cacheData = $this->loadFromCache();

        $cacheData[$idRibbon][$idGroup][$category] = $result;

        $this->saveToCache($cacheData);
    }

    /**
     * Loads group ribbon right from cache
     * 
     * @param int $idRibbon Ribbon ID
     * @param int $idGroup Group ID
     * @param string $category Right category
     * @return null|bool Result of the right evaluation
     */
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

    /**
     * Saves array to cache
     * 
     * @param array $array Array
     * @return void
     */
    public function saveArrayToCache(array $array) {
        $cacheData = $this->loadFromCache();

        $cacheData[$this->category] = $array;

        $this->saveToCache($cacheData);
    }

    /**
     * Saves string to cache
     * 
     * @param string $text Text
     * @return void
     */
    public function saveStringToCache(string $text) {
        $cacheData = $this->loadFromCache();

        $cacheData[$this->category][] = $text;

        $this->saveToCache($cacheData);
    }

    /**
     * Loads strings from cache
     * 
     * @return null|mixed Cached data
     */
    public function loadStringsFromCache() {
        $cacheData = $this->loadFromCache();

        if($cacheData === FALSE) {
            return null;
        }

        return $cacheData[$this->category];
    }

    /**
     * Saves folder to cache
     * 
     * @param Folder $folder Folder instance
     * @return void
     */
    public function saveFolderToCache(Folder $folder) {
        $cacheData = $this->loadFromCache();

        $cacheData[$this->category][$folder->getId()] = $folder;

        $this->saveToCache($cacheData);
    }

    /**
     * Loads folder from cache
     * 
     * @param int $id Folder ID
     * @return null|Folder Folder instance or null
     */
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

    /**
     * Saves user to cache
     * 
     * @param User $user User instance
     * @return void
     */
    public function saveUserToCache(User $user) {
        $cacheData = $this->loadFromCache();

        $cacheData[$this->category][$user->getId()] = $user;

        $this->saveToCache($cacheData);
    }

    /**
     * Loads user from cache
     * 
     * @param int $id User ID
     * @return null|User User instance or null
     */
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

        $this->fm->deleteFile('cache/' . $filename);
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

        if(!is_dir($this->fm->cacheFolder . $dirname)) {
            mkdir($this->fm->cacheFolder . $dirname);
        }

        if(!is_dir($this->fm->cacheFolder . $dirname . '/' . $this->category . '/')) {
            mkdir($this->fm->cacheFolder . $dirname . '/' . $this->category . '/');
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

        if(self::SERIALIZE) {
            if(self::ADVANCED_CACHE_PROTECTION) {
                $data = unserialize(base64_decode($data));
            } else {
                $data = unserialize($data);
            }
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

        if(self::SERIALIZE) {
            if(self::ADVANCED_CACHE_PROTECTION) {
                $data = base64_encode(serialize($data));
            } else {
                $data = serialize($data);
            }
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
            return new self($category, '../../' . AppConfiguration::getLogDir(), '../../' . AppConfiguration::getCacheDir());
        } else {
            return new self($category, AppConfiguration::getLogDir(), AppConfiguration::getCacheDir());
        }
    }

    /**
     * Invalidates all types of cache
     */
    public static function invalidateAllCache() {
        foreach(CacheCategories::$all as $cc) {
            $cm = new self($cc, AppConfiguration::getLogDir(), AppConfiguration::getCacheDir());

            $cm->invalidateCache();
        }
    }
}

?>