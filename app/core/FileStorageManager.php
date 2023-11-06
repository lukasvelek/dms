<?php

namespace DMS\Core;

use DMS\Core\Logger\Logger;
use DMS\Entities\FileStorageFile;

class FileStorageManager {
    private string $fileFolder;
    private FileManager $fm;
    private Logger $logger;

    private static array $allowedFileTypes = array(
        'pdf',
        'txt',
        'log',
        'docx',
        'xml',
        'xlsx',
        'csv'
    );

    public function __construct(string $fileFolder, FileManager $fm, Logger $logger) {
        $this->fileFolder = $fileFolder;
        $this->fm = $fm;
        $this->logger = $logger;
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
     * Returns an array of stored files
     * 
     * @return array<FileStorageFile> array of stored files
     */
    public function getStoredFiles() {
        $files = [];
        
        $this->fm->readFilesInFolder($this->fileFolder, $files);

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

            $fileObjects[] = new FileStorageFile($name, $path, $extension);
        }

        return $fileObjects;
    }

    /**
     * Uploads a file to a folder defined in config.
     * Checks if the uploaded file is allowed.
     * 
     * @param mixed $file File variable passed from the form
     * @return bool True if upload was successful and false if not
     */
    public function uploadFile($file, string &$filePath) {
        $d = function(string $text) {
            return date($text) . '/';
        };

        $targetFile = $this->fileFolder . $d('Y') . $d('m') . $d('d') . $d('H') . $d('i') . $file['name'];
        $ok = true;

        if(!is_dir($this->fileFolder . $d('Y') . $d('m') . $d('d') . $d('H') . $d('i'))) {
            $this->logger->warn('Specified folder does not exist! Creating...', __METHOD__);
            $ok = mkdir($this->fileFolder . $d('Y') . $d('m') . $d('d') . $d('H') . $d('i'), 0777, true);
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
        }

        $filePath = $targetFile;

        return $ok;
    }
}

?>