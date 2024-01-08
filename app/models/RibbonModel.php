<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Ribbon;

class RibbonModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
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

        if($row['is_visible'] == 1) {
            $visible = true;
        }

        if(isset($row['id_parent_folder'])) {
            $idParentRibbon = $row['id_parent_ribbon'];
        }

        if(isset($row['image'])) {
            $image = $row['image'];
        }

        if(isset($row['title'])) {
            $title = $row['title'];
        }

        return new Ribbon($id, $name, $title, $idParentRibbon, $image, $visible, $pageUrl);
    }
}

?>