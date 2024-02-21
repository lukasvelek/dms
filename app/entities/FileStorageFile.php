<?php

namespace DMS\Entities;

/**
 * Filestorage file entity
 * 
 * @author Lukas Velek
 */
class FileStorageFile extends AEntity {
    private string $fullname;
    private string $name;
    private string $path;
    private string $extension;

    /**
     * Class constructor
     * 
     * @param string $fullname File fullname
     * @param string $name Filename
     * @param string $path Filepath
     * @param string $extension File extension
     */
    public function __construct(string $fullname, string $name, string $path, string $extension) {
        $this->fullname = $fullname;
        $this->name = $name;
        $this->path = $path;
        $this->extension = $extension;
    }

    /**
     * Returns file fullname
     * 
     * @return string File fullname
     */
    public function getFullname() {
        return $this->fullname;
    }

    /**
     * Returns filename
     * 
     * @return string Filename
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns filepath
     * 
     * @return string Filepath
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * Returns file extension
     * 
     * @return string File extension
     */
    public function getExtension() {
        return $this->extension;
    }
}

?>