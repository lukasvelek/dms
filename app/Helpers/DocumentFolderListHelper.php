<?php

namespace DMS\Helpers;

use DMS\Entities\Folder;
use DMS\Models\FolderModel;
use DMS\UI\LinkBuilder;

class DocumentFolderListHelper {
    private FolderModel $folderModel;

    public function __construct(FolderModel $folderModel) {
        $this->folderModel = $folderModel;
    }

    public function createFolderList(Folder $folder, array &$list, int $level, ?string $filter, string $defaultLink = 'showAll', array $folderArray = []) {
        $link = 'showAll';
        if($filter !== NULL) {
            $link = 'showFiltered';
        }

        if(empty($folderArray)) {
            $folderArray = $this->folderModel->getAllFolders();
        }

        $childFolders = $this->getFoldersForIdParentFolder($folder->getId(), $folderArray);

        $folderLink = $this->createFolderLink($link, $folder->getName(), $folder->getId(), $filter);

        $spaces = '&nbsp;&nbsp;';

        if($level > 0) {
            for($i = 0; $i < $level; $i++) {
                $spaces .= '&nbsp;&nbsp;';
            }
        }

        if(!array_key_exists($folder->getId(), $list)) {
            $list[$folder->getId()] = $spaces . $folderLink . '<br>';
        }

        if(count($childFolders) > 0) {
            foreach($childFolders as $cf) {
                $this->createFolderList($cf, $list, $level + 1, $filter, $defaultLink, $folderArray);
            }
        }
    }

    public function createFolderLink(string $action, string $text, ?int $idFolder, ?string $filter) {
        $url = [
            'page' => $action
        ];

        if($idFolder !== NULL) {
            $url['id_folder'] = $idFolder;
        }

        if($filter !== NULL) {
            $url['filter'] = $filter;
        }

        return LinkBuilder::createAdvLink($url, $text);
    }

    private function getFoldersForIdParentFolder(int $idParentFolder, array $folderArray) {
        $folders = [];
        foreach($folderArray as $fa) {
            if($fa->getIdParentFolder() == $idParentFolder) {
                $folders[] = $fa;
            }
        }
        return $folders;
    }
}

?>