<?php

use DMS\Constants\FileStorageTypes;
use DMS\Core\FileStorageManager;
use DMS\Entities\FileStorageLocation;
use DMS\Helpers\GridDataHelper;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;

require_once('Ajax.php');

$action = null;

if(isset($_GET['action'])) {
    $action = htmlspecialchars($_GET['action']);
} else if(isset($_POST['action'])) {
    $action = htmlspecialchars($_POST['action']);
}

if($action == null) {
    exit;
}

echo($action());

function getLocationsGrid() {
    global $fm, $logger, $fileStorageModel;
    $fsManager = new FileStorageManager($fm, $logger, $fileStorageModel);

    $locations = $fileStorageModel->getAllFileStorageLocations(true);
    $locationCount = count($locations);

    $gb = new GridBuilder();
    
    $gb->addColumns(['order' => 'Order', 'name' => 'Name', 'path' => 'Path', 'type' => 'Type', 'isDefault' => 'Default', 'isActive' => 'Active', 'freeSpace' => 'Free space', 'fileCount' => 'Files stored']);
    $gb->addDataSource($locations);
    $gb->addOnColumnRender('isDefault', function(FileStorageLocation $loc) {
        return GridDataHelper::renderBooleanValueWithColors($loc->isDefault(), 'Yes', 'No');
    });
    $gb->addOnColumnRender('isActive', function(FileStorageLocation $loc) {
        return GridDataHelper::renderBooleanValueWithColors($loc->isActive(), 'Yes', 'No');
    });
    $gb->addOnColumnRender('freeSpace', function(FileStorageLocation $loc) use ($fsManager) {
        return $fsManager->getFreeSpaceLeft($loc->getPath());
    });
    $gb->addOnColumnRender('type', function(FileStorageLocation $loc) {
        return FileStorageTypes::$texts[$loc->getType()];
    });
    $gb->addOnColumnRender('fileCount', function(FileStorageLocation $loc) use ($fsManager) {
        return count($fsManager->getStoredFilesInDirectory($loc->getPath()));
    });
    $gb->addAction(function(FileStorageLocation $loc) use ($locationCount) {
        if($locationCount > 1) {
            if($loc->getOrder() < $locationCount) {
                return LinkBuilder::createAdvLink(['page' => 'UserModule:FileStorageSettings:moveLocationDown', 'id' => $loc->getId(), 'order' => $loc->getOrder()], '&darr;');
            }
        } else {
            return '-';
        }
    });
    $gb->addAction(function(FileStorageLocation $loc) use ($locationCount) {
        if($locationCount > 1) {
            if($loc->getOrder() > 1) {
                return LinkBuilder::createAdvLink(['page' => 'UserModule:FileStorageSettings:moveLocationUp', 'id' => $loc->getId(), 'order' => $loc->getOrder()], '&uarr;');
            }
        } else {
            return '-';
        }
    });
    $gb->addAction(function(FileStorageLocation $loc) use ($locationCount) {
        if($locationCount > 1 && $loc->isSystem() === FALSE) {
            return LinkBuilder::createAdvLink(['page' => 'UserModule:FileStorageSettings:showRemoveLocationForm', 'id' => $loc->getId()], 'Remove');
        } else {
            return '-';
        }
    });
    $gb->addAction(function(FileStorageLocation $loc) {
        if($loc->isDefault() === FALSE) {
            return LinkBuilder::createAdvLink(['page' => 'UserModule:FileStorageSettings:setLocationAsDefault', 'id' => $loc->getId(), 'type' => $loc->getType()], 'Set as default');
        } else {
            return '-';
        }
    });

    $returnArray = [];
    $returnArray['grid'] = $gb->build();

    return json_encode($returnArray);
}

?>