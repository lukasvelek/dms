<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Ribbon;

class RibbonModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getSplitterCountForIdParent(int $idParent) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(id) AS cnt'])
            ->from('ribbons')
            ->where('id_parent_ribbon = ?', [$idParent])
            ->andWhere('name = ?', ['SPLITTER'])
            ->execute();

        return $qb->fetch('cnt');
    }

    public function deleteRibbonForIdDocumentFilter(int $idFilter) {
        $qb = $this->qb(__METHOD__);

        $code = 'documents.custom_filter.' . $idFilter;

        $qb ->delete()
            ->from('ribbons')
            ->where('code = ?', [$code])
            ->execute();

        return $qb->fetchAll();
    }

    public function getRibbonForIdDocumentFilter(int $idFilter) {
        $qb = $this->composeStandardRibbonQuery(__METHOD__);

        $code = 'documents.custom_filter.' . $idFilter;

        $qb ->andWhere('code = ?', [$code])
            ->execute();

        return $this->createRibbonObjectFromDbRow($qb->fetch());
    }

    public function deleteRibbon(int $idRibbon) {
        return $this->deleteById($idRibbon, 'ribbons');
    }

    public function updateRibbon(int $idRibbon, array $data) {
        return $this->updateExisting('ribbons', $idRibbon, $data);
    }

    public function getLastInsertedRibbonId() {
        $row = $this->getLastInsertedRow('ribbons');

        if($row === FALSE || is_null($row)) {
            return false;
        }

        return $row['id'];
    }

    public function insertNewRibbon(array $data) {
        return $this->insertNew($data, 'ribbons');
    }

    public function getRibbonById(int $id) {
        $qb = $this->composeStandardRibbonQuery(__METHOD__);

        $qb ->andWhere('id = ?', [$id])
            ->execute();

        return $this->createRibbonObjectFromDbRow($qb->fetch());
    }

    public function getRibbonByCode(string $code) {
        $qb = $this->composeStandardRibbonQuery(__METHOD__);

        $qb ->andWhere('code = ?', [$code])
            ->execute();

        return $this->createRibbonObjectFromDbRow($qb->fetch());
    }

    public function getRibbonsForIdParentRibbon(int $idParentRibbon) {
        $qb = $this->composeStandardRibbonQuery(__METHOD__);

        $qb ->andWhere('id_parent_ribbon = ?', [$idParentRibbon])
            ->execute();

        return $this->createRibbonObjectsFromDbRows($qb->fetchAll());
    }

    public function getToppanelRibbons() {
        $qb = $this->composeStandardRibbonQuery(__METHOD__);

        $qb ->andWhere('id_parent_ribbon IS NULL')
            ->execute();

        return $this->createRibbonObjectsFromDbRows($qb->fetchAll());
    }

    public function getAllRibbons(bool $includeInvisibleRibbons = false) {
        $qb = $this->composeStandardRibbonQuery(__METHOD__);

        if(!$includeInvisibleRibbons) {
            $qb ->andWhere('is_visible = 1');
        }

        $qb->execute();

        return $this->createRibbonObjectsFromDbRows($qb->fetchAll());
    }

    private function composeStandardRibbonQuery(string $callingMethod = __METHOD__) {
        $qb = $this->qb($callingMethod)
            ->select(['*'])
            ->from('ribbons');

        return $qb;
    }

    private function createRibbonObjectsFromDbRows($rows) {
        $objects = [];
        foreach($rows as $row) {
            $objects[] = $this->createRibbonObjectFromDbRow($row);
        }

        return $objects;
    }

    private function createRibbonObjectFromDbRow($row) {
        if($row === FALSE || $row === NULL) {
            return null;
        }

        $id = $row['id'];
        $name = $row['name'];
        $visible = false;
        $pageUrl = $row['page_url'];
        $idParentRibbon = null;
        $image = null;
        $title = null;
        $code = $row['code'];
        $system = $row['is_system'] ? true : false;

        if($row['is_visible'] == 1) {
            $visible = true;
        }

        if(isset($row['id_parent_ribbon'])) {
            $idParentRibbon = $row['id_parent_ribbon'];
        }

        if(isset($row['image'])) {
            $image = $row['image'];
        }

        if(isset($row['title'])) {
            $title = $row['title'];
        }

        return new Ribbon($id, $name, $title, $idParentRibbon, $image, $visible, $pageUrl, $code, $system);
    }
}

?>