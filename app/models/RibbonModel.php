<?php

namespace DMS\Models;

use DMS\Constants\Metadata\RibbonMetadata;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Ribbon;

class RibbonModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getSplitterCountForIdParent(int $idParent) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(' . RibbonMetadata::ID . ') AS cnt'])
            ->from('ribbons')
            ->where(RibbonMetadata::ID_PARENT_RIBBON . ' = ?', [$idParent])
            ->andWhere(RibbonMetadata::NAME . ' = ?', ['SPLITTER'])
            ->execute();

        return $qb->fetch('cnt');
    }

    public function deleteRibbonForIdDocumentFilter(int $idFilter) {
        $qb = $this->qb(__METHOD__);

        $code = 'documents.custom_filter.' . $idFilter;

        $qb ->delete()
            ->from('ribbons')
            ->where(RibbonMetadata::CODE . ' = ?', [$code])
            ->execute();

        return $qb->fetchAll();
    }

    public function getIdRibbonsForDocumentFilters() {
        $qb = $this->composeStandardRibbonQuery(__METHOD__);

        $code = 'documents.custom_filter.%';

        $qb ->andWhere(RibbonMetadata::CODE . ' LIKE ?', [$code])
            ->execute();

        $ids = [];
        while($row = $qb->fetchAssoc()) {
            $code = $row[RibbonMetadata::CODE];

            $id = explode('.', $code)[2];
            $ids[] = $id;
        }

        return $ids;
    }

    public function getRibbonForIdDocumentFilter(int $idFilter) {
        $qb = $this->composeStandardRibbonQuery(__METHOD__);

        $code = 'documents.custom_filter.' . $idFilter;

        $qb ->andWhere(RibbonMetadata::CODE . ' = ?', [$code])
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

        return $row[RibbonMetadata::ID];
    }

    public function insertNewRibbon(array $data) {
        return $this->insertNew($data, 'ribbons');
    }

    public function getRibbonById(int $id) {
        $qb = $this->composeStandardRibbonQuery(__METHOD__);

        $qb ->andWhere(RibbonMetadata::ID . ' = ?', [$id])
            ->execute();

        return $this->createRibbonObjectFromDbRow($qb->fetch());
    }

    public function getRibbonByCode(string $code) {
        $qb = $this->composeStandardRibbonQuery(__METHOD__);

        $qb ->andWhere(RibbonMetadata::CODE . ' = ?', [$code])
            ->execute();

        return $this->createRibbonObjectFromDbRow($qb->fetch());
    }

    public function getRibbonsForIdParentRibbon(int $idParentRibbon) {
        $qb = $this->composeStandardRibbonQuery(__METHOD__);

        $qb ->andWhere(RibbonMetadata::ID_PARENT_RIBBON . ' = ?', [$idParentRibbon])
            ->execute();

        return $this->createRibbonObjectsFromDbRows($qb->fetchAll());
    }

    public function getToppanelRibbons() {
        $qb = $this->composeStandardRibbonQuery(__METHOD__);

        $qb ->andWhere(RibbonMetadata::ID_PARENT_RIBBON . ' IS NULL')
            ->execute();

        return $this->createRibbonObjectsFromDbRows($qb->fetchAll());
    }

    public function getAllRibbons(bool $includeInvisibleRibbons = false) {
        $qb = $this->composeStandardRibbonQuery(__METHOD__);

        if(!$includeInvisibleRibbons) {
            $qb ->andWhere(RibbonMetadata::IS_VISIBLE . ' = 1');
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

        $id = $row[RibbonMetadata::ID];
        $name = $row[RibbonMetadata::NAME];
        $visible = false;
        $pageUrl = $row[RibbonMetadata::PAGE_URL];
        $idParentRibbon = null;
        $image = null;
        $title = null;
        $code = $row[RibbonMetadata::CODE];
        $system = $row[RibbonMetadata::IS_SYSTEM] ? true : false;

        if($row[RibbonMetadata::IS_VISIBLE] == 1) {
            $visible = true;
        }

        if(isset($row[RibbonMetadata::ID_PARENT_RIBBON])) {
            $idParentRibbon = $row[RibbonMetadata::ID_PARENT_RIBBON];
        }

        if(isset($row[RibbonMetadata::IMAGE])) {
            $image = $row[RibbonMetadata::IMAGE];
        }

        if(isset($row[RibbonMetadata::TITLE])) {
            $title = $row[RibbonMetadata::TITLE];
        }

        return new Ribbon($id, $name, $title, $idParentRibbon, $image, $visible, $pageUrl, $code, $system);
    }
}

?>