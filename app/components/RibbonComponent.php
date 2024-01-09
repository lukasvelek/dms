<?php

namespace DMS\Components;

use DMS\Authorizators\RibbonAuthorizator;
use DMS\Constants\CacheCategories;
use DMS\Core\CacheManager;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Ribbon;
use DMS\Models\RibbonModel;

class RibbonComponent extends AComponent {
    private RibbonModel $ribbonModel;
    private RibbonAuthorizator $ribbonAuthorizator;

    public function __construct(Database $db, Logger $logger, RibbonModel $ribbonModel, RibbonAuthorizator $ribbonAuthorizator) {
        parent::__construct($db, $logger);

        $this->ribbonModel = $ribbonModel;
        $this->ribbonAuthorizator = $ribbonAuthorizator;
    }

    public function getRibbonVisibleToUser(int $idUser, int $idRibbon) {
        /*$cm = CacheManager::getTemporaryObject(CacheCategories::RIBBONS);

        $valFromCache = $cm->loadRibbons();
        
        if(!is_null($valFromCache)) {
            //$ribbons = $valFromCache;

            foreach($valFromCache as $k1 => $v1) {
                if($)
            }
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

        return $visibleRibbons;*/

        $cm = CacheManager::getTemporaryObject(CacheCategories::RIBBONS);

        $valFromCache = $cm->loadRibbons();

        $visibleRibbon = null;

        if(!is_null($valFromCache)) {
            foreach($valFromCache as $ribbon) {
                if($ribbon instanceof Ribbon) {
                    if($ribbon->getId() == $idRibbon) {
                        $visibleRibbon = $ribbon;
                    }
                } else if(is_array($ribbon)) {
                    foreach($ribbon as $r) {
                        if($r instanceof Ribbon) {
                            if($r->getId() == $idRibbon) {
                                $visibleRibbon = $ribbon;
                            }
                        }
                    }
                }
            }
        }

        if($visibleRibbon === NULL) {
            $visibleRibbon = $this->ribbonModel->getRibbonById($idRibbon);

            $cm->saveRibbon($visibleRibbon);
        }

        $result = $this->ribbonAuthorizator->checkRibbonVisible($idUser, $visibleRibbon);

        return $result;
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
            $result = $this->ribbonAuthorizator->checkRibbonVisible($idUser, $ribbon);

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
            $result = $this->ribbonAuthorizator->checkRibbonVisible($idUser, $ribbon);

            if($result === TRUE) {
                $visibleRibbons[] = $ribbon;
            }
        }

        return $visibleRibbons;
    }

    public function getSiblingRibbonsVisibleToUser(int $idUser, int $idRibbon) {
        $cm = CacheManager::getTemporaryObject(CacheCategories::RIBBONS);

        $valFromCache = $cm->loadSiblingRibbons($idRibbon);

        if(!is_null($valFromCache)) {
            $ribbons = $valFromCache;
        } else {
            $ribbon = $this->ribbonModel->getRibbonById($idRibbon);
            $idParentRibbon = $ribbon->getIdParentRibbon();

            $ribbons = $this->ribbonModel->getRibbonsForIdParentRibbon($idParentRibbon);

            foreach($ribbons as $ribbon) {
                $cm->saveRibbon($ribbon);
            }
        }

        var_dump($valFromCache);

        $visibleRibbons = [];
        foreach($ribbons as $ribbon) {
            $result = $this->ribbonAuthorizator->checkRibbonVisible($idUser, $ribbon);

            if($result === TRUE) {
                $visibleRibbons[] = $ribbon;
            }
        }

        return $visibleRibbons;
    }
}

?>