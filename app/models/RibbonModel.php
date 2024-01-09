<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Ribbon;

class RibbonModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getRibbonById(int $id) {
        $qb = $this->composeStandardRibbonQuery(__METHOD__);

        $row = $qb ->where('id=:id')
                   ->setParam(':id', $id)
                   ->execute()
                   ->fetchSingle();

        return $this->createRibbonObjectFromDbRow($row);
    }

    public function getRibbonByCode(string $code) {
        $qb = $this->composeStandardRibbonQuery(__METHOD__);

        $row = $qb ->where('code=:code')
                   ->setParam(':code', $code)
                   ->execute()
                   ->fetchSingle();

        return $this->createRibbonObjectFromDbRow($row);
    }

    public function getRibbonsForIdParentRibbon(int $idParentRibbon) {
        $qb = $this->composeStandardRibbonQuery(__METHOD__);

        $rows = $qb ->where('id_parent_ribbon=:id_parent_ribbon')
                    ->setParam(':id_parent_ribbon', $idParentRibbon)
                    ->execute()
                    ->fetch();

        return $this->createRibbonObjectsFromDbRows($rows);
    }

    public function getToppanelRibbons() {
        $qb = $this->composeStandardRibbonQuery(__METHOD__);

        $rows = $qb ->whereNull('id_parent_ribbon')
                    ->execute()
                    ->fetch();

        return $this->createRibbonObjectsFromDbRows($rows);
    }

    public function getAllRibbons(bool $includeInvisibleRibbons = false) {
        $qb = $this->composeStandardRibbonQuery(__METHOD__);

        if(!$includeInvisibleRibbons) {
            $qb ->where('is_visible=:visible')
                ->setParam(':visible', '1');
        }

        $rows = $qb->execute()->fetch();

        return $this->createRibbonObjectsFromDbRows($rows);
    }

    private function composeStandardRibbonQuery(string $callingMethod = __METHOD__) {
        $qb = $this->qb($callingMethod)
            ->select('*')
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
        $id = $row['id'];
        $name = $row['name'];
        $visible = false;
        $pageUrl = $row['page_url'];
        $idParentRibbon = null;
        $image = null;
        $title = null;
        $code = $row['code'];

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

        return new Ribbon($id, $name, $title, $idParentRibbon, $image, $visible, $pageUrl, $code);
    }
}

?>