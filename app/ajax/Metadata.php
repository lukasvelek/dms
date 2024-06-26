<?php

use DMS\Constants\UserActionRights;
use DMS\Core\AppConfiguration;
use DMS\Exceptions\AException;
use DMS\Exceptions\ValueIsNullException;
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
    throw new ValueIsNullException('$action');
}

try {
    echo($action());
} catch(AException $e) {
    echo('<b>Exception: </b>' . $e->getMessage() . '<br><b>Stack trace: </b>' . $e->getTraceAsString());
    exit;
}

function getMetadata() {
    global $metadataModel, $user, $actionAuthorizator, $metadataAuthorizator, $gridSize;

    if($user === NULL) {
        die();
    }
    $idUser = $user->getId();

    $page = 1;
    if(isset($_GET['page'])) {
        $page = (int)$_GET['page'];
    }
    $page -= 1;

    $returnArray = [];

    $canDeleteMetadata = $actionAuthorizator->checkActionRight(UserActionRights::DELETE_METADATA, null, false);
    $canEditMetadata = $actionAuthorizator->checkActionRight(UserActionRights::EDIT_METADATA, null, false);
    $canEditMetadataValues = $actionAuthorizator->checkActionRight(UserActionRights::EDIT_METADATA_VALUES, null, false);
    $canEditUserMetadataRights = $actionAuthorizator->checkActionRight(UserActionRights::EDIT_USER_METADATA_RIGHTS, null, false);

    $idsEditableMetadata = $metadataAuthorizator->getEditableMetadataForIdUser($idUser);
    $idsMetadataViewMetadataValues = $metadataAuthorizator->getViewMetadataForIdUser($idUser);
    $idsViewableMetadata = $metadataAuthorizator->getViewableMetadataForIdUser($idUser);

    $data = function() use ($metadataModel, $idsViewableMetadata, $gridSize, $page) {
        return $metadataModel->getAllViewableMetadataWithOffset($idsViewableMetadata, $gridSize, ($page * $gridSize));
    };

    $gb = new GridBuilder();
        
    $gb->addColumns(['name' => 'Name', 'text' => 'Text', 'dbTable' => 'Database table', 'inputType' => 'Input type']);
    $gb->addDataSourceCallback($data);
    $gb->addOnColumnRender('dbTable', function (\DMS\Entities\Metadata $metadata) {
        return $metadata->getTableName();
    });
    $gb->addAction(function(\DMS\Entities\Metadata $metadata) use ($idsEditableMetadata, $canDeleteMetadata) {
        $link = '-';
        if(in_array($metadata->getId(), $idsEditableMetadata) &&
           $canDeleteMetadata &&
           !$metadata->getIsSystem()) {
            $link = LinkBuilder::createAdvLink(array('page' => 'UserModule:Settings:deleteMetadata', 'id' => $metadata->getId()), 'Delete');
        }
        return $link;
    });
    $gb->addAction(function(\DMS\Entities\Metadata $metadata) use ($idsEditableMetadata, $canEditMetadata) {
        $link = '-';
        if(in_array($metadata->getId(), $idsEditableMetadata) &&
           $canEditMetadata) {
           $link = LinkBuilder::createAdvLink(array('page' => 'UserModule:Settings:showEditMetadataForm', 'id_metadata' => $metadata->getId()), 'Edit');
        }
        return $link;
    });
    $gb->addAction(function(\DMS\Entities\Metadata $metadata) use ($idsMetadataViewMetadataValues, $canEditMetadataValues) {
        $link = '-';
        if((in_array($metadata->getInputType(), ['select', 'select_external'])) &&
           in_array($metadata->getId(), $idsMetadataViewMetadataValues) &&
           $canEditMetadataValues) {
            $link = LinkBuilder::createAdvLink(array('page' => 'UserModule:Metadata:showValues', 'id' => $metadata->getId()), 'Values');
        }
        return $link;
    });
    $gb->addAction(function(\DMS\Entities\Metadata $metadata) use ($canEditUserMetadataRights) {
        $link = '-';
        if($canEditUserMetadataRights) {
            $link = LinkBuilder::createAdvLink(array('page' => 'UserModule:Metadata:showUserRights', 'id_metadata' => $metadata->getId()), 'User rights');
        }
        return $link;
    });

    $returnArray['grid'] = $gb->build();
    $returnArray['controls'] = _createGridPageControls($page + 1, $idsViewableMetadata);

    return json_encode($returnArray);
}

function _createGridPageControls(int $page, array $idsViewable) {
    global $metadataModel;
    $totalCount = $metadataModel->getAllViewableMetadataCount($idsViewable);

    $pageControl = '';

    $firstPageLink = '<button id="grid-first-page-control-btn" type="button" onclick="loadMetadata(\'';
    $previousPageLink = '<button id="grid-previous-page-control-btn" type="button" onclick="loadMetadata(\'';
    $nextPageLink = '<button id="grid-next-page-control-btn" type="button" onclick="loadMetadata(\'';
    $lastPageLink = '<button id="grid-last-page-control-btn" type="button" onclick="loadMetadata(\'';

    $pageCheck = $page - 1;

    $firstPageLink .= '1\')"';
    $firstPageLink .= '>&lt;&lt;</button>';

    if($page >= 2) {
        $previousPageLink .= ($page - 1) . '\')';
    } else {
        $previousPageLink .= '1\')';
    }
    $previousPageLink .= '"';
    $previousPageLink .= '>&lt;</button>';

    if($page < ceil($totalCount / AppConfiguration::getGridSize())) {
        $nextPageLink .= ($page + 1) . '\')';
    } else if($totalCount == 0) {
        $nextPageLink .= '1\')';
    } else {
        $nextPageLink .= ceil($totalCount / AppConfiguration::getGridSize()) . '\')';
    }
    $nextPageLink .= '"';
    $nextPageLink .= '>&gt;</button>';

    if($totalCount == 0) {
        $lastPageLink .= '1\')';
    } else {
        $lastPageLink .= ceil($totalCount / AppConfiguration::getGridSize()) . '\')';
    }
    $lastPageLink .= '"';
    $lastPageLink .= '>&gt;&gt;</button>';

    $pageControl = 'Total count: ' . $totalCount . ' | ';
    if($totalCount > AppConfiguration::getGridSize()) {
        if($pageCheck * AppConfiguration::getGridSize() >= $totalCount) {
            $pageControl .= (1 + ($page * AppConfiguration::getGridSize()));
        } else {
            $from = 1 + ($pageCheck * AppConfiguration::getGridSize());
            $to = AppConfiguration::getGridSize() + ($pageCheck * AppConfiguration::getGridSize());

            if($to > $totalCount) {
                $to = $totalCount;
            }

            $pageControl .= $from . '-' . $to;
        }
    } else {
        $pageControl = 'Total count: ' . $totalCount;
    }
    $pageControl .= ' | ' . $firstPageLink . ' ' . $previousPageLink . ' ' . $nextPageLink . ' ' . $lastPageLink;

    return $pageControl;
}

?>