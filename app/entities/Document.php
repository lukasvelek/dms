<?php

namespace DMS\Entities;

/**
 * Document entity
 * 
 * @author Lukas Velek
 */
class Document extends AEntity {
    private string $name;
    private int $idAuthor;
    private ?int $idOfficer;
    private int $idManager;
    private int $status;
    private int $idGroup;
    private int $isDeleted;
    private string $rank;
    private ?int $idFolder;
    private ?string $file;
    private string $shredYear;
    private string $afterShredAction;
    private int $shreddingStatus;
    private ?int $idArchiveDocument;
    private ?int $idArchiveBox;
    private ?int $idArchiveArchive;

    private array $metadata;
    
    /**
     * Class constructor
     * 
     * @param int $id Document ID
     * @param string $dateCreated Date created
     * @param int $idAuthor Author ID
     * @param null|int $idOfficer Current officer ID
     * @param string $name Document name
     * @param int $status Document status
     * @param int $idManager Manager ID
     * @param int $idGroup Group ID
     * @param int $isDeleted Is document deleted
     * @param string $rank Document rank
     * @param null|int $idFolder Folder ID or null
     * @param null|string $file Filepath or null
     * @param string $shredYear Shread year
     * @param string $afterShredAction After shred action
     * @param int $shreddingStatus Shredding status
     * @param string $dateUpdate Date updated
     * @param null|int $idArchiveDocument Archive document ID
     * @param null|int $idArchiveBox Archive box ID
     * @param null|int $idArchiveArchive Archive ID
     */
    public function __construct(int $id,
                                string $dateCreated,
                                int $idAuthor,
                                ?int $idOfficer,
                                string $name,
                                int $status,
                                int $idManager,
                                int $idGroup,
                                int $isDeleted,
                                string $rank,
                                ?int $idFolder,
                                ?string $file,
                                string $shredYear,
                                string $afterShredAction,
                                int $shreddingStatus,
                                string $dateUpdated,
                                ?int $idArchiveDocument,
                                ?int $idArchiveBox,
                                ?int $idArchiveArchive) {
        parent::__construct($id, $dateCreated, $dateUpdated);

        $this->idAuthor = $idAuthor;
        $this->idOfficer = $idOfficer;
        $this->name = $name;
        $this->status = $status;
        $this->idManager = $idManager;
        $this->idGroup = $idGroup;
        $this->isDeleted = $isDeleted;
        $this->rank = $rank;
        $this->idFolder = $idFolder;
        $this->file = $file;
        $this->shredYear = $shredYear;
        $this->afterShredAction = $afterShredAction;
        $this->shreddingStatus = $shreddingStatus;
        $this->idArchiveDocument = $idArchiveDocument;
        $this->idArchiveBox = $idArchiveBox;
        $this->idArchiveArchive = $idArchiveArchive;
    }

    /**
     * Returns ID author
     * 
     * @return int Author ID
     */
    public function getIdAuthor() {
        return $this->idAuthor;
    }

    /**
     * Returns ID current officer
     * 
     * @return null|int Current officer ID or null
     */
    public function getIdOfficer() {
        return $this->idOfficer;
    }

    /**
     * Returns document name
     * 
     * @return string Document name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns document status
     * 
     * @return int Document status
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Returns ID manager
     * 
     * @return int Manager ID
     */
    public function getIdManager() {
        return $this->idManager;
    }

    /**
     * Returns ID group
     * 
     * @return int Group ID
     */
    public function getIdGroup() {
        return $this->idGroup;
    }

    /**
     * Returns whether the document is deleted
     * 
     * @return int 1 if document is deleted or 0 if not
     */
    public function getIsDeleted() {
        return $this->isDeleted;
    }

    /**
     * Returns document rank
     * 
     * @return string Document rank
     */
    public function getRank() {
        return $this->rank;
    }

    /**
     * Returns document custom metadata or its value
     * 
     * @param string $key If empty an array of custom metadata is returned or otherwise custom metadata with a given name
     * @return null|array|mixed Null or custom metadata array or custom metadata value
     */
    public function getMetadata(string $key = '') {
        if($key != '') {
            if(array_key_exists($key, $this->metadata)) {
                return $this->metadata[$key];
            } else if(isset($this->{$key})) {
                return $this->{$key};
            } else {
                return null;
            }
        } else {
            return $this->metadata;
        }
    }

    /**
     * Sets custom metadata
     * 
     * @param array $metadata Custom metadata
     */
    public function setMetadata(array $metadata) {
        $this->metadata = $metadata;
    }

    /**
     * Returns ID folder
     * 
     * @return null|int Folder ID or null
     */
    public function getIdFolder() {
        return $this->idFolder;
    }

    /**
     * Returns file path
     * 
     * @return null|string File path or null
     */
    public function getFile() {
        return $this->file;
    }

    /**
     * Returns document shred year
     * 
     * @return string Shred year
     */
    public function getShredYear() {
        return $this->shredYear;
    }

    /**
     * Returns document after shred action
     * 
     * @return string After shred action
     */
    public function getAfterShredAction() {
        return $this->afterShredAction;
    }

    /**
     * Returns document shredding status
     * 
     * @return int Shredding status
     */
    public function getShreddingStatus() {
        return $this->shreddingStatus;
    }

    /**
     * Returns archive document ID
     * 
     * @return null|int Archive document ID or null
     */
    public function getIdArchiveDocument() {
        return $this->idArchiveDocument;
    }

    /**
     * Returns archive box ID
     * 
     * @return null|int Archive box ID or null
     */
    public function getIdArchiveBox() {
        return $this->idArchiveBox;
    }

    /**
     * Returns archive ID
     * 
     * @return null|int Archive ID or null
     */
    public function getIdArchiveArchive() {
        return $this->idArchiveArchive;
    }
}

?>