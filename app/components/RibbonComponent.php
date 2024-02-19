<?php

namespace DMS\Components;

use DMS\Authorizators\RibbonAuthorizator;
use DMS\Constants\CacheCategories;
use DMS\Core\CacheManager;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Ribbon;
use DMS\Models\RibbonModel;

/**
 * Component that contains useful functions for ribbons
 * 
 * @author Lukas Velek
 */
class RibbonComponent extends AComponent {
    private RibbonModel $ribbonModel;
    private RibbonAuthorizator $ribbonAuthorizator;

    /**
     * Class constructor
     * 
     * @param Database $db Database instance
     * @param Logger $logger Logger instance
     * @param RibbonModel $ribbonModel RibbonModel instance
     * @param RibbonAuthorizator $ribbonAuthorizator RibbonAuthorizator instance
     */
    public function __construct(Database $db, Logger $logger, RibbonModel $ribbonModel, RibbonAuthorizator $ribbonAuthorizator) {
        parent::__construct($db, $logger);

        $this->ribbonModel = $ribbonModel;
        $this->ribbonAuthorizator = $ribbonAuthorizator;
    }

    /**
     * Checks if a given ribbon is visible by a given user
     * 
     * @param int $idUser User ID
     * @param int $idRibbon Ribbon ID
     * @return bool True if the ribbon is visible or false if not
     */
    public function getRibbonVisibleToUser(int $idUser, int $idRibbon) {
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

    /**
     * Returns all visible toppanels (top-tier ribbons) for a given user
     * 
     * @param int $idUser User ID
     * @return array Ribbon instances array
     */
    public function getToppanelRibbonsVisibleToUser(int $idUser) {
        $cm = CacheManager::getTemporaryObject(CacheCategories::RIBBONS);

        $valFromCache = $cm->loadRibbons();

        $ribbons = null;
        if(!is_null($valFromCache)) {
            if(array_key_exists('null', $valFromCache)) {
                $ribbons = $valFromCache['null'];
            }
        } else {
            $ribbons = $this->ribbonModel->getToppanelRibbons();

            foreach($ribbons as $ribbon) {
                $cm->saveRibbon($ribbon);
            }
        }

        $visibleRibbons = [];
        if(!is_null($ribbons)) {
            foreach($ribbons as $ribbon) {
                $result = $this->ribbonAuthorizator->checkRibbonVisible($idUser, $ribbon);
    
                if($result === TRUE) {
                    $visibleRibbons[] = $ribbon;
                }
            }
        }

        return $visibleRibbons;
    }

    /**
     * Returns all children ribbon for a given ID parent ribbon that are visible by ID user
     * 
     * @param int $idUser User ID
     * @param int $idParentRibbon Parent Ribbon ID
     * @return array Ribbon instances array
     */
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
        if(!is_null($ribbons)) {
            foreach($ribbons as $ribbon) {
                $result = $this->ribbonAuthorizator->checkRibbonVisible($idUser, $ribbon);
    
                if($result === TRUE) {
                    $visibleRibbons[] = $ribbon;
                }
            }
        }

        return $visibleRibbons;
    }

    /**
     * Returns all sibling ribbons for a given ID ribbon that are visible by a given ID user
     * 
     * @param int $idUser User ID
     * @param int $idRibbon Ribbon ID
     * @return array Ribbon instances array
     */
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

        $visibleRibbons = [];
        if(!is_null($ribbons)) {
            foreach($ribbons as $ribbon) {
                $result = $this->ribbonAuthorizator->checkRibbonVisible($idUser, $ribbon);
    
                if($result === TRUE) {
                    $visibleRibbons[] = $ribbon;
                }
            }
        }

        return $visibleRibbons;
    }
}

?>