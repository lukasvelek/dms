<?php

namespace DMS\Enums;

use DMS\Models\FolderModel;

class FoldersEnum extends AEnum {
    private FolderModel $folderModel;

    public function __construct(FolderModel $folderModel) {
        parent::__construct('FoldersEnum');
        $this->folderModel = $folderModel;

        $this->loadValues();
    }

    private function loadValues() {
        $folders = $this->folderModel->getAllFolders();

        $this->addValue('null', '-');

        foreach($folders as $folder) {
            $this->addValue($folder->getId(), $folder->getName());
        }
    }
}

?>