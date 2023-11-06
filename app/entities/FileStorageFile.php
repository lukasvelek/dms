<?php

namespace DMS\Entities;

class FileStorageFile {
    private string $name;
    private string $path;
    private string $extension;

    public function __construct(string $name, string $path, string $extension) {
        $this->name = $name;
        $this->path = $path;
        $this->extension = $extension;
    }

    public function getName() {
        return $this->name;
    }

    public function getPath() {
        return $this->path;
    }

    public function getExtension() {
        return $this->extension;
    }

    public static function createObject(string $filename) {
        
    }
}

?>