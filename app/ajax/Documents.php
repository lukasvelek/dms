<?php

use DMS\Constants\CacheCategories;
use DMS\Constants\DocumentRank;
use DMS\Constants\DocumentStatus;
use DMS\Constants\ProcessStatus;
use DMS\Constants\UserActionRights;
use DMS\Core\AppConfiguration;
use DMS\Core\CacheManager;
use DMS\Core\CypherManager;
use DMS\Entities\Document;
use DMS\Helpers\ArrayStringHelper;
use DMS\Helpers\DatetimeFormatHelper;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;

require_once('Ajax.php');
require_once('AjaxCommonMethods.php');

$ucm = new CacheManager(CacheCategories::USERS, '../../' . AppConfiguration::getLogDir(), '../../' . AppConfiguration::getCacheDir());
$fcm = new CacheManager(CacheCategories::FOLDERS, '../../' . AppConfiguration::getLogDir(), '../../' . AppConfiguration::getCacheDir());

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
    global $documentModel, $documentBulkActionAuthorizator, $user, $processModel;

    $bulkActions = [];
    $text = '';
    $canDelete = null;
    $canApproveArchivation = null;
    $canDeclineArchivation = null;
    $canArchive = null;
    $canSuggestShredding = null;
    $canMoveToArchiveDocument = null;
    $canMoveFromArchiveDocument = null;

    $idDocuments = $_POST['idDocuments'];

    $idFolder = null;
    if(isset($_POST['id_folder']) && $_POST['id_folder'] != 'null') {
        $idFolder = $_POST['id_folder'];
    }

    $filter = null;
    if(isset($_POST['filter']) && $_POST['filter'] != 'null') {
        $filter = $_POST['filter'];
    }

    if(!is_null($user)) {
        $qb = $processModel->composeStandardProcessQuery(['id_document']);
        $qb ->where('is_archive = 0')
            ->andWhere('status = ?', [ProcessStatus::IN_PROGRESS])
            ->andWhere('id_document IS NOT NULL')
            ->execute();
        
        $idDocumentsInProcess = [];
        while($row = $qb->fetchAssoc()) {
            $idDocumentsInProcess[] = $row['id_document'];
        }

        $qb->clean();

        $qb = $documentModel->composeQueryStandardDocuments();
        $qb ->andWhere($qb->getColumnInValues('id', $idDocuments))
            ->execute();

        $documents = [];
        while($row = $qb->fetchAssoc()) {
            $documents[$row['id']] = $documentModel->createDocumentObjectFromDbRow($row);
        }

        foreach($idDocuments as $idDocument) {
            $inProcess = false;
            if(in_array($idDocument, $idDocumentsInProcess)) {
                $inProcess = true;
            }

            if(!array_key_exists($idDocument, $documents)) {
                continue;
            } else {
                $document = $documents[$idDocument];
            }


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

            if($documentBulkActionAuthorizator->canMoveToArchiveDocument($document, null, true, false) &&
               (is_null($canMoveToArchiveDocument) || $canMoveToArchiveDocument) &&
               !$inProcess) {
                $canMoveToArchiveDocument = true;
            } else {
                $canMoveToArchiveDocument = false;
            }

            if($documentBulkActionAuthorizator->canMoveFromArchiveDocument($document, null, true, false) &&
               (is_null($canMoveFromArchiveDocument) || $canMoveFromArchiveDocument) &&
               !$inProcess) {
                $canMoveFromArchiveDocument = true;
            } else {
                $canMoveFromArchiveDocument = false;
            }
        }
    }

    if($canApproveArchivation) {
        $bulkActions['Approve archivation'] = _createBulkFunctionLink('approve_archivation', $idDocuments, $idFolder, $filter);
    }

    if($canDeclineArchivation) {
        $bulkActions['Decline archivation'] = _createBulkFunctionLink('decline_archivation', $idDocuments, $idFolder, $filter);
    }

    if($canArchive) {
        $bulkActions['Archive'] = _createBulkFunctionLink('archive', $idDocuments, $idFolder, $filter);
    }

    if($canDelete) {
        $bulkActions['Delete'] = _createBulkFunctionLink('delete_documents', $idDocuments, $idFolder, $filter);
    }

    if($canSuggestShredding) {
        $bulkActions['Suggest shredding'] = _createBulkFunctionLink('suggest_for_shredding', $idDocuments, $idFolder, $filter);
    }

    if($canMoveToArchiveDocument) {
        $bulkActions['Move to archive document'] = _createBulkFunctionLink('move_to_archive_document', $idDocuments, $idFolder, $filter);
    }

    if($canMoveFromArchiveDocument) {
        $bulkActions['Move from archive document'] = _createBulkFunctionLink('move_from_archive_document', $idDocuments, $idFolder, $filter);
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
    global $documentModel, $userModel, $folderModel, $ucm, $fcm, $gridSize, $actionAuthorizator, $user;

    $returnArray = [];

    if($user === NULL) {
        die();
    }

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

        $canShareDocuments = $actionAuthorizator->checkActionRight(UserActionRights::SHARE_DOCUMENT, null, false);

        $documents = $documentModel->getDocumentsForName($query, $idFolder, $filter, $gridSize, (($page - 1) * $gridSize));

        $gb = new GridBuilder();

        $gb->addColumns(['name' => 'Name', 'idAuthor' => 'Author', 'status' => 'Status', 'idFolder' => 'Folder', 'dateCreated' => 'Date created', 'dateUpdated' => 'Date updated']);
        $gb->addOnColumnRender('dateUpdated', function(Document $document) use ($user) {
            return DatetimeFormatHelper::formatDateByUserDefaultFormat($document->getDateUpdated(), $user);
        });
        $gb->addOnColumnRender('dateCreated', function(Document $document) use ($user) {
            return DatetimeFormatHelper::formatDateByUserDefaultFormat($document->getDateUpdated(), $user);
        });
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
        $gb->addAction(function(Document $document) {
            return LinkBuilder::createAdvLink(['page' => 'UserModule:SingleDocument:showInfo', 'id' => $document->getId()], 'Info');
        });
        $gb->addAction(function(Document $document) {
            return LinkBuilder::createAdvLink(['page' => 'UserModule:SingleDocument:showEdit', 'id' => $document->getId()], 'Edit');
        });
        $gb->addAction(function(Document $document) use ($canShareDocuments) {
            if($canShareDocuments &&
               ($document->getStatus() == DocumentStatus::ARCHIVED) &&
               ($document->getRank() == DocumentRank::PUBLIC)) {
                return LinkBuilder::createAdvLink(['page' => 'UserModule:SingleDocument:showShare', 'id' => $document->getId()], 'Share');
            } else {
                return '-';
            }
        });
        $gb->addHeaderCheckbox('select-all', 'selectAllDocumentEntries()');
        $gb->addRowCheckbox(function(Document $document) {
            return '<input type="checkbox" id="select" name="select[]" value="' . $document->getId() . '" onupdate="drawDocumentBulkActions(\'' . ($document->getIdFolder() ?? 'null') . '\')" onchange="drawDocumentBulkActions(\'' . ($document->getIdFolder() ?? 'null') . '\')">';
        });
        $gb->addDataSource($documents);

        $returnArray['grid'] = $gb->build();
        $returnArray['controls'] = _createGridPageControls($page, $filter, $idFolder, 'search', $query);
    } else {
        $documents = $documentModel->getStandardDocumentsWithOffset($idFolder, $gridSize, (($page - 1) * $gridSize), $filter);

        $canShareDocuments = $actionAuthorizator->checkActionRight(UserActionRights::SHARE_DOCUMENT, null, false);

        $gb = new GridBuilder();

        $gb->addColumns(['name' => 'Name', 'idAuthor' => 'Author', 'status' => 'Status', 'idFolder' => 'Folder', 'dateCreated' => 'Date created', 'dateUpdated' => 'Date updated']);
        $gb->addOnColumnRender('dateUpdated', function(Document $document) use ($user) {
            return DatetimeFormatHelper::formatDateByUserDefaultFormat($document->getDateUpdated(), $user);
        });
        $gb->addOnColumnRender('dateCreated', function(Document $document) use ($user) {
            return DatetimeFormatHelper::formatDateByUserDefaultFormat($document->getDateUpdated(), $user);
        });
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
        $gb->addAction(function(Document $document) use ($canShareDocuments) {
            if($canShareDocuments &&
               ($document->getStatus() == DocumentStatus::ARCHIVED) &&
               ($document->getRank() == DocumentRank::PUBLIC)) {
                return LinkBuilder::createAdvLink(['page' => 'UserModule:SingleDocument:showShare', 'id' => $document->getId()], 'Share');
            } else {
                return '-';
            }
        });
        $gb->addHeaderCheckbox('select-all', 'selectAllDocumentEntries(\'' . ($idFolder ?? 'null') . '\', \'' . ($filter ?? 'null') . '\')');
        $gb->addRowCheckbox(function(Document $document) use ($filter) {
            return '<input type="checkbox" id="select" name="select[]" value="' . $document->getId() . '" onupdate="drawDocumentBulkActions(\'' . ($document->getIdFolder() ?? 'null') . '\', \'' . ($filter ?? 'null') . '\')" onchange="drawDocumentBulkActions(\'' . ($document->getIdFolder() ?? 'null') . '\', \'' . ($filter ?? 'null') . '\')">';
        });
        $gb->addDataSource($documents);

        $returnArray['grid'] = $gb->build();
        $returnArray['controls'] = _createGridPageControls($page, $filter, $idFolder, 'showAll', count($documents));
    }

    echo json_encode($returnArray);
}

function searchDocumentsSharedWithMe() {
    global $documentModel, $folderModel, $user, $userModel, $ucm, $fcm;

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
        return '<input type="checkbox" id="select" name="select[]" value="' . $document->getId() . '" onchange="drawDocumentBulkActions(\'' . ($document->getIdFolder() ?? 'null') . '\')">';
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

    $documentModel->beginTran();

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
            'shred_year' => date('Y'),
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

    $documentModel->commitTran();
    $documentModel->beginTran();

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

    $documentModel->commitTran();

    if($id_folder == '0') {
        $id_folder = null;
    }

    $data = array(
        'total_count' => $documentModel->getTotalDocumentCount($id_folder),
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
    global $documentModel, $filterModel, $userModel, $folderModel, $ucm, $fcm, $actionAuthorizator;

    $idFilter = $_GET['id_filter'];
    $filter = $filterModel->getDocumentFilterById($idFilter);

    $dataSourceCallback = function() use ($filter, $documentModel) {
        return $documentModel->getDocumentsBySQL($filter->getSQL());
    };

    $gb = new GridBuilder();

    $gb->addColumns(['name' => 'Name', 'idAuthor' => 'Author', 'status' => 'Status', 'idFolder' => 'Folder']);
    $gb->addOnColumnRender('idAuthor', function(Document $document) use ($userModel, $ucm) {
        $user = $ucm->loadUserByIdFromCache($document->getIdAuthor());

        if(is_null($user)) {
            $user = $userModel->getUserById($document->getIdAuthor());
        }
        
        return $user->getFullname();
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
    $gb->addOnColumnRender('status', function(Document $document) {
        return DocumentStatus::$texts[$document->getStatus()];
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

// PRIVATE METHODS

function _createGridPageControls(int $page, ?string $filter, ?string $idFolder, string $action = 'showAll', ?string $query = null) {
    global $documentModel, $user;
    $documentCount = 0;

    if($user === NULL) {
        return '';
    }

    if($filter !== NULL && $action == 'showAll') {
        $action = 'showFiltered';
    }

    switch($action) {
        case 'showSharedWithMe':
            $documentCount = $documentModel->getCountDocumentsSharedWithUser($user->getId());
            break;

        case 'showFiltered':
            $documentCount = $documentModel->getDocumentCountForStatus($idFolder, $filter);
            break;

        case 'search':
            $documentCount = $documentModel->getDocumentsForNameCount($query, $idFolder, $filter);
            break;

        default:
        case 'showAll':
            $documentCount = $documentModel->getTotalDocumentCount($idFolder);
            break;
        }

        $documentPageControl = '';

        $firstPageLink = '<button id="grid-first-page-control-btn" type="button" onclick="';
        $previousPageLink = '<button id="grid-previous-page-control-btn" type="button" onclick="';
        $nextPageLink = '<button id="grid-next-page-control-btn" type="button" onclick="';
        $lastPageLink = '<button id="grid-last-page-control-btn" type="button" onclick="';

        if($action == 'search') {
            $firstPageLink .= 'loadDocumentsSearch(\'' . $query . '\', \'';
            $previousPageLink .= 'loadDocumentsSearch(\'' . $query . '\', \'';
            $nextPageLink .= 'loadDocumentsSearch(\'' . $query . '\', \'';
            $lastPageLink .= 'loadDocumentsSearch(\'' . $query . '\', \'';

            if($idFolder !== NULL) {
                $firstPageLink .= $idFolder . '\', ';
                $previousPageLink .= $idFolder . '\', ';
                $nextPageLink .= $idFolder . '\', ';
                $lastPageLink .= $idFolder . '\', ';
            } else {
                $firstPageLink .= 'null\', ';
                $previousPageLink .= 'null\', ';
                $nextPageLink .= 'null\', ';
                $lastPageLink .= 'null\', ';
            }

            $firstPageLink .= '\'1\')';
        } else {
            if($filter !== NULL) {
                $firstPageLink .= 'loadDocumentsFilter(\'';
                $previousPageLink .= 'loadDocumentsFilter(\'';
                $nextPageLink .= 'loadDocumentsFilter(\'';
                $lastPageLink .= 'loadDocumentsFilter(\'';
    
                if($idFolder !== NULL) {
                    $firstPageLink .= $idFolder . '\', ';
                    $previousPageLink .= $idFolder . '\', ';
                    $nextPageLink .= $idFolder . '\', ';
                    $lastPageLink .= $idFolder . '\', ';
                } else {
                    $firstPageLink .= 'null\', ';
                    $previousPageLink .= 'null\', ';
                    $nextPageLink .= 'null\', ';
                    $lastPageLink .= 'null\', ';
                }
    
                $firstPageLink .= '\'' . $filter . '\', \'1\')';
                $previousPageLink .= '\'' . $filter . '\', ';
                $nextPageLink .= '\'' . $filter . '\', ';
                $lastPageLink .= '\'' . $filter . '\', ';
            } else {
                $firstPageLink .= 'loadDocuments(\'';
                $previousPageLink .= 'loadDocuments(\'';
                $nextPageLink .= 'loadDocuments(\'';
                $lastPageLink .= 'loadDocuments(\'';
    
                if($idFolder !== NULL) {
                    $firstPageLink .= $idFolder . '\', ';
                    $previousPageLink .= $idFolder . '\', ';
                    $nextPageLink .= $idFolder . '\', ';
                    $lastPageLink .= $idFolder . '\', ';
                } else {
                    $firstPageLink .= 'null\', ';
                    $previousPageLink .= 'null\', ';
                    $nextPageLink .= 'null\', ';
                    $lastPageLink .= 'null\', ';
                }
    
                $firstPageLink .= '\'1\')';
            }
        }

        $pageCheck = $page - 1;

        // FIRST PAGE LINK
        $firstPageLink .= '"';
        $firstPageLink .= '>&lt;&lt;</button>';


        // PREVIOUS PAGE LINK
        if($page >= 2) {
            $previousPageLink .= '\'' . ($page - 1) . '\')';
        } else {
            $previousPageLink .= '\'1\')';
        }
        $previousPageLink .= '"';
        $previousPageLink .= '>&lt;</button>';

        
        // NEXT PAGE LINK
        if($page < ceil($documentCount / AppConfiguration::getGridSize())) {
            $nextPageLink .= '\'' . ($page + 1) . '\')';
        } else if($documentCount == 0) {
            $nextPageLink .= '\'1\')';
        } else {
            $nextPageLink .= '\'' . ceil($documentCount / AppConfiguration::getGridSize()) . '\')';
        }
        $nextPageLink .= '"';
        $nextPageLink .= '>&gt;</button>';


        // LAST PAGE LINK
        if($documentCount == 0) {
            $lastPageLink .= '\'1\')';
        } else {
            $lastPageLink .= '\'' . ceil($documentCount / AppConfiguration::getGridSize()) . '\')';
        }
        $lastPageLink .= '"';
        $lastPageLink .= '>&gt;&gt;</button>';

        $documentPageControl = 'Total count: ' . $documentCount . ' | ';

        if($documentCount > AppConfiguration::getGridSize()) {
            if($pageCheck * AppConfiguration::getGridSize() >= $documentCount) {
                $documentPageControl .= (1 + ($page * AppConfiguration::getGridSize()));
            } else {
                $from = 1 + ($pageCheck * AppConfiguration::getGridSize());
                $to = AppConfiguration::getGridSize() + ($pageCheck * AppConfiguration::getGridSize());

                if($to > $documentCount) {
                    $to = $documentCount;
                }

                $documentPageControl .= $from . '-' . $to;
            }
        } else {
            $documentPageControl = 'Total count: ' .  $documentCount;
        }

        $documentPageControl .= ' | ' . $firstPageLink . ' ' . $previousPageLink . ' ' . $nextPageLink . ' ' . $lastPageLink;

        return $documentPageControl;
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

function _createBulkFunctionLink(string $action, array $idDocuments, ?int $idFolder, ?string $filter) {
    global $user;
    $link = '?page=UserModule:Documents:performBulkAction&';

    if($user === NULL) {
        die('User does not exist in AJAX');
        exit;
    }

    $cm = CacheManager::getTemporaryObject(md5($user->getId() . 'bulk_action' . $action), true);
    foreach($idDocuments as $idDocument) {
        $cm->saveStringToCache($idDocument);
    }

    $link .= '&action=' . $action;

    if($idFolder !== NULL) {
        $link .= '&id_folder=' . $idFolder;
    }

    if($filter !== NULL) {
        $link .= '&filter=' . $filter;
    }

    return $link;
}

exit;

?>