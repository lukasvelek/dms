<?php

namespace DMS\Core;

class FileManager {
    /**
     * @var string
     */
    private $logFolder;

    /**
     * @var string
     */
    private $cacheFolder;

    public function __construct(string $logFolder, string $cacheFolder) {
        if(is_dir($logFolder) || $logFolder == '') {
            $this->logFolder = $logFolder;
        } else {
            die('Log folder does not exist!');
        }

        if(is_dir($cacheFolder) || $logFolder == '') {
            $this->cacheFolder = $cacheFolder;
        } else {
            die('Cache folder does not exist!');
        }
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
        if(($this->fileExists($file) && $overwrite) || !$this->fileExists($file)) {
            if($overwrite) {
                file_put_contents($file, $data);
            } else {
                file_put_contents($file, $data, FILE_APPEND);
            }

            return true;
        } else {
            return false;
        }
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
     * Checks if a defined file exists or not.
     * 
     * @param string $file filename
     * @return bool returns true if file exists or false if not
     */
    public function fileExists(string $file) {
        if(file_exists($file)) return true;

        return false;
    }

    public static function getTemporaryObject() {
        return new self('', '');
    }
}

?>