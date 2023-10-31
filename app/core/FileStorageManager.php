<?php

namespace DMS\Core;

use DMS\Core\Logger\Logger;

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
     * Uploads a file to a folder defined in config.
     * Checks if the uploaded file is allowed.
     * 
     * @param mixed $file File variable passed from the form
     * @return bool True if upload was successful and false if not
     */
    public function uploadFile($file) {
        $d = function(string $text) {
            return date($text) . '/';
        };

        $targetFile = $this->fileFolder . $d('Y') . $d('m') . $d('d') . $d('H') . $d('i') . $d('s') . $file['name'];
        $ok = true;

        $this->fm->fileExists($targetFile);

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

        return $ok;
    }
}

?>