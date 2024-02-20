<?php

namespace DMS\Core;

/**
 * Manager responsible for using templates
 * 
 * @author Lukas Velek
 */
class TemplateManager {
    private FileManager $fileManager;

    /**
     * Class constructor
     * 
     * @param FileManager $fileManager FileManager instance
     */
    public function __construct(FileManager $fileManager) {
        $this->fileManager = $fileManager;
    }

    /**
     * Loads the template
     * 
     * @param string $file Template file location
     * @return string File content or error message
     */
    public function loadTemplate(string $file) {
        $file = $this->fileManager->read($file);

        if(is_string($file)) {
            return $file;
        } else {
            return 'Template <i>' . $file . '</i> not found!';
        }
    }

    /**
     * Replaces searched elements with different elements in a given subject (text)
     * 
     * @param string $search Searched element
     * @param string $replace Replacing element
     * @param string $subject Text
     */
    public function replace(string $search, string $replace, string &$subject) {
        $subject = str_replace($search, $replace, $subject);
    }

    /**
     * Fills the template with given data
     * 
     * @param array $data Data used to fill the template
     * @param string $subject Text
     */
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

    /**
     * Returns TemplateManager instance
     * 
     * @return self
     */
    public static function getTemporaryObject() {
        return new self(FileManager::getTemporaryObject());
    }
}

?>