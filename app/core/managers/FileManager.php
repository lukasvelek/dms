<?php

namespace App\Core\Managers;

class FileManager {
    public array $cfg;

    public function __construct(array $cfg) {
        $this->cfg = $cfg;
    }

    public function fileExists(string $path) {
        return file_exists($path);
    }

    public function saveFile(string $path, string $content, bool $rewrite = false) {
        if($this->fileExists($path) && !$rewrite) {
            return false;
        }

        $result = 0;
        if($rewrite) {
            $result = file_put_contents($path, $content);
        } else {
            $result = file_put_contents($path, $content, FILE_APPEND);
        }

        if(is_numeric($result) && $result > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function loadFile(string $path) {
        if(!$this->fileExists($path)) {
            return false;
        }

        return file_get_contents($path);
    }

    public function dirExists(string $path) {
        return is_dir($path);
    }

    public function createDir(string $path) {
        return mkdir($path);
    }
}

?>