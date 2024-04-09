<?php

namespace DMS\Core;

use DMS\Constants\FileStorageTypes;
use DMS\Core\Logger\Logger;
use DMS\Entities\FileStorageFile;
use DMS\Entities\FileStorageLocation;
use DMS\Models\FileStorageModel;

/**
 * Manager responsible for storing files
 * 
 * @author Lukas Velek
 */
class FileStorageManager {
    public FileManager $fm;
    private Logger $logger;
    public FileStorageModel $fsm;

    private static array $allowedFileTypes = array(
        'pdf',
        'txt',
        'log',
        'docx',
        'xml',
        'xlsx',
        'csv'
    );

    /**
     * Class constructor
     * 
     * @param string $fileFolder Files folder
     * @param FileManager $fm FileManager instance
     * @param Logger $logger Logger instance
     */
    public function __construct(FileManager $fm, Logger $logger, FileStorageModel $fsm) {
        $this->fm = $fm;
        $this->logger = $logger;
        $this->fsm = $fsm;
    }

    /**
     * Moves file to the storage
     * 
     * @param string $filepath Imported filepath
     * @param FileStorageLocation $location FileStorageLocation instance of the final path
     * @return bool True on success or false on failure
     */
    public function storeFile(string $filepath, string $filename, FileStorageLocation $location) {
        return rename($filepath, $location->getPath());

        $d = function(string $text) {
            return date($text) . '\\';
        };

        $targetFile = $location->getPath() . $d('Y') . $d('m') . $d('d') . $d('H') . $d('i') . $filename;
        $ok = true;

        if(!is_dir($location->getPath() . $d('Y') . $d('m') . $d('d') . $d('H') . $d('i'))) {
            $this->logger->warn('Specified folder does not exist! Creating...', __METHOD__);
            $ok = mkdir($location->getPath() . $d('Y') . $d('m') . $d('d') . $d('H') . $d('i'), 0777, true);
            if($ok == true) {
                $this->logger->info('Folder has been created!', __METHOD__);
            } else {
                $this->logger->error('Folder could not be created!', __METHOD__);
            }
        }

        if($this->fm->fileExists($targetFile)) {
            return false;
        }

        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        if(!in_array($fileType, self::$allowedFileTypes)) {
            $this->logger->error('File is not allowed to be uploaded!', __METHOD__);
            return false;
        }

        return rename($filepath, $location->getPath());
    }

    /**
     * Returns the default file storage location for given type
     * 
     * @param string $type Storage type
     * @return FileStorageLocation Default file storage location for given type
     */
    public function getDefaultLocationForStorageType(string $type) {
        $locations = $this->fsm->getAllActiveFileStorageLocations(true);

        $filteredLocations = [];
        foreach($locations as $location) {
            if($location->getType() == $type) {
                $filteredLocations[] = $location;
            }
        }

        $defaultLocation = null;
        foreach($filteredLocations as $loc) {
            if($loc->isDefault() === TRUE) {
                $defaultLocation = $loc;
                break;
            }
        }

        return $defaultLocation;
    }

    /**
     * Returns free space left on a disk for a specified directory
     * 
     * @param string $directory Directory for which the free space is returned
     * @return string Free space left
     */
    public function getFreeSpaceLeft(string $directory) {
        $bytes = disk_free_space($directory);
        $prefix = ['','k', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y'];
        $base = 1000;
        $class = min((int)log($bytes, $base), count($prefix) - 1);
        return sprintf('%1.2f', $bytes / pow($base, $class)) . ' ' . $prefix[$class] . 'B';
    }

    /**
     * Returns total space on a disk for a specified directory
     * 
     * @param string $directory Directory for which the total space is returned
     * @return string Total space
     */
    public function getTotalSpace(string $directory) {
        $bytes = disk_total_space($directory);
        $prefix = ['','k', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y'];
        $base = 1000;
        $class = min((int)log($bytes, $base), count($prefix) - 1);
        return sprintf('%1.2f', $bytes / pow($base, $class)) . ' ' . $prefix[$class] . 'B';
    }

    /**
     * Returns count of stored files
     * 
     * @return int count of stored files
     */
    public function getStoredFileCount() {
        return count($this->getStoredFiles());
    }

    /**
     * Returns an array of files stored in the given directory
     * 
     * @param string $directory Directory to check for files
     * @return array<FileStorageFile> array of stored files
     */
    public function getStoredFilesInDirectory(string $directory) {
        $files = [];

        $this->fm->readFilesInFolder($directory, $files);

        $fileObjects = [];

        foreach($files as $f) {
            $fileParts = explode('/', $f);
            $filename = $fileParts[count($fileParts) - 1];
            $explode = explode('.', $filename);

            $name = '';
            $extension = '';
            $path = $f;

            if(!empty($explode)) {
                $name = $explode[0];
                $extension = $explode[count($explode) - 1];
            }

            $fileObjects[] = new FileStorageFile($f, $name, $path, $extension);
        }

        return $fileObjects;
    }

    /**
     * Returns an array of stored files
     * 
     * @return array<FileStorageFile> array of stored files
     */
    public function getStoredFiles(string $type = 'files') {
        $files = [];

        $directories = $this->getStorageDirectories($type);

        foreach($directories as $dir) {
            $this->fm->readFilesInFolder($dir, $files);
        }

        $fileObjects = [];

        foreach($files as $f) {
            $fileParts = explode('\\', $f);
            $filename = $fileParts[count($fileParts) - 1];
            $explode = explode('.', $filename);

            $name = '';
            $extension = '';
            $path = $f;

            if(!empty($explode)) {
                $name = $explode[0];
                $extension = $explode[count($explode) - 1];
            }

            $fileObjects[] = new FileStorageFile($f, $name, $path, $extension);
        }

        return $fileObjects;
    }

    /**
     * Uploads a file to a folder defined in config.
     * Checks if the uploaded file is allowed.
     * 
     * @param mixed $file File variable passed from the form
     * @param string $directory Directory chosen by user for the file to be moved to
     * @return bool True if upload was successful and false if not
     */
    public function uploadFile($file, string &$filePath, string $directory) {
        $d = function(string $text) {
            return date($text) . '\\';
        };

        $targetFile = $directory . $d('Y') . $d('m') . $d('d') . $d('H') . $d('i') . $file['name'];
        $ok = true;

        if(!is_dir($directory . $d('Y') . $d('m') . $d('d') . $d('H') . $d('i'))) {
            $this->logger->warn('Specified folder does not exist! Creating...', __METHOD__);
            $ok = mkdir($directory . $d('Y') . $d('m') . $d('d') . $d('H') . $d('i'), 0777, true);
            if($ok == true) {
                $this->logger->info('Folder has been created!', __METHOD__);
            } else {
                $this->logger->error('Folder could not be created!', __METHOD__);
            }
        }

        if($this->fm->fileExists($targetFile)) {
            return false;
        }

        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        if(!in_array($fileType, self::$allowedFileTypes)) {
            $ok = false;
            $this->logger->error('File is not allowed to be uploaded!', __METHOD__);
        }

        if($ok === false) {
            $this->logger->error('File could not be uploaded!', __METHOD__);
        } else {
            if(move_uploaded_file($file['tmp_name'], $targetFile)) {
                $this->logger->info('File has been uploaded!');
            } else {
                $this->logger->error('File could not be uploaded!', __METHOD__);

                $ok = false;
            }
            
            $filePath = $targetFile;
        }

        return $ok;
    }

    /**
     * Returns all file storage directories in the system
     * 
     * @return array Array of file storage directory paths
     */
    public function getStorageDirectories(string $type) {
        if($type == 'files') {
            $locations = $this->fsm->getAllActiveFileOnlyStorageLocations();
        } else if($type == 'document_reports') {
            $locations = $this->fsm->getAllActiveDocumentReportStorageLocations();
        } else {
            $locations = $this->fsm->getAllActiveFileStorageLocations();
        }

        $dirs = [];
        foreach($locations as $loc) {
            $dirs[] = $loc->getPath();
        }

        return $dirs;
    }
}

?>