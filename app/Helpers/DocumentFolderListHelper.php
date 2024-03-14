<?php

namespace DMS\Helpers;

use DMS\Entities\Folder;
use DMS\Models\FolderModel;
use DMS\UI\LinkBuilder;

/**
 * DocumentFolderListHelper helps with creating folder list
 * 
 * @author Lukas Velek
 */
class DocumentFolderListHelper {
    private FolderModel $folderModel;

    /**
     * Class constructor
     * 
     * @param FolderModel $folderModel FolderModel
     */
    public function __construct(FolderModel $folderModel) {
        $this->folderModel = $folderModel;
    }

    /**
     * Creates a folder list
     * 
     * @param Folder $folder Current folder
     * @param array $list Folder link list
     * @param int $level Current folder nest level
     * @param null|string $filter Current filter
     * @param string $defaultLink Default action used in folder links
     * @param array $folderArray Array of folders
     */
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

    /**
     * Creates a folder link
     * 
     * @param string $action Page to redirect to
     * @param string $text Link text
     * @param null|int $idFolder Folder ID
     * @param null|string $filter Filter
     * @return string HTML link code
     */
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

    /**
     * Gets all folders for a parent folder
     * 
     * @param int $idParentFolder Parent Folder ID
     * @param array $folderArray Array of folders
     * @return array Found folders that are childer of the given parent folder ID
     */
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