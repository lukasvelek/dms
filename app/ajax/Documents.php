<?php

use DMS\Constants\CacheCategories;
use DMS\Constants\DocumentLockType;
use DMS\Constants\DocumentRank;
use DMS\Constants\DocumentStatus;
use DMS\Constants\Metadata\DocumentMetadata;
use DMS\Constants\Metadata\DocumentStatsMetadata;
use DMS\Constants\ProcessStatus;
use DMS\Constants\UserActionRights;
use DMS\Core\AppConfiguration;
use DMS\Core\CacheManager;
use DMS\Core\CypherManager;
use DMS\Entities\Document;
use DMS\Entities\DocumentLockEntity;
use DMS\Exceptions\AException;
use DMS\Exceptions\ValueIsNullException;
use DMS\Helpers\ArrayStringHelper;
use DMS\Helpers\DatetimeFormatHelper;
use DMS\Helpers\GridDataHelper;
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
    throw new ValueIsNullException('$action');
}

try {
    echo($action());
} catch(AException $e) {
    echo('<b>Exception: </b>' . $e->getMessage() . '<br><b>Stack trace: </b>' . $e->getTraceAsString());
    exit;
}

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

        $canDeleteIds = $documentBulkActionAuthorizator->getAllDocumentIdsForCanDelete($documentModel, null, true);
        $canApproveArchivationIds = $canDeclineArchivationIds = $documentBulkActionAuthorizator->getAllDocumentIdsForApproveArchivation($documentModel, null, true);
        //$canDeclineArchivationIds = $documentBulkActionAuthorizator->getAllDocumentIdsForDeclineArchivation($documentModel, null, true); // commented because it does the same SQL query as above
        $canArchiveIds = $documentBulkActionAuthorizator->getAllDocumentIdsForArchive($documentModel, null, true);
        $canSuggestShreddingIds = $documentBulkActionAuthorizator->getAllDocumentIdsForSuggestForShredding($documentModel, null, true);
        $canMoveToArchiveDocumentIds = $documentBulkActionAuthorizator->getAllDocumentIdsForMoveToArchiveDocument($documentModel, null, true);
        $canMoveFromArchiveDocumentIds = $documentBulkActionAuthorizator->getAllDocumentIdsForMoveFromArchiveDocument($documentModel, null, true);

        foreach($idDocuments as $idDocument) {
            $inProcess = false;
            if(in_array($idDocument, $idDocumentsInProcess)) {
                $inProcess = true;
            }

            if(in_array($idDocument, $canDeleteIds) && (is_null($canDelete) || $canDelete) && !$inProcess) {
                $canDelete = true;
            } else {
                $canDelete = false;
            }

            if(in_array($idDocument, $canApproveArchivationIds) && (is_null($canApproveArchivation) || $canApproveArchivation) && !$inProcess) {
                $canApproveArchivation = true;
            } else {
                $canApproveArchivation = false;
            }

            if(in_array($idDocument, $canDeclineArchivationIds) && (is_null($canDeclineArchivation) || $canDeclineArchivation) && !$inProcess) {
                $canDeclineArchivation = true;
            } else {
                $canDeclineArchivation = false;
            }

            if(in_array($idDocument, $canArchiveIds) && (is_null($canArchive) || $canArchive) && !$inProcess) {
                $canArchive = true;
            } else {
                $canArchive = false;
            }

            if(in_array($idDocument, $canSuggestShreddingIds) && (is_null($canSuggestShredding) || $canSuggestShredding) && !$inProcess) {
                $canSuggestShredding = true;
            } else {
                $canSuggestShredding = false;
            }

            if(in_array($idDocument, $canMoveToArchiveDocumentIds) && (is_null($canMoveToArchiveDocument) || $canMoveToArchiveDocument) && !$inProcess) {
                $canMoveToArchiveDocument = true;
            } else {
                $canMoveToArchiveDocument = false;
            }

            if(in_array($idDocument, $canMoveFromArchiveDocumentIds) && (is_null($canMoveFromArchiveDocument) || $canMoveFromArchiveDocument) && !$inProcess) {
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

    try {
        $documentCommentRepository->deleteComment($idComment, $idCurrentUser);
    } catch(Exception $e) {
        return $e->getMessage();
    }
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
    global $documentModel, $userModel, $folderModel, $ucm, $fcm, $gridSize, $actionAuthorizator, $user, $documentLockModel, $documentLockComponent;

    $returnArray = [];

    if($user === NULL) {
        throw new ValueIsNullException('$user');
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

        $gb->addColumns(['lock' => 'Lock', 'name' => 'Name', 'idAuthor' => 'Author', 'status' => 'Status', 'idFolder' => 'Folder', 'dateCreated' => 'Date created', 'dateUpdated' => 'Date updated']);
        $gb->addOnColumnRender('lock', function(Document $document) use ($user, $documentLockComponent) {
            $lock = $documentLockComponent->isDocumentLocked($document->getId());
    
            if($lock === FALSE) {
                return LinkBuilder::createAdvLink(['page' => 'UserModule:Documents:lockDocumentForUser', 'id_document' => $document->getId(), 'id_user' => $user->getId()], GridDataHelper::renderBooleanValueWithColors($lock, '-', 'Unlocked', 'red', 'green'));
            }
    
            return $documentLockComponent->createLockText($lock, $user->getId());
        });
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
        $gb->addAction(function(Document $document) use ($documentLockComponent, $user) {
            if($document->getStatus() == DocumentStatus::ARCHIVED) {
                $lock = $documentLockComponent->isDocumentLocked($document->getId());

                if($lock !== FALSE) {
                    if($lock->getType() == DocumentLockType::USER_LOCK && $lock->getIdUser() != $user->getId()) {
                        return '-';
                    } else if($lock->getType() == DocumentLockType::PROCESS_LOCK) {
                        return '-';
                    }
                }

                return LinkBuilder::createAdvLink(['page' => 'UserModule:SingleDocument:showEdit', 'id' => $document->getId()], 'Edit');
            } else {
                return '-';
            }
        });
        $gb->addAction(function(Document $document) use ($canShareDocuments, $documentLockComponent, $user) {
            if($canShareDocuments &&
               ($document->getStatus() == DocumentStatus::ARCHIVED) &&
               ($document->getRank() == DocumentRank::PUBLIC)) {
                $lock = $documentLockComponent->isDocumentLocked($document->getId());

                if($lock !== FALSE) {
                    if($lock->getType() == DocumentLockType::USER_LOCK && $lock->getIdUser() != $user->getId()) {
                        return '-';
                    } else if($lock->getType() == DocumentLockType::PROCESS_LOCK) {
                        return '-';
                    }
                }

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

        $gb->addColumns(['lock' => 'Lock', 'name' => 'Name', 'idAuthor' => 'Author', 'status' => 'Status', 'idFolder' => 'Folder', 'dateCreated' => 'Date created', 'dateUpdated' => 'Date updated']);
        $gb->addOnColumnRender('lock', function(Document $document) use ($user, $documentLockComponent) {
            $lock = $documentLockComponent->isDocumentLocked($document->getId());

            if($lock === FALSE) {
                if(in_array($document->getStatus(), [DocumentStatus::DELETED, DocumentStatus::SHREDDED])) {
                    return '-';
                } else {
                    return LinkBuilder::createAdvLink(['page' => 'UserModule:Documents:lockDocumentForUser', 'id_document' => $document->getId(), 'id_user' => $user->getId()], GridDataHelper::renderBooleanValueWithColors(($lock instanceof DocumentLockEntity), '-', 'Unlocked', 'red', 'green'));
                }
            }

            return $documentLockComponent->createLockText($lock, $user->getId());
        });
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
        $gb->addAction(function(Document $document) use ($documentLockComponent, $user) {
            if($document->getStatus() == DocumentStatus::ARCHIVED) {
                $lock = $documentLockComponent->isDocumentLocked($document->getId());

                if($lock !== FALSE) {
                    if($lock->getType() == DocumentLockType::USER_LOCK && $lock->getIdUser() != $user->getId()) {
                        return '-';
                    } else if($lock->getType() == DocumentLockType::PROCESS_LOCK) {
                        return '-';
                    }
                }

                return LinkBuilder::createAdvLink(['page' => 'UserModule:SingleDocument:showEdit', 'id' => $document->getId()], 'Edit');
            } else {
                return '-';
            }
        });
        $gb->addAction(function(Document $document) use ($canShareDocuments, $documentLockComponent, $user) {
            if($canShareDocuments &&
               ($document->getStatus() == DocumentStatus::ARCHIVED) &&
               ($document->getRank() == DocumentRank::PUBLIC)) {
                $lock = $documentLockComponent->isDocumentLocked($document->getId());

                if($lock !== FALSE) {
                    if($lock->getType() == DocumentLockType::USER_LOCK && $lock->getIdUser() != $user->getId()) {
                        return '-';
                    } else if($lock->getType() == DocumentLockType::PROCESS_LOCK) {
                        return '-';
                    }
                }

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

        if(AppConfiguration::getIsDocumentDuplicationEnabled()) {
            $gb->addAction(function(Document $document) use ($documentLockComponent, $user) {
                if(($document->getStatus() == DocumentStatus::ARCHIVED) &&
                   ($document->getRank() == DocumentRank::PUBLIC)) {
                    $lock = $documentLockComponent->isDocumentLocked($document->getId());

                    if($lock !== FALSE) {
                        if($lock->getType() == DocumentLockType::USER_LOCK) {
                            return LinkBuilder::createAdvLink(['page' => 'UserModule:Documents:duplicateDocument', 'id' => $document->getId()], 'Duplicate');
                        }
                    } else {
                        return LinkBuilder::createAdvLink(['page' => 'UserModule:Documents:duplicateDocument', 'id' => $document->getId()], 'Duplicate');
                    }
                }

                return '-';
            });
        }

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
    global $documentModel, $user, $logger;

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
            DocumentMetadata::NAME => 'DG_' . CypherManager::createCypher(8),
            DocumentMetadata::ID_AUTHOR => $user->getId(),
            DocumentMetadata::ID_OFFICER => $user->getId(),
            DocumentMetadata::STATUS => '1',
            DocumentMetadata::ID_MANAGER => '2',
            DocumentMetadata::ID_GROUP => '1',
            DocumentMetadata::IS_DELETED => '0',
            DocumentMetadata::RANK => 'public',
            DocumentMetadata::SHRED_YEAR => date('Y'),
            DocumentMetadata::AFTER_SHRED_ACTION => 'showAsShredded',
            DocumentMetadata::SHREDDING_STATUS => '5'
        );

        if($id_folder != '0') {
            $data[DocumentMetadata::ID_FOLDER] = $id_folder;
        }

        $result = $documentModel->insertNewDocument($data);

        if($result) {
            $inserted++;
        }

        if($inserted % 100 == 0) {
            $documentModel->commitTran();
            $documentModel->beginTran();
        }
    }

    $documentModel->commitTran();
    $documentModel->beginTran();

    if($inserted < $count) {
        for($i = 0; $i < ($count - $inserted); $i++) {
            $data = array(
                DocumentMetadata::NAME => 'DG_' . CypherManager::createCypher(8),
                DocumentMetadata::ID_AUTHOR => $user->getId(),
                DocumentMetadata::ID_OFFICER => $user->getId(),
                DocumentMetadata::STATUS => '1',
                DocumentMetadata::ID_MANAGER => '2',
                DocumentMetadata::ID_GROUP => '1',
                DocumentMetadata::IS_DELETED => '0',
                DocumentMetadata::RANK => 'public',
                DocumentMetadata::SHRED_YEAR => date('Y'),
                DocumentMetadata::AFTER_SHRED_ACTION => 'showAsShredded',
                DocumentMetadata::SHREDDING_STATUS => '5'
            );
    
            if($id_folder != '0') {
                $data[DocumentMetadata::ID_FOLDER] = $id_folder;
            }
    
            $documentModel->insertNewDocument($data);
        }
    }

    $documentModel->commitTran();

    if($id_folder == '0') {
        $id_folder = null;
    }

    $data = array(
        DocumentStatsMetadata::TOTAL_COUNT => $documentModel->getTotalDocumentCount($id_folder),
        DocumentStatsMetadata::SHREDDED_COUNT => $documentModel->getDocumentCountByStatus(DocumentStatus::SHREDDED),
        DocumentStatsMetadata::ARCHIVED_COUNT => $documentModel->getDocumentCountByStatus(DocumentStatus::ARCHIVED),
        DocumentStatsMetadata::NEW_COUNT => $documentModel->getDocumentCountByStatus(DocumentStatus::NEW),
        DocumentStatsMetadata::WAITING_FOR_ARCHIVATION_COUNT => $documentModel->getDocumentCountByStatus(DocumentStatus::ARCHIVATION_APPROVED)
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
        throw new ValueIsNullException('$user');
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