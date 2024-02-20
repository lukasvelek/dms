<?php

namespace DMS\Enums;

use DMS\Models\FolderModel;

/**
 * Folders external enum
 * 
 * @author LUkas Velek
 */
class FoldersEnum extends AEnum {
    private FolderModel $folderModel;

    /**
     * Class constructor
     * 
     * @param FolderModel $folderModel FolderModel isntance
     */
    public function __construct(FolderModel $folderModel) {
        parent::__construct('FoldersEnum');
        $this->folderModel = $folderModel;

        $this->loadValues();
    }

    /**
     * Loads enum values
     */
    private function loadValues() {
        $folders = $this->folderModel->getAllFolders();

        $this->addValue('null', '-');

        foreach($folders as $folder) {
            $this->addValue($folder->getId(), $folder->getName());
        }
    }
}

?>