<?php

use DMS\Constants\CacheCategories;
use DMS\Constants\DocumentStatus;
use DMS\Constants\UserActionRights;
use DMS\Core\AppConfiguration;
use DMS\Core\CacheManager;
use DMS\Core\CypherManager;
use DMS\Entities\Document;
use DMS\Helpers\ArrayStringHelper;
use DMS\Helpers\DatetimeFormatHelper;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

require_once('Ajax.php');

$ucm = new CacheManager(true, CacheCategories::USERS, '../../' . AppConfiguration::getLogDir(), '../../' . AppConfiguration::getCacheDir());
$fcm = new CacheManager(true, CacheCategories::FOLDERS, '../../' . AppConfiguration::getLogDir(), '../../' . AppConfiguration::getCacheDir());

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

function getBulkActions() {
    global $processComponent, $documentModel, $documentBulkActionAuthorizator, $user;

    $bulkActions = [];
    $text = '';
    $canDelete = null;
    $canApproveArchivation = null;
    $canDeclineArchivation = null;
    $canArchive = null;
    $canSuggestShredding = null;

    $idDocuments = $_GET['idDocuments'];

    if(!is_null($user)) {
        foreach($idDocuments as $idDocument) {
            $inProcess = $processComponent->checkIfDocumentIsInProcess($idDocument);
            $document = $documentModel->getDocumentById($idDocument);

            if($documentBulkActionAuthorizator->canDelete($document, null, true, false) && 
                (is_null($canDelete) || $canDelete) &&
                !$inProcess) {
                $canDelete = true;
            } else {
                $canDelete = false;
            }

            if($documentBulkActionAuthorizator->canApproveArchivation($document, null, true, false) && 
                (is_null($canApproveArchivation) || $canApproveArchivation) &&
                !$inProcess) {
                $canApproveArchivation = true;
            } else {
                $canApproveArchivation = false;
            }

            if($documentBulkActionAuthorizator->canDeclineArchivation($document, null, true, false) &&
                (is_null($canDeclineArchivation) || $canDeclineArchivation) &&
                !$inProcess) {
                $canDeclineArchivation = true;
            } else {
                $canDeclineArchivation = false;
            }

            if($documentBulkActionAuthorizator->canArchive($document, null, true, false) &&
                (is_null($canArchive) || $canArchive) &&
                !$inProcess) {
                $canArchive = true;
            } else {
                $canArchive = false;
            }

            if($documentBulkActionAuthorizator->canSuggestForShredding($document, null, true, false) &&
              (is_null($canSuggestShredding) || $canSuggestShredding) &&
              !$inProcess) {
                $canSuggestShredding = true;
            } else {
                $canSuggestShredding = false;
            }
        }
    }

    if($canApproveArchivation) {
        $link = '?page=UserModule:Documents:performBulkAction&';

        $i = 0;
        foreach($idDocuments as $idDocument) {
            if(($i + 1) == count($idDocuments)) {
                $link .= 'select[]=' . $idDocument;
            } else {
                $link .= 'select[]=' . $idDocument . '&';
            }
        }

        $link .= '&action=approve_archivation';

        $bulkActions['Approve archivation'] = $link;
    }

    if($canDeclineArchivation) {
        $link = '?page=UserModule:Documents:performBulkAction&';

        $i = 0;
        foreach($idDocuments as $idDocument) {
            if(($i + 1) == count($idDocuments)) {
                $link .= 'select[]=' . $idDocument;
            } else {
                $link .= 'select[]=' . $idDocument . '&';
            }
        }

        $link .= '&action=decline_archivation';

        $bulkActions['Decline archivation'] = $link;
    }

    if($canArchive) {
        $link = '?page=UserModule:Documents:performBulkAction&';

        $i = 0;
        foreach($idDocuments as $idDocument) {
            if(($i + 1) == count($idDocuments)) {
                $link .= 'select[]=' . $idDocument;
            } else {
                $link .= 'select[]=' . $idDocument . '&';
            }
        }

        $link .= '&action=archive';

        $bulkActions['Archive'] = $link;
    }

    if($canDelete) {
        $link = '?page=UserModule:Documents:performBulkAction&';
        
        $i = 0;
        foreach($idDocuments as $idDocument) {
            if(($i + 1) == count($idDocuments)) {
                $link .= 'select[]=' . $idDocument;
            } else {
                $link .= 'select[]=' . $idDocument . '&';
            }
        }

        $link .= '&action=delete_documents';

        $bulkActions['Delete'] = $link;
    }

    if($canSuggestShredding) {
        $link = '?page=UserModule:Documents:performBulkAction&';
        
        $i = 0;
        foreach($idDocuments as $idDocument) {
            if(($i + 1) == count($idDocuments)) {
                $link .= 'select[]=' . $idDocument;
            } else {
                $link .= 'select[]=' . $idDocument . '&';
            }
        }

        $link .= '&action=suggest_for_shredding';

        $bulkActions['Suggest shredding'] = $link;
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

    echo $text;
}

function deleteComment() {
    global $user, $documentCommentRepository;

    $idComment = htmlspecialchars($_POST['idComment']);
    $idCurrentUser = null;

    if(!is_null($user)) {
        $idCurrentUser = $user->getId();
    } else {
        $idCurrentUser = $_SESSION['id_current_user'];
    }

    $documentCommentRepository->deleteComment($idComment, $idCurrentUser);
}

function getComments() {
    global $documentCommentModel, $userModel, $ucm, $user;

    $idDocument = htmlspecialchars($_GET['idDocument']);
    $canDelete = htmlspecialchars($_GET['canDelete']);

    $comments = $documentCommentModel->getCommentsForIdDocument($idDocument);
    
    $codeArr = [];

    if(empty($comments)) {
        $codeArr[] = '<hr>';
        $codeArr[] = 'No comments found!';
    } else {
        foreach($comments as $comment) {
            $cacheAuthor = $ucm->loadUserByIdFromCache($comment->getIdAuthor());
            
            $author = null;

            if(is_null($cacheAuthor)) {
                $author = $userModel->getUserById($comment->getIdAuthor());

                $ucm->saveUserToCache($author);
            } else {
                $author = $cacheAuthor;
            }

            $authorLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showProfile', 'id' => $comment->getIdAuthor()), $author->getFullname());
            
            $codeArr[] = '<article id="comment' . $comment->getId() . '">';
            $codeArr[] = '<hr>';
            $codeArr[] = '<p class="comment-text">' . $comment->getText() . '</p>';

            $datePosted = $comment->getDateCreated();
            if(!is_null($user)) {
                $datePosted = DatetimeFormatHelper::formatDateByUserDefaultFormat($datePosted, $user);
            } else {
                $datePosted = DatetimeFormatHelper::formatDateByFormat($datePosted, AppConfiguration::getDefaultDatetimeFormat());
            }

            if($canDelete == '1') {
                $deleteLink = '<a class="general-link" style="cursor: pointer" onclick="deleteDocumentComment(\'' . $comment->getId() . '\', \'' . $idDocument . '\', \'' . $canDelete . '\');">Delete</a>';

                $codeArr[] = '<p class="comment-info">Author: ' . $authorLink . ' | Date posted: ' . $datePosted . ' | ' . $deleteLink . '</p>';
            } else {
                $codeArr[] = '<p class="comment-info">Author: ' . $authorLink . ' | Date posted: ' . $datePosted . '</p>';
            }

            $codeArr[] = '</article>';
        }
    }

    echo ArrayStringHelper::createUnindexedStringFromUnindexedArray($codeArr);
}

function sendComment() {
    global $documentCommentModel, $userModel, $documentCommentRepository, $user;

    $text = htmlspecialchars($_POST['commentText']);
    $idAuthor = htmlspecialchars($_POST['idAuthor']);
    $idDocument = htmlspecialchars($_POST['idDocument']);
    $canDelete = htmlspecialchars($_POST['canDelete']);

    $documentCommentRepository->insertComment($idAuthor, $idDocument, $text);
    $comment = $documentCommentModel->getLastInsertedCommentForIdUserAndIdDocument($idAuthor, $idDocument);

    $author = $userModel->getUserById($idAuthor);

    $authorLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showProfile', 'id' => $comment->getIdAuthor()), $author->getFullname());

    $codeArr[] = '<hr>';
    $codeArr[] = '<article id="comment' . $comment->getId() . '">';
    $codeArr[] = '<p class="comment-text">' . $comment->getText() . '</p>';

    $datePosted = $comment->getDateCreated();
    if(!is_null($user)) {
        $datePosted = DatetimeFormatHelper::formatDateByUserDefaultFormat($datePosted, $user);
    } else {
        $datePosted = DatetimeFormatHelper::formatDateByFormat($datePosted, AppConfiguration::getDefaultDatetimeFormat());
    }

    if($canDelete == '1') {
        $deleteLink = '<a class="general-link" style="cursor: pointer" onclick="deleteComment(\'' . $comment->getId() . '\', \'' . $idDocument . '\', \'' . $canDelete . '\');">Delete</a>';

        $codeArr[] = '<p class="comment-info">Author: ' . $authorLink . ' | Date posted: ' . $datePosted . ' | ' . $deleteLink . '</p>';
    } else {
        $codeArr[] = '<p class="comment-info">Author: ' . $authorLink . ' | Date posted: ' . $datePosted . '</p>';
    }

    $codeArr[] = '</article>';

    echo ArrayStringHelper::createUnindexedStringFromUnindexedArray($codeArr);
}

function search() {
    global $documentModel, $userModel, $folderModel, $metadataModel, $ucm, $fcm, $gridSize, $gridUseFastLoad, $actionAuthorizator;

    $idFolder = htmlspecialchars($_POST['idFolder']);

    if($idFolder == 'null') {
        $idFolder = null;
    }

    $filter = null;
    $page = 1;

    if(isset($_POST['filter'])) {
        $filter = htmlspecialchars($_POST['filter']);
    }

    if(isset($_POST['page'])) {
        $page = (int)(htmlspecialchars($_POST['page']));
    }

    if(isset($_POST['q'])) {
        $query = htmlspecialchars($_POST['q']);

        $query = str_replace('_', '%', $query);

        if($idFolder == 'null') {
            $idFolder = null;
        }

        $gb = new GridBuilder();

        $gb->addColumns(['name' => 'Name', 'idAuthor' => 'Author', 'status' => 'Status', 'idFolder' => 'Folder']);
        $gb->addOnColumnRender('idAuthor', function(Document $document) use ($userModel, $ucm) {
            $user = $ucm->loadUserByIdFromCache($document->getIdAuthor());

            if(is_null($user)) {
                $user = $userModel->getUserById($document->getIdAuthor());
            }
            
            return $user->getFullname();
        });
        $gb->addOnColumnRender('status', function(Document $document) {
            return DocumentStatus::$texts[$document->getStatus()];
        });
        $gb->addOnColumnRender('idFolder', function(Document $document) use ($folderModel, $fcm) {
            if($document->getIdFolder() !== NULL) {
                $folder = $fcm->loadFolderByIdFromCache($document->getIdFolder());

                if(is_null($folder)) {
                    $folder = $folderModel->getFolderById($document->getIdFolder());
                }

                return $folder->getName();
            } else {
                return '-';
            }
        });
        $gb->addAction(function(Document $document) {
            return LinkBuilder::createAdvLink(['page' => 'UserModule:SingleDocument:showInfo', 'id' => $document->getId()], 'Info');
        });
        $gb->addAction(function(Document $document) {
            return LinkBuilder::createAdvLink(['page' => 'UserModule:SingleDocument:showEdit', 'id' => $document->getId()], 'Edit');
        });
        $gb->addHeaderCheckbox('select-all', 'selectAllDocumentEntries()');
        $gb->addRowCheckbox(function(Document $document) {
            return '<input type="checkbox" id="select" name="select[]" value="' . $document->getId() . '" onupdate="drawDocumentBulkActions()" onchange="drawDocumentBulkActions()">';
        });
        $gb->addDataSourceCallback(function() use ($documentModel, $idFolder, $filter, $query) {
            return $documentModel->getDocumentsForName($query, $idFolder, $filter);
        });

        echo $gb->build();
    } else {
        $dataSourceCallback = null;
        if($gridUseFastLoad) {
            $page -= 1;
        
            $firstIdDocumentOnPage = $documentModel->getFirstIdDocumentOnAGridPage(($page * $gridSize));

            $dataSourceCallback = function() use ($documentModel, $firstIdDocumentOnPage, $idFolder, $filter, $gridSize) {
                return $documentModel->getStandardDocumentsFromId($firstIdDocumentOnPage, $idFolder, $filter, $gridSize);
            };
        } else {
            $dataSourceCallback = function() use ($documentModel, $idFolder, $filter, $page, $gridSize) {
                return $documentModel->getStandardDocuments($idFolder, $filter, ($page * $gridSize));
            };
        }

        $gb = new GridBuilder();

        $gb->addColumns(['name' => 'Name', 'idAuthor' => 'Author', 'status' => 'Status', 'idFolder' => 'Folder']);
        $gb->addOnColumnRender('idAuthor', function(Document $document) use ($userModel, $ucm) {
            $user = $ucm->loadUserByIdFromCache($document->getIdAuthor());

            if(is_null($user)) {
                $user = $userModel->getUserById($document->getIdAuthor());
            }
            
            return $user->getFullname();
        });
        $gb->addOnColumnRender('status', function(Document $document) {
            return DocumentStatus::$texts[$document->getStatus()];
        });
        $gb->addOnColumnRender('idFolder', function(Document $document) use ($folderModel, $fcm) {
            if($document->getIdFolder() !== NULL) {
                $folder = $fcm->loadFolderByIdFromCache($document->getIdFolder());

                if(is_null($folder)) {
                    $folder = $folderModel->getFolderById($document->getIdFolder());
                }

                return $folder->getName();
            } else {
                return '-';
            }
        });
        $gb->addAction(function(Document $document) {
            return LinkBuilder::createAdvLink(['page' => 'UserModule:SingleDocument:showInfo', 'id' => $document->getId()], 'Info');
        });
        $gb->addAction(function(Document $document) {
            if($document->getStatus() == DocumentStatus::ARCHIVED) {
                return LinkBuilder::createAdvLink(['page' => 'UserModule:SingleDocument:showEdit', 'id' => $document->getId()], 'Edit');
            } else {
                return '-';
            }
        });
        $gb->addAction(function(Document $document) use ($actionAuthorizator) {
            if($actionAuthorizator->checkActionRight(UserActionRights::SHARE_DOCUMENT, null, false) && ($document->getStatus() == DocumentStatus::ARCHIVED)) {
                return LinkBuilder::createAdvLink(['page' => 'UserModule:SingleDocument:showShare', 'id' => $document->getId()], 'Share');
            } else {
                return '-';
            }
        });
        $gb->addHeaderCheckbox('select-all', 'selectAllDocumentEntries()');
        $gb->addRowCheckbox(function(Document $document) {
            return '<input type="checkbox" id="select" name="select[]" value="' . $document->getId() . '" onupdate="drawDocumentBulkActions()" onchange="drawDocumentBulkActions()">';
        });
        $gb->addDataSourceCallback($dataSourceCallback);

        echo $gb->build();
    }
}

function searchDocumentsSharedWithMe() {
    global $documentModel, $folderModel, $user, $userModel, $metadataModel, $ucm, $fcm;

    if(is_null($user)) {
        return '';
    }

    $documentCallback = function() use ($documentModel, $user) {
        return $documentModel->getSharedDocumentsWithUser($user->getId());
    };

    $gb = new GridBuilder();

    $gb->addColumns(['name' => 'Name', 'idAuthor' => 'Author', 'status' => 'Status', 'idFolder' => 'Folder', 'sharedFrom' => 'Shared from', 'sharedTo' => 'Shared to', 'sharedBy' => 'Shared by']);
    $gb->addDataSourceCallback($documentCallback);
    $gb->addOnColumnRender('idAuthor', function(Document $document) use ($userModel, $ucm) {
        $user = $ucm->loadUserByIdFromCache($document->getIdAuthor());

        if(is_null($user)) {
            $user = $userModel->getUserById($document->getIdAuthor());

            $ucm->saveUserToCache($user);
        }

        return $user->getFullname();
    });
    $gb->addOnColumnRender('status', function(Document $document) {
        return DocumentStatus::$texts[$document->getStatus()];
    });
    $gb->addOnColumnRender('idFolder', function(Document $document) use ($folderModel, $fcm) {
        if($document->getIdFolder() !== NULL) {
            $folder = $fcm->loadFolderByIdFromCache($document->getIdFolder());

            if(is_null($folder)) {
                $folder = $folderModel->getFolderById($document->getIdFolder());

                $fcm->saveFolderToCache($folder);
            }

            return $folder->getName();
        } else {
            return '-';
        }
    });
    $gb->addOnColumnRender('sharedFrom', function(Document $document) use ($documentModel, $user) {
        if($user === NULL) {
            return '';
        }

        $sharing = $documentModel->getDocumentSharingByIdDocumentAndIdUser($user->getId(), $document->getId());
        
        return $sharing['date_from'];
    });
    $gb->addOnColumnRender('sharedTo', function(Document $document) use ($documentModel, $user) {
        if($user === NULL) {
            return '';
        }

        $sharing = $documentModel->getDocumentSharingByIdDocumentAndIdUser($user->getId(), $document->getId());
        
        return $sharing['date_to'];
    });
    $gb->addOnColumnRender('sharedBy', function(Document $document) use ($documentModel, $ucm, $userModel, $user) {
        if($user === NULL) {
            return '';
        }

        $sharing = $documentModel->getDocumentSharingByIdDocumentAndIdUser($user->getId(), $document->getId());

        $idAuthor = $sharing['id_author'];

        $author = $ucm->loadUserByIdFromCache($idAuthor);

        if(is_null($author)) {
            $author = $userModel->getUserById($idAuthor);

            $ucm->saveUserToCache($author);
        }

        return $author->getFullname();
    });
    $gb->addHeaderCheckbox('select-all', 'selectAllDocumentEntries()');
    $gb->addRowCheckbox(function(Document $document) {
        return '<input type="checkbox" id="select" name="select[]" value="' . $document->getId() . '" onchange="drawDocumentBulkActions()">';
    });
    $gb->addAction(function(Document $document) {
        return LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showInfo', 'id' => $document->getId()), 'Info');
    });
    $gb->addAction(function(Document $document) {
        return LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showEdit', 'id' => $document->getId()), 'Edit');
    });

    echo $gb->build();
}

function generateDocuments() {
    global $documentModel, $user;

    if($user == null) {
        exit;
    }

    $id_folder = $_GET['id_folder'];
    $count = $_GET['count'];
    $isDebug = $_GET['is_debug'];

    if($isDebug == 0 || $isDebug == '0') {
        exit;
    }

    $inserted = 0;
    while($inserted < $count) {
        $data = array(
            'name' => 'DG_' . CypherManager::createCypher(8),
            'id_author' => $user->getId(),
            'id_officer' => $user->getId(),
            'status' => '1',
            'id_manager' => '2',
            'id_group' => '1',
            'is_deleted' => '0',
            'rank' => 'public',
            'shred_year' => '2023',
            'after_shred_action' => 'showAsShredded',
            'shredding_status' => '5'
        );

        if($id_folder != '0') {
            $data['id_folder'] = $id_folder;
        }

        $result = $documentModel->insertNewDocument($data);

        if($result) {
            $inserted++;
        }
    }

    if($inserted < $count) {
        for($i = 0; $i < ($count - $inserted); $i++) {
            $data = array(
                'name' => 'DG_' . CypherManager::createCypher(8),
                'id_author' => $user->getId(),
                'id_officer' => $user->getId(),
                'status' => '1',
                'id_manager' => '2',
                'id_group' => '1',
                'is_deleted' => '0',
                'rank' => 'public',
                'shred_year' => '2023',
                'after_shred_action' => 'showAsShredded',
                'shredding_status' => '5'
            );
    
            if($id_folder != '0') {
                $data['id_folder'] = $id_folder;
            }
    
            $documentModel->insertNewDocument($data);
        }
    }

    $data = array(
        'total_count' => $documentModel->getTotalDocumentCount(),
        'shredded_count' => $documentModel->getDocumentCountByStatus(DocumentStatus::SHREDDED),
        'archived_count' => $documentModel->getDocumentCountByStatus(DocumentStatus::ARCHIVED),
        'new_count' => $documentModel->getDocumentCountByStatus(DocumentStatus::NEW),
        'waiting_for_archivation_count' => $documentModel->getDocumentCountByStatus(DocumentStatus::ARCHIVATION_APPROVED)
    );

    $documentModel->beginTran();

    $documentModel->insertDocumentStatsEntry($data);

    $documentModel->commitTran();
}

function documentsCustomFilter() {
    global $documentModel, $filterModel, $userModel, $metadataModel, $folderModel;

    $idFilter = $_GET['id_filter'];
    $filter = $filterModel->getDocumentFilterById($idFilter);

    $documents = $documentModel->getDocumentsBySQL($filter->getSql());

    $tb = TableBuilder::getTemporaryObject();

    $headers = array(
        '<input type="checkbox" id="select-all" onchange="selectAllDocumentEntries()">',
        'Actions',
        'Name',
        'Author',
        'Status',
        'Folder'
    );

    $headerRow = null;

    if(empty($documents)) {
        $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
    } else {
        foreach($documents as $document) {
            $actionLinks = array(
                LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showInfo', 'id' => $document->getId()), 'Information'),
                LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showEdit', 'id' => $document->getId()), 'Edit')
            );

            if(is_null($headerRow)) {
                $row = $tb->createRow();

                foreach($headers as $header) {
                    $col = $tb->createCol()->setText($header)
                                           ->setBold();

                    if($header == 'Actions') {
                        $col->setColspan(count($actionLinks));
                    }

                    $row->addCol($col);
                }

                $headerRow = $row;

                $tb->addRow($row);
            }

            $docuRow = $tb->createRow();
            $docuRow->addCol($tb->createCol()->setText('<input type="checkbox" id="select" name="select[]" value="' . $document->getId() . '" onchange="drawDocumentBulkActions()">'));

            foreach($actionLinks as $actionLink) {
                $docuRow->addCol($tb->createCol()->setText($actionLink));
            }

            $docuRow->addCol($tb->createCol()->setText($document->getName()))
                    ->addCol($tb->createCol()->setText($userModel->getUserById($document->getIdAuthor())->getFullname()))
            ;

            $dbStatuses = $metadataModel->getAllValuesForIdMetadata($metadataModel->getMetadataByName('status', 'documents')->getId());

            foreach($dbStatuses as $dbs) {
                if($dbs->getValue() == $document->getStatus()) {
                    $docuRow->addCol($tb->createCol()->setText($dbs->getName()));
                }
            }

            $folderName = '-';

            if($document->getIdFolder() !== NULL) {
                $folder = $folderModel->getFolderById($document->getIdFolder());
                $folderName = $folder->getName();
            }

            $docuRow->addCol($tb->createCol()->setText($folderName));
                
            $tb->addRow($docuRow);
        }
    }

    echo $tb->build();
}

// PRIVATE METHODS

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

exit;

?>