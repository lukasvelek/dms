<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Folder;

class FolderModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function insertNewFolder(string $name, ?string $description, ?int $idParentFolder) {
        $qb = $this->qb(__METHOD__);

        $keys = array(
            'name'
        );
        $values = array(
            ':name'
        );
        $params = array(
            ':name' => $name
        );

        if(!is_null($description)) {
            $keys[] = 'description';
            $values[] = ':description';
            $params[':description'] = $description;
        }

        if(!is_null($idParentFolder)) {
            $keys[] = 'id_parent_folder';
            $values[] = ':id_parent_folder';
            $params[':id_parent_folder'] = $idParentFolder;
        }

        $result = $qb->insertArr('folders', $keys)
                     ->valuesArr($values)
                     ->setParams($params)
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function getAllFolders() {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('folders')
                   ->execute()
                   ->fetch();

        $folders = [];
        foreach($rows as $row) {
            $folders[] = $this->createFolderObjectFromDbRow($row);
        }

        return $folders;
    }

    public function getFoldersForIdParentFolder(?int $idFolder) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('folders');

        if(is_null($idFolder)) {
            $rows = $rows->whereNull('id_parent_folder');
        } else {
            $rows = $rows->where('id_parent_folder=:id_folder')
                         ->setParam(':id_folder', $idFolder);
        }

        $rows = $rows->execute()->fetch();

        $folders = [];
        foreach($rows as $row) {
            $folders[] = $this->createFolderObjectFromDbRow($row);
        }

        return $folders;
    }

    public function getFolderById(int $id) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('folders')
                  ->where('id=:id')
                  ->setParam(':id', $id)
                  ->execute()
                  ->fetchSingle();

        return $this->createFolderObjectFromDbRow($row);
    }

    private function createFolderObjectFromDbRow($row) {
        $name = $row['name'];
        $id = $row['id'];
        $dateCreated = $row['date_created'];
        $idParentFolder = null;
        $description = null;

        if(isset($row['id_parent_folder'])) {
            $idParentFolder = $row['id_parent_folder'];
        }

        if(isset($row['description'])) {
            $description = $row['description'];
        }

        return new Folder($id, $dateCreated, $idParentFolder, $name, $description);
    }
}

?>