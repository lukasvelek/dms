<?php

namespace DMS\Core;

class TemplateManager {
    /**
     * @var FileManager
     */
    private $fileManager;

    public function __construct(FileManager $fileManager) {
        $this->fileManager = $fileManager;
    }

    public function loadTemplate(string $file) {
        return $this->fileManager->read($file);
    }

    public function replace(string $search, string $replace, string &$subject) {
        $subject = str_replace($search, $replace, $subject);
    }

    public function fill(array $data, string &$subject) {
        foreach($data as $key => $value) {
            $subject = str_replace($key, $value, $subject);
        }
    }

    public static function getTemporaryObject() {
        return new self(FileManager::getTemporaryObject());
    }
}

?>