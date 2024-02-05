<?php

use DMS\Constants\ArchiveStatus;
use DMS\Constants\ArchiveType;
use DMS\Constants\BulkActionRights;
use DMS\Constants\CacheCategories;
use DMS\Constants\DocumentStatus;
use DMS\Constants\UserActionRights;
use DMS\Core\AppConfiguration;
use DMS\Core\CacheManager;
use DMS\Entities\Archive;
use DMS\Entities\Document;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;

require_once('Ajax.php');

$ucm = new CacheManager(true, CacheCategories::USERS, '../../' . AppConfiguration::getLogDir(), '../../' . AppConfiguration::getCacheDir());

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

function getArchiveBulkActions() {
    global $user, $archiveModel, $archiveAuthorizator, $actionAuthorizator, $documentModel;

    $bulkActions = [];
    $text = '';
    $canCloseArchive = null;
    $canSuggestForShredding = null;
    $canShred = null;

    $idDocuments = $_GET['idDocuments'];

    if(!is_null($user)) {
        foreach($idDocuments as $idDocument) {
            $archive = $archiveModel->getArchiveById($idDocument);

            if($archiveAuthorizator->bulkActionCloseArchive($archive, null, true, true) &&
               $actionAuthorizator->checkActionRight(UserActionRights::CLOSE_ARCHIVE, null, false) &&
               ($archiveModel->getChildrenCount($archive->getId(), ArchiveType::ARCHIVE) > 0) &&
               (is_null($canCloseArchive) || $canCloseArchive)) {
                $canCloseArchive = true;
            } else {
                $canCloseArchive = false;
            }

            if($archiveAuthorizator->bulkActionSuggestForShredding($archive, null, true, true) &&
               (is_null($canSuggestForShredding) || $canSuggestForShredding)) {
                $canSuggestForShredding = true;
            } else {
                $canSuggestForShredding = false;
            }
        }
    }

    if($canCloseArchive) {
        $link = '?page=UserModule:Archive:performBulkAction&';

        $i = 0;
        foreach($idDocuments as $idDocument) {
            if(($i + 1) == count($idDocuments)) {
                $link .= 'select[]=' . $idDocument;
            } else {
                $link .= 'select[]=' . $idDocument . '&';
            }
        }

        $link .= '&action=close_archive';

        $bulkActions['Close archive'] = $link;
    }

    if($canSuggestForShredding) {
        $link = '?page=UserModule:Archive:performBulkAction&';

        $i = 0;
        foreach($idDocuments as $idDocument) {
            if(($i + 1) == count($idDocuments)) {
                $link .= 'select[]=' . $idDocument;
            } else {
                $link .= 'select[]=' . $idDocument . '&';
            }
        }

        $link .= '&action=suggest_for_shredding';

        $bulkActions['Suggest for shredding'] = $link;
    }

    $i = 0;
    $x = 0;
    $br = 0;
    foreach($bulkActions as $name => $url) {
        if(($x + 1) % 5 == 0) {
            $br++;
            $x = 0;
        }

        if($i == 0) {
            $left = ($x * 75) + 10;
            $top = 10;

            if($name == 'br') {
                $text .= _createBlankLink($left, $top);
            } else {
                $text .= _createLink($url, $name, $left, $top);
            }
        } else {
            $nextLineTop = $br * -130;
            $left = ($x * 75) + (($x + 1) * 10);
            $top = (($x * -75) + 10) + ($br * -85) + $nextLineTop;
            
            if($name == 'br') {
                $text .= _createBlankLink($left, $top);
            } else {
                $text .= _createLink($url, $name, $left, $top);
            }
        }

        $i++;
        $x++;
    }

    return $text;
}

function getBoxBulkActions() {
    global $user, $archiveModel, $archiveAuthorizator, $actionAuthorizator, $documentModel;

    $bulkActions = [];
    $text = '';
    $canMoveBoxToArchive = null;
    $canMoveBoxFromArchive = null;

    $idDocuments = $_GET['idDocuments'];

    if(!is_null($user)) {
        foreach($idDocuments as $idDocument) {
            $archive = $archiveModel->getBoxById($idDocument);

            if($archiveAuthorizator->bulkActionMoveBoxToArchive($archive, null, true, false) &&
               $actionAuthorizator->checkActionRight(UserActionRights::MOVE_ENTITIES_WITHIN_ARCHIVE, null, false) &&
               ($archiveModel->getChildrenCount($idDocument, ArchiveType::BOX) > 0) &&
               (is_null($canMoveBoxToArchive) || $canMoveBoxToArchive)) {
                $canMoveBoxToArchive = true;
            } else {
                $canMoveBoxToArchive = false;
            }

            if($archiveAuthorizator->bulkActionMoveBoxFromArchive($archive, null, true, false) &&
              $actionAuthorizator->checkActionRight(UserActionRights::MOVE_ENTITIES_WITHIN_ARCHIVE, null, false) &&
              (is_null($canMoveBoxFromArchive) || $canMoveBoxFromArchive)) {
                $canMoveBoxFromArchive = true;
            } else {
                $canMoveBoxFromArchive = false;
            }
        }
    }

    if($canMoveBoxToArchive) {
        $link = '?page=UserModule:Archive:performBulkAction&';

        $i = 0;
        foreach($idDocuments as $idDocument) {
            if(($i + 1) == count($idDocuments)) {
                $link .= 'select[]=' . $idDocument;
            } else {
                $link .= 'select[]=' . $idDocument . '&';
            }
        }

        $link .= '&action=move_box_to_archive';

        $bulkActions['Move box to archive'] = $link;
    }

    if($canMoveBoxFromArchive) {
        $link = '?page=UserModule:Archive:performBulkAction&';

        $i = 0;
        foreach($idDocuments as $idDocument) {
            if(($i + 1) == count($idDocuments)) {
                $link .= 'select[]=' . $idDocument;
            } else {
                $link .= 'select[]=' . $idDocument . '&';
            }
        }

        $link .= '&action=move_box_from_archive';

        $bulkActions['Move box from archive'] = $link;
    }

    $i = 0;
    $x = 0;
    $br = 0;
    foreach($bulkActions as $name => $url) {
        if(($x + 1) % 5 == 0) {
            $br++;
            $x = 0;
        }

        if($i == 0) {
            $left = ($x * 75) + 10;
            $top = 10;

            if($name == 'br') {
                $text .= _createBlankLink($left, $top);
            } else {
                $text .= _createLink($url, $name, $left, $top);
            }
        } else {
            $nextLineTop = $br * -130;
            $left = ($x * 75) + (($x + 1) * 10);
            $top = (($x * -75) + 10) + ($br * -85) + $nextLineTop;
            
            if($name == 'br') {
                $text .= _createBlankLink($left, $top);
            } else {
                $text .= _createLink($url, $name, $left, $top);
            }
        }

        $i++;
        $x++;
    }

    return $text;
}

function getDocumentBulkActions() {
    global $user, $archiveModel, $archiveAuthorizator, $actionAuthorizator, $documentModel;

    $bulkActions = [];
    $text = '';
    $canMoveDocumentToBox = null;
    $canMoveDocumentFromBox = null;

    $idDocuments = $_GET['idDocuments'];

    if(!is_null($user)) {
        foreach($idDocuments as $idDocument) {
            $archive = $archiveModel->getDocumentById($idDocument);

            $parentEntity = null;
            if($archive->getIdParentArchiveEntity() !== NULL) {
                $parentEntity = $archiveModel->getBoxById($archive->getIdParentArchiveEntity());
            }

            if($archiveAuthorizator->bulkActionMoveDocumentToBox($archive, null, true, false) &&
               $actionAuthorizator->checkActionRight(UserActionRights::MOVE_ENTITIES_WITHIN_ARCHIVE, null, false) &&
               ($documentModel->getDocumentCountInArchiveDocument($idDocument) > 0) &&
               (is_null($canMoveDocumentToBox) || $canMoveDocumentToBox)) {
                $canMoveDocumentToBox = true;
            } else {
                $canMoveDocumentToBox = false;
            }

            if($archiveAuthorizator->bulkActionMoveDocumentFromBox($archive, null, true, false) &&
               $actionAuthorizator->checkActionRight(UserActionRights::MOVE_ENTITIES_WITHIN_ARCHIVE, null, false) &&
               (!is_null($parentEntity) && $parentEntity->getIdParentArchiveEntity() === NULL) &&
               (is_null($canMoveDocumentFromBox) || $canMoveDocumentFromBox)) {
                $canMoveDocumentFromBox = true;
            } else {
                $canMoveDocumentFromBox = false;
            }
        }
    }

    if($canMoveDocumentToBox) {
        $link = '?page=UserModule:Archive:performBulkAction&';

        $i = 0;
        foreach($idDocuments as $idDocument) {
            if(($i + 1) == count($idDocuments)) {
                $link .= 'select[]=' . $idDocument;
            } else {
                $link .= 'select[]=' . $idDocument . '&';
            }
        }

        $link .= '&action=move_document_to_box';

        $bulkActions['Move document to box'] = $link;
    }

    if($canMoveDocumentFromBox) {
        $link = '?page=UserModule:Archive:performBulkAction&';

        $i = 0;
        foreach($idDocuments as $idDocument) {
            if(($i + 1) == count($idDocuments)) {
                $link .= 'select[]=' . $idDocument;
            } else {
                $link .= 'select[]=' . $idDocument . '&';
            }
        }

        $link .= '&action=move_document_from_box';

        $bulkActions['Move document from box'] = $link;
    }

    $i = 0;
    $x = 0;
    $br = 0;
    foreach($bulkActions as $name => $url) {
        if(($x + 1) % 5 == 0) {
            $br++;
            $x = 0;
        }

        if($i == 0) {
            $left = ($x * 75) + 10;
            $top = 10;

            if($name == 'br') {
                $text .= _createBlankLink($left, $top);
            } else {
                $text .= _createLink($url, $name, $left, $top);
            }
        } else {
            $nextLineTop = $br * -130;
            $left = ($x * 75) + (($x + 1) * 10);
            $top = (($x * -75) + 10) + ($br * -85) + $nextLineTop;
            
            if($name == 'br') {
                $text .= _createBlankLink($left, $top);
            } else {
                $text .= _createLink($url, $name, $left, $top);
            }
        }

        $i++;
        $x++;
    }

    return $text;
}

function getDocuments() {
    global $archiveModel, $actionAuthorizator, $gridSize, $archiveAuthorizator;
    
    $page = 1;

    if(isset($_GET['page'])) {
        $page = (int)(htmlspecialchars($_GET['page']));
    }

    $dataSourceCallback = function() use ($archiveModel, $page, $gridSize) {
        $page -= 1;

        $firstIdOnPage = $archiveModel->getFirstIdDocumentOnAGridPage(($page * $gridSize));

        return $archiveModel->getAllDocumentsFromId($firstIdOnPage, $gridSize);
    };

    $gb = new GridBuilder();

    $gb->addColumns(['name' => 'Name', 'status' => 'Status']);
    $gb->addOnColumnRender('status', function(Archive $archive) {
        return ArchiveStatus::$texts[$archive->getStatus()];
    });
    $gb->addDataSourceCallback($dataSourceCallback);
    $gb->addAction(function(Archive $archive) use ($actionAuthorizator) {
        $link = '-';
        if($actionAuthorizator->checkActionRight(UserActionRights::VIEW_ARCHIVE_DOCUMENT_CONTENT, null, false)) {
            $link = LinkBuilder::createAdvLink(['page' => 'UserModule:SingleArchive:showContent', 'id' => $archive->getId(), 'type' => $archive->getType()], 'Open');
        }
        return $link;
    });
    $gb->addAction(function(Archive $archive) use ($actionAuthorizator) {
        $link = '-';
        if($actionAuthorizator->checkActionRight(UserActionRights::EDIT_ARCHIVE_DOCUMENT, null, false)) {
            $link = LinkBuilder::createAdvLink(['page' => 'UserModule:SingleArchive:showEditForm', 'id' => $archive->getId(), 'type' => $archive->getType()], 'Edit');
        }
        return $link;
    });
    $gb->addAction(function(Archive $archive) use ($actionAuthorizator, $archiveAuthorizator) {
        $link = '-';
        if($actionAuthorizator->checkActionRight(UserActionRights::DELETE_ARCHIVE_DOCUMENT, null, false) &&
           $archiveAuthorizator->canDeleteDocument($archive)) {
            $link = LinkBuilder::createAdvLink(['page' => 'UserModule:Archive:deleteDocument', 'id' => $archive->getId(), 'type' => $archive->getType()], 'Delete');
        }
        return $link;
    });
    $gb->addHeaderCheckbox('select-all', 'selectAllArchiveDocumentEntries()');
    $gb->addRowCheckbox(function(Archive $archive) {
        return '<input type="checkbox" id="select" name="select[]" value="' . $archive->getId() . '" onupdate="drawArchiveDocumentBulkActions()" onchange="drawArchiveDocumentBulkActions()">';
    });

    return $gb->build();
}

function getBoxes() {
    global $archiveModel, $actionAuthorizator, $gridSize, $archiveAuthorizator;
    
    $page = 1;

    if(isset($_GET['page'])) {
        $page = (int)(htmlspecialchars($_GET['page']));
    }

    $dataSourceCallback = function() use ($archiveModel, $page, $gridSize) {
        $page -= 1;

        $firstIdOnPage = $archiveModel->getFirstIdBoxOnAGridPage(($page * $gridSize));

        $result =  $archiveModel->getAllBoxesFromId($firstIdOnPage, $gridSize);

        return $result;
    };

    $gb = new GridBuilder();

    $gb->addColumns(['name' => 'Name', 'status' => 'Status']);
    $gb->addOnColumnRender('status', function(Archive $archive) {
        return ArchiveStatus::$texts[$archive->getStatus()];
    });
    $gb->addDataSourceCallback($dataSourceCallback);
    $gb->addAction(function(Archive $archive) use ($actionAuthorizator) {
        $link = '-';
        if($actionAuthorizator->checkActionRight(UserActionRights::VIEW_ARCHIVE_DOCUMENT_CONTENT, null, false)) {
            $link = LinkBuilder::createAdvLink(['page' => 'UserModule:SingleArchive:showContent', 'id' => $archive->getId(), 'type' => $archive->getType()], 'Open');
        }
        return $link;
    });
    $gb->addAction(function(Archive $archive) use ($actionAuthorizator) {
        $link = '-';
        if($actionAuthorizator->checkActionRight(UserActionRights::EDIT_ARCHIVE_DOCUMENT, null, false)) {
            $link = LinkBuilder::createAdvLink(['page' => 'UserModule:SingleArchive:showEditForm', 'id' => $archive->getId(), 'type' => $archive->getType()], 'Edit');
        }
        return $link;
    });
    $gb->addAction(function(Archive $archive) use ($actionAuthorizator, $archiveAuthorizator) {
        $link = '-';
        if($actionAuthorizator->checkActionRight(UserActionRights::DELETE_ARCHIVE_DOCUMENT, null, false) &&
           $archiveAuthorizator->canDeleteDocument($archive)) {
            $link = LinkBuilder::createAdvLink(['page' => 'UserModule:Archive:deleteDocument', 'id' => $archive->getId(), 'type' => $archive->getType()], 'Delete');
        }
        return $link;
    });
    $gb->addHeaderCheckbox('select-all', 'selectAllArchiveBoxEntries()');
    $gb->addRowCheckbox(function(Archive $archive) {
        return '<input type="checkbox" id="select" name="select[]" value="' . $archive->getId() . '" onupdate="drawArchiveBoxBulkActions()" onchange="drawArchiveBoxBulkActions()">';
    });

    return $gb->build();
}

function getArchives() {
    global $archiveModel, $actionAuthorizator, $gridSize, $archiveAuthorizator;
    
    $page = 1;

    if(isset($_GET['page'])) {
        $page = (int)(htmlspecialchars($_GET['page']));
    }

    $dataSourceCallback = function() use ($archiveModel, $page, $gridSize) {
        $page -= 1;

        $firstIdOnPage = $archiveModel->getFirstIdArchiveOnAGridPage(($page * $gridSize));

        return $archiveModel->getAllArchivesFromId($firstIdOnPage, $gridSize);
    };

    $gb = new GridBuilder();

    $gb->addColumns(['name' => 'Name', 'status' => 'Status']);
    $gb->addOnColumnRender('status', function(Archive $archive) {
        return ArchiveStatus::$texts[$archive->getStatus()];
    });
    $gb->addDataSourceCallback($dataSourceCallback);
    $gb->addAction(function(Archive $archive) use ($actionAuthorizator) {
        $link = '-';
        if($actionAuthorizator->checkActionRight(UserActionRights::VIEW_ARCHIVE_DOCUMENT_CONTENT, null, false)) {
            $link = LinkBuilder::createAdvLink(['page' => 'UserModule:SingleArchive:showContent', 'id' => $archive->getId(), 'type' => $archive->getType()], 'Open');
        }
        return $link;
    });
    $gb->addAction(function(Archive $archive) use ($actionAuthorizator) {
        $link = '-';
        if($actionAuthorizator->checkActionRight(UserActionRights::EDIT_ARCHIVE_DOCUMENT, null, false)) {
            $link = LinkBuilder::createAdvLink(['page' => 'UserModule:SingleArchive:showEditForm', 'id' => $archive->getId(), 'type' => $archive->getType()], 'Edit');
        }
        return $link;
    });
    $gb->addAction(function(Archive $archive) use ($actionAuthorizator, $archiveAuthorizator) {
        $link = '-';
        if($actionAuthorizator->checkActionRight(UserActionRights::DELETE_ARCHIVE_DOCUMENT, null, false) &&
           $archiveAuthorizator->canDeleteDocument($archive)) {
            $link = LinkBuilder::createAdvLink(['page' => 'UserModule:Archive:deleteDocument', 'id' => $archive->getId(), 'type' => $archive->getType()], 'Delete');
        }
        return $link;
    });
    $gb->addHeaderCheckbox('select-all', 'selectAllArchiveArchiveEntries()');
    $gb->addRowCheckbox(function(Archive $archive) {
        return '<input type="checkbox" id="select" name="select[]" value="' . $archive->getId() . '" onupdate="drawArchiveArchiveBulkActions()" onchange="drawArchiveArchiveBulkActions()">';
    });

    return $gb->build();
}

function getContent() {
    global $archiveModel;

    $page = 1;
    $id = null;
    $type = null;

    if(isset($_GET['page'])) {
        $page = (int)(htmlspecialchars($_GET['page']));
    }

    if(isset($_GET['id'])) {
        $id = htmlspecialchars($_GET['id']);
    }

    if(isset($_GET['type'])) {
        $type = htmlspecialchars($_GET['type']);
    }

    if($id === NULL) {
        return;
    }

    $content = '';
    switch($type) {
        case ArchiveType::DOCUMENT:
            $entity = $archiveModel->getDocumentById($id);
            $content = internalCreateDocumentGrid($entity, $page);
            break;
        
        case ArchiveType::BOX:
            $entity = $archiveModel->getBoxById($id);
            $content = internalCreateBoxGrid($entity, $page);
            break;
        
        case ArchiveType::ARCHIVE:
            $entity = $archiveModel->getArchiveById($id);
            $content = internalCreateArchiveGrid($entity, $page);
            break;
    }

    return $content;
}



/**
 * PRIVATE METHODS
 */

function internalCreateDocumentGrid(Archive $entity, int $page) {
    global $documentModel, $gridSize, $userModel, $ucm;

    $dataSourceCallback = function() use ($documentModel, $entity, $page, $gridSize) {
        $page -= 1;

        $firstIdOnPage = $documentModel->getFirstIdDocumentInIdArchiveDocumentOnAGridPage($entity->getId(), ($page * $gridSize));

        return $documentModel->getDocumentsInIdArchiveDocumentFromId($firstIdOnPage, $entity->getId(), $gridSize);
    };

    $gb = new GridBuilder();

    $gb->addColumns(['name' => 'Name', 'idAuthor' => 'Author', 'status' => 'Status']);
    $gb->addDataSourceCallback($dataSourceCallback);
    $gb->addOnColumnRender('status', function(Document $document) {
        return DocumentStatus::$texts[$document->getStatus()];
    });
    $gb->addOnColumnRender('idAuthor', function(Document $document) use ($userModel, $ucm) {
        $user = $ucm->loadUserByIdFromCache($document->getIdAuthor());

        if(is_null($user)) {
            $user = $userModel->getUserById($document->getIdAuthor());
        }
        
        return $user->getFullname();
    });

    return $gb->build();
}

function internalCreateBoxGrid(Archive $entity, int $page) {
    global $archiveModel, $gridSize;

    $dataSourceCallback = function() use ($archiveModel, $entity, $page, $gridSize) {
        $page -= 1;

        $firstIdOnPage = $archiveModel->getFirstIdDocumentOnAGridPage(($page * $gridSize), ArchiveType::DOCUMENT);

        return $archiveModel->getDocumentsForIdBoxFromId($firstIdOnPage, $gridSize, $entity->getId());
    };

    $gb = new GridBuilder();

    $gb->addColumns(['name' => 'Name']);
    $gb->addDataSourceCallback($dataSourceCallback);

    return $gb->build();
}

function internalCreateArchiveGrid(Archive $entity, int $page) {
    global $archiveModel, $gridSize;

    $dataSourceCallback = function() use ($archiveModel, $entity, $page, $gridSize) {
        $page -= 1;

        $firstIdOnPage = $archiveModel->getFirstIdBoxOnAGridPage(($page * $gridSize), ArchiveType::BOX);

        return $archiveModel->getBoxesForIdArchiveFromId($firstIdOnPage, $gridSize, $entity->getId());
    };

    $gb = new GridBuilder();

    $gb->addColumns(['name' => 'Name']);
    $gb->addDataSourceCallback($dataSourceCallback);

    return $gb->build();
}

function _createLink(string $url, string $text, int $left, int $top) {
    $code = '
        <div style="width: 75px; height: 75px; position: relative; left: ' . $left . 'px; top: ' . $top . 'px; text-align: center; border: 1px solid black; border-radius: 25px; background-color: white">
            <a style="color: black; text-decoration: none; font-size: 14px; width: 75xp; height: 75px" href="' . $url . '">
                <div style="width: 75xp; height: 75px">
                    ' . $text . '
                </div>
            </a>
        </div>
    ';

    return $code;
}

function _createBlankLink(int $left, int $top) {
    $code = '
        <div style="width: 75px; height: 75px; position: relative; left: ' . $left . 'px; top: ' . $top . 'px">
        </div>
    ';

    return $code;
}

?>