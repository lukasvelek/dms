<?php

namespace DMS\Core;

class FileManager {
    public string $logFolder;
    public string $cacheFolder;

    public function __construct(string $logFolder, string $cacheFolder) {
        if(is_dir($logFolder) || $logFolder == '') {
            $this->logFolder = $logFolder;
        } else {
            die('Log folder does not exist!');
        }

        if(is_dir($cacheFolder) || $cacheFolder == '') {
            $this->cacheFolder = $cacheFolder;
        } else {
            die('Cache folder does not exist!');
        }
    }

    public function createDirectory(string $dirpath) {
        if(is_dir($dirpath)) {
           return false; 
        }

        return mkdir($dirpath, 0777, true);
    }

    public function moveFileToDirectory(string $filepath, string $newFilepath) {
        return rename($filepath, $newFilepath);
    }

    public function readFoldersInFolder(string $dir, array &$dirs) {
        $contents = scandir($dir);

        unset($contents[0]);
        unset($contents[1]);

        foreach($contents as $c) {
            if(is_dir($dir . $c . '/')) {
                $dirs[] = $dir . $c . '/';
                $this->readFoldersinFolder($dir . $c . '/', $dirs);
            }
        }
    }

    /**
     * Returns a list of files in the directory and all subdirectories
     * 
     * @param string $dir Dir name
     * @param array $files Link to the files array
     */
    public function readFilesInFolder(string $dir, array &$files) {
        $contents = scandir($dir);

        unset($contents[0]);
        unset($contents[1]);

        foreach($contents as $c) {
            if(is_dir($dir . $c . '/')) {
                $this->readFilesInFolder($dir . $c . '/', $files);
            } else {
                $files[] = $dir . $c;
            }
        }
    }

    /**
     * Saves a cache file to the cache folder
     * 
     * @param string $file filename
     * @param string $data serialized cache data
     * @return bool true if data was written and false if it was not written
     */
    public function writeCache(string $file, string|array $data) {
        return $this->write($this->cacheFolder . $file, $data, true);
    }

    /**
     * Reads a cache file from the cache folder
     * 
     * @param string $file filename
     * @param bool $returnArray true if array should be returned and false if string should be returned
     * @return string|array|bool string or array if file is successfully loaded and false if it does not exist
     */
    public function readCache(string $file, bool $returnArray = false) {
        if($returnArray) {
            return $this->readArray($this->cacheFolder . $file);
        } else {
            return $this->read($this->cacheFolder . $file);
        }
    }

    /**
     * Invalidates a cache file by deleting it
     * 
     * @param string $file filename
     * @return bool true if the operation was successful and false if it was not
     */
    public function invalidateCache(string $file) {
        return $this->deleteFile($this->cacheFolder . $file);
    }

    /**
     * Writes log to the log file
     * 
     * @param string $file filename
     * @param string $data
     * @return bool true if data was successfully written to the file or false if not
     */
    public function writeLog(string $file, string $data) {
        return $this->write($this->logFolder . $file, $data, false);
    }

    /**
     * Writes data to the file
     * 
     * @param string $file filename
     * @param string|array $data data to be written
     * @param bool $overwrite if the file should be overwritten or not
     * @return bool true if data was successfully written to the file or false if not
     */
    public function write(string $file, string|array $data, bool $overwrite = true) {
        if($overwrite) {
            file_put_contents($file, $data);
        } else {
            file_put_contents($file, $data, FILE_APPEND);
        }

        return true;
    }

    /**
     * Reads data from the file and returns it as a string
     * 
     * @param string $file filename
     * @return string|bool file content as string or if not successful false
     */
    public function read(string $file) {
        if($this->fileExists($file)) {
            return file_get_contents($file);
        } else {
            return false;
        }
    }

    /**
     * Reads data from the file and returns it as an array
     * 
     * @param string $file filename
     * @return array|bool file content as an array or if not successful false
     */
    public function readArray(string $file) {
        if($this->fileExists($file)) {
            $data = array();

            $handler = fopen($file, 'r');

            if($handler) {
                while(($line = fgets($handler)) !== false) {
                    $data[] = $line;
                }
            }

            if(!empty($data)) return $data;
        }

        return false;
    }

    /**
     * Deletes a defined file
     * 
     * @param string $file filename
     * @return bool returns true if file was deleted and false if it does not exist
     */
    public function deleteFile(string $file) {
        if($this->fileExists($file)) {
            unlink($file);
        } else {
            return false;
        }

        return true;
    }

    /**
     * Returns temporary object with empty parameters -> log folder and cache folder is not set
     * 
     * @return FileManager self
     */
    public static function getTemporaryObject() {
        return new self('', '');
    }

    /**
     * Checks if a defined file exists or not.
     * 
     * @param string $file filename
     * @return bool returns true if file exists or false if not
     */
    public static function fileExists(string $file) {
        if(file_exists($file)) return true;

        return false;
    }
}

?>