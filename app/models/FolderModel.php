<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Folder;

class FolderModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function updateFolder(int $idFolder, array $data) {
        return $this->updateExisting('folders', $idFolder, $data);
    }

    public function getFolderCount() {
        return $this->getRowCount('folders');
    }

    public function deleteFolder(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('folders')
            ->where('id = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function getLastInsertedFolder() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('folders')
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->execute();

        return $this->createFolderObjectFromDbRow($qb->fetch());
    }

    public function insertNewFolder(array $data) {
        return $this->insertNew($data, 'folders');
    }

    public function getAllFolders() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('folders')
            ->execute();

        $folders = [];
        while($row = $qb->fetchAssoc()) {
            $folders[] = $this->createFolderObjectFromDbRow($row);
        }

        return $folders;
    }

    public function getFoldersForIdParentFolder(?int $idFolder) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('folders');

        if(is_null($idFolder)) {
            $qb->where('id_parent_folder IS NULL');
        } else {
            $qb->where('id_parent_folder = ?', [$idFolder]);
        }

        $qb->execute();

        $folders = [];
        while($row = $qb->fetchAssoc()) {
            $folders[] = $this->createFolderObjectFromDbRow($row);
        }

        return $folders;
    }

    public function getFolderById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('folders')
            ->where('id = ?', [$id])
            ->execute();

        return $this->createFolderObjectFromDbRow($qb->fetch());
    }

    private function createFolderObjectFromDbRow($row) {
        if($row === NULL) {
            return null;
        }
        
        $name = $row['name'];
        $id = $row['id'];
        $dateCreated = $row['date_created'];
        $idParentFolder = null;
        $description = null;
        $nestLevel = $row['nest_level'];

        if(isset($row['id_parent_folder'])) {
            $idParentFolder = $row['id_parent_folder'];
        }

        if(isset($row['description'])) {
            $description = $row['description'];
        }

        return new Folder($id, $dateCreated, $idParentFolder, $name, $description, $nestLevel);
    }
}

?>