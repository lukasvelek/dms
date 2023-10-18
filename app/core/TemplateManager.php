<?php

namespace DMS\Core;

class TemplateManager {
    private FileManager $fileManager;

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
            if(!is_array($value)) {
                $subject = str_replace($key, $value, $subject);
            } else {
                $keyValueData = '';

                foreach($value as $v) {
                    $keyValueData .= $v;
                }

                $subject = str_replace($key, $keyValueData, $subject);
            }
        }
    }

    public static function getTemporaryObject() {
        return new self(FileManager::getTemporaryObject());
    }
}

?>