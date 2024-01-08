<?php

namespace DMS\Components;

use DMS\Authorizators\RibbonAuthorizator;
use DMS\Constants\CacheCategories;
use DMS\Core\CacheManager;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Models\RibbonModel;

class RibbonComponent extends AComponent {
    private RibbonModel $ribbonModel;
    private RibbonAuthorizator $ribbonAuthorizator;

    public function __construct(Database $db, Logger $logger, RibbonModel $ribbonModel, RibbonAuthorizator $ribbonAuthorizator) {
        parent::__construct($db, $logger);

        $this->ribbonModel = $ribbonModel;
        $this->ribbonAuthorizator = $ribbonAuthorizator;
    }

    public function getToppanelRibbonsVisibleToUser(int $idUser) {
        $cm = CacheManager::getTemporaryObject(CacheCategories::RIBBONS);

        $valFromCache = $cm->loadRibbons();

        if(!is_null($valFromCache)) {
            $ribbons = $valFromCache['null'];
        } else {
            $ribbons = $this->ribbonModel->getToppanelRibbons();

            foreach($ribbons as $ribbon) {
                $cm->saveRibbon($ribbon);
            }
        }

        $visibleRibbons = [];
        foreach($ribbons as $ribbon) {
            $result = $this->ribbonAuthorizator->checkRibbonVisible($idUser, $ribbon->getId());

            if($result === TRUE) {
                $visibleRibbons[] = $ribbon;
            }
        }

        return $visibleRibbons;
    }

    public function getChildrenRibbonsVisibleToUser(int $idUser, int $idParentRibbon) {
        $cm = CacheManager::getTemporaryObject(CacheCategories::RIBBONS);

        $valFromCache = $cm->loadChildrenRibbons($idParentRibbon);
        
        if(!is_null($valFromCache)) {
            $ribbons = $valFromCache;
        } else {
            $ribbons = $this->ribbonModel->getRibbonsForIdParentRibbon($idParentRibbon);

            foreach($ribbons as $ribbon) {
                $cm->saveRibbon($ribbon);
            }
        }

        $visibleRibbons = [];
        foreach($ribbons as $ribbon) {
            $result = $this->ribbonAuthorizator->checkRibbonVisible($idUser, $ribbon->getId());

            if($result === TRUE) {
                $visibleRibbons[] = $ribbon;
            }
        }

        return $visibleRibbons;
    }
}

?>