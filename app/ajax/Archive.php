<?php

use DMS\Constants\ArchiveType;
use DMS\Constants\UserActionRights;
use DMS\Entities\Archive;
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

function getDocuments() {
    global $archiveModel, $actionAuthorizator, $gridSize, $archiveAuthorizator;
    
    $page = 1;

    if(isset($_GET['page'])) {
        $page = (int)(htmlspecialchars($_GET['page']));
    }

    $dataSourceCallback = function() use ($archiveModel, $page, $gridSize) {
        $page -= 1;

        $firstIdOnPage = $archiveModel->getFirstIdEntityOnAGridPage(($page * $gridSize), ArchiveType::DOCUMENT);

        return $archiveModel->getAllDocumentsFromId($firstIdOnPage, $gridSize);
    };

    $gb = new GridBuilder();

    $gb->addColumns(['name' => 'Name']);
    $gb->addDataSourceCallback($dataSourceCallback);
    $gb->addAction(function(Archive $archive) use ($actionAuthorizator) {
        $link = '-';
        if($actionAuthorizator->checkActionRight(UserActionRights::VIEW_ARCHIVE_DOCUMENT_CONTENT, null, false)) {
            $link = LinkBuilder::createAdvLink(['page' => 'UserModule:SingleArchive:showContent', 'id' => $archive->getId()], 'Open');
        }
        return $link;
    });
    $gb->addAction(function(Archive $archive) use ($actionAuthorizator) {
        $link = '-';
        if($actionAuthorizator->checkActionRight(UserActionRights::EDIT_ARCHIVE_DOCUMENT, null, false)) {
            $link = LinkBuilder::createAdvLink(['page' => 'UserModule:SingleArchive:showEditForm', 'id' => $archive->getId()], 'Edit');
        }
        return $link;
    });
    $gb->addAction(function(Archive $archive) use ($actionAuthorizator, $archiveAuthorizator) {
        $link = '-';
        if($actionAuthorizator->checkActionRight(UserActionRights::DELETE_ARCHIVE_DOCUMENT, null, false) &&
           $archiveAuthorizator->canDeleteDocument($archive)) {
            $link = LinkBuilder::createAdvLink(['page' => 'UserModule:Archive:deleteDocument', 'id' => $archive->getId()], 'Delete');
        }
        return $link;
    });

    return $gb->build();
}

?>