<?php

namespace DMS\Entities;

class FileStorageFile {
    private string $fullname;
    private string $name;
    private string $path;
    private string $extension;

    public function __construct(string $fullname, string $name, string $path, string $extension) {
        $this->fullname = $fullname;
        $this->name = $name;
        $this->path = $path;
        $this->extension = $extension;
    }

    public function getFullname() {
        return $this->fullname;
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