<?php

namespace DMS\Models;

use DMS\Constants\Metadata\FolderMetadata;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Folder;

class FolderModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getFolderByOrderAndParentFolder(?int $idFolder, int $order) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('folders')
            ->where(FolderMetadata::ORDER . ' = ?', [$order]);

        if($idFolder === NULL) {
            $qb->andWhere(FolderMetadata::ID_PARENT_FOLDER . ' IS NULL');
        } else {
            $qb->andWhere(FolderMetadata::ID_PARENT_FOLDER . ' = ?', [$idFolder]);
        }

        $qb ->limit(1)
            ->execute();

        return $this->createFolderObjectFromDbRow($qb->fetch());
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
            ->where(FolderMetadata::ID . ' = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function getLastInsertedFolder() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('folders')
            ->orderBy(FolderMetadata::ID, 'DESC')
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
            ->orderBy(FolderMetadata::ORDER)
            ->execute();

        $folders = [];
        while($row = $qb->fetchAssoc()) {
            $folders[] = $this->createFolderObjectFromDbRow($row);
        }

        return $folders;
    }

    public function getFoldersForIdParentFolder(?int $idFolder, bool $orderByOrder = false) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('folders');

        if(is_null($idFolder)) {
            $qb->where(FolderMetadata::ID_PARENT_FOLDER . ' IS NULL');
        } else {
            $qb->where(FolderMetadata::ID_PARENT_FOLDER . ' = ?', [$idFolder]);
        }

        if($orderByOrder === TRUE) {
            $qb->orderBy(FolderMetadata::ORDER);
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
            ->where(FolderMetadata::ID . ' = ?', [$id])
            ->execute();

        return $this->createFolderObjectFromDbRow($qb->fetch());
    }

    private function createFolderObjectFromDbRow($row) {
        if($row === NULL) {
            return null;
        }
        
        $name = $row[FolderMetadata::NAME];
        $id = $row[FolderMetadata::ID];
        $dateCreated = $row[FolderMetadata::DATE_CREATED];
        $idParentFolder = null;
        $description = null;
        $nestLevel = $row[FolderMetadata::NEST_LEVEL];
        $order = $row[FolderMetadata::ORDER];

        if(isset($row[FolderMetadata::ID_PARENT_FOLDER])) {
            $idParentFolder = $row[FolderMetadata::ID_PARENT_FOLDER];
        }

        if(isset($row[FolderMetadata::DESCRIPTION])) {
            $description = $row[FolderMetadata::DESCRIPTION];
        }

        return new Folder($id, $dateCreated, $idParentFolder, $name, $description, $nestLevel, $order);
    }
}

?>