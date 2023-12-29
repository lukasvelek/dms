<?php

use DMS\Constants\CacheCategories;
use DMS\Core\CacheManager;
use DMS\Core\CypherManager;
use DMS\Helpers\ArrayStringHelper;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

require_once('Ajax.php');

$ucm = new CacheManager(true, CacheCategories::USERS, '../../logs/', '../../cache/');

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

            if($documentBulkActionAuthorizator->canDelete($idDocument, null, false) && 
                (is_null($canDelete) || $canDelete) &&
                !$inProcess) {
                $canDelete = true;
            } else {
                $canDelete = false;
            }

            if($documentBulkActionAuthorizator->canApproveArchivation($idDocument, null, false) && 
                (is_null($canApproveArchivation) || $canApproveArchivation) &&
                !$inProcess) {
                $canApproveArchivation = true;
            } else {
                $canApproveArchivation = false;
            }

            if($documentBulkActionAuthorizator->canDeclineArchivation($idDocument, null, false) &&
                (is_null($canDeclineArchivation) || $canDeclineArchivation) &&
                !$inProcess) {
                $canDeclineArchivation = true;
            } else {
                $canDeclineArchivation = false;
            }

            if($documentBulkActionAuthorizator->canArchive($idDocument, null, false) &&
                (is_null($canArchive) || $canArchive) &&
                !$inProcess) {
                $canArchive = true;
            } else {
                $canArchive = false;
            }

            if($documentBulkActionAuthorizator->canSuggestForShredding($idDocument, null, false) &&
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
    global $documentCommentModel, $userModel, $ucm;

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

            if($canDelete == '1') {
                $deleteLink = '<a class="general-link" style="cursor: pointer" onclick="deleteDocumentComment(\'' . $comment->getId() . '\', \'' . $idDocument . '\', \'' . $canDelete . '\');">Delete</a>';

                $codeArr[] = '<p class="comment-info">Author: ' . $authorLink . ' | Date posted: ' . $comment->getDateCreated() . ' | ' . $deleteLink . '</p>';
            } else {
                $codeArr[] = '<p class="comment-info">Author: ' . $authorLink . ' | Date posted: ' . $comment->getDateCreated() . '</p>';
            }

            $codeArr[] = '</article>';
        }
    }

    echo ArrayStringHelper::createUnindexedStringFromUnindexedArray($codeArr);
}

function sendComment() {
    global $documentCommentModel, $userModel, $documentCommentRepository;

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

    if($canDelete == '1') {
        $deleteLink = '<a class="general-link" style="cursor: pointer" onclick="deleteComment(\'' . $comment->getId() . '\', \'' . $idDocument . '\', \'' . $canDelete . '\');">Delete</a>';

        $codeArr[] = '<p class="comment-info">Author: ' . $authorLink . ' | Date posted: ' . $comment->getDateCreated() . ' | ' . $deleteLink . '</p>';
    } else {
        $codeArr[] = '<p class="comment-info">Author: ' . $authorLink . ' | Date posted: ' . $comment->getDateCreated() . '</p>';
    }

    $codeArr[] = '</article>';

    echo ArrayStringHelper::createUnindexedStringFromUnindexedArray($codeArr);
}

function search() {
    global $documentModel, $userModel, $folderModel, $metadataModel, $ucm;

    $idFolder = htmlspecialchars($_POST['idFolder']);

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

        $tb = TableBuilder::getTemporaryObject();

        $tb->showRowBorder();

        $headers = array(
            '<input type="checkbox" id="select-all" onchange="selectAllDocumentEntries()">',
            'Actions',
            'Name',
            'Author',
            'Status',
            'Folder'
        );

        $headerRow = null;

        if($idFolder == 'null') {
            $idFolder = null;
        }

        $dbStatuses = $metadataModel->getAllValuesForIdMetadata($metadataModel->getMetadataByName('status', 'documents')->getId());
        
        $documents = $documentModel->getDocumentsForName($query, $idFolder, $filter);

        if(empty($documents)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($documents as $document) {
                $actionLinks = array(
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showInfo', 'id' => $document->getId()), 'Information'),
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showEdit', 'id' => $document->getId()), 'Edit')
                );

                $shared = false;

                if(!$shared) {
                    $actionLinks[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showShare', 'id' => $document->getId()), 'Share');
                }

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

                $docuRow->addCol($tb->createCol()->setText('<input type="checkbox" id="select" name="select[]" value="' . $document->getId() . '" onupdate="drawDocumentBulkActions()" onchange="drawDocumentBulkActions()">'));

                foreach($actionLinks as $actionLink) {
                    $docuRow->addCol($tb->createCol()->setText($actionLink));
                }

                $author = null;

                $cacheAuthor = $ucm->loadUserByIdFromCache($document->getIdAuthor());

                if(is_null($cacheAuthor)) {
                    $author = $userModel->getUserById($document->getIdAuthor());
                    $ucm->saveUserToCache($author);
                } else {
                    $author = $cacheAuthor;
                }

                $docuRow->addCol($tb->createCol()->setText($document->getName()))
                        ->addCol($tb->createCol()->setText($author->getFullname()))
                ;

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
    } else {
        $tb = TableBuilder::getTemporaryObject();

        $tb->showRowBorder();

        $headers = array(
            '<input type="checkbox" id="select-all" onchange="selectAllDocumentEntries()">',
            'Actions',
            'Name',
            'Author',
            'Status',
            'Folder'
        );
    
        $headerRow = null;
    
        if($idFolder == 'null') {
            $idFolder = null;
        }

        $dbStatuses = $metadataModel->getAllValuesForIdMetadata($metadataModel->getMetadataByName('status', 'documents')->getId());

        $documents = $documentModel->getStandardDocuments($idFolder, $filter, ($page * 20));

        $skip = 0;
        $maxSkip = ($page - 1) * 20;
    
        if(empty($documents)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($documents as $document) {
                if($skip < $maxSkip) {
                    $skip++;
                    continue;
                }

                $actionLinks = array(
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showInfo', 'id' => $document->getId()), 'Information'),
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showEdit', 'id' => $document->getId()), 'Edit'),
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showShare', 'id' => $document->getId()), 'Share')
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
    
                $docuRow->addCol($tb->createCol()->setText('<input type="checkbox" id="select" name="select[]" value="' . $document->getId() . '" onupdate="drawDocumentBulkActions()" onchange="drawDocumentBulkActions()">'));
                
                foreach($actionLinks as $actionLink) {
                    $docuRow->addCol($tb->createCol()->setText($actionLink));
                }

                $author = null;

                $cacheAuthor = $ucm->loadUserByIdFromCache($document->getIdAuthor());

                if(is_null($cacheAuthor)) {
                    $author = $userModel->getUserById($document->getIdAuthor());
                    $ucm->saveUserToCache($author);
                } else {
                    $author = $cacheAuthor;
                }

                $docuRow->addCol($tb->createCol()->setText($document->getName()))
                        ->addCol($tb->createCol()->setText($author->getFullname()))
                ;
    
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
}

function searchDocumentsSharedWithMe() {
    global $documentModel, $folderModel, $user, $userModel, $metadataModel;

    $tb = TableBuilder::getTemporaryObject();

    $headers = array(
        '<input type="checkbox" id="select-all" onchange="selectAllDocumentEntries()">',
        'Actions',
        'Name',
        'Author',
        'Status',
        'Folder',
        'Shared from',
        'Shared to',
        'Shared by'
    );

    $headerRow = null;
    $documents = [];

    if(!is_null($user)) {
        $documents = $documentModel->getSharedDocumentsWithUser($user->getId());
    }

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

            if(isset($user)) {
                $documentSharing = $documentModel->getDocumentSharingByIdDocumentAndIdUser($user->getId(), $document->getId());

                $documentSharingAuthor = $userModel->getUserById($documentSharing['id_author']);

                $dateFrom = date('Y-m-d', strtotime($documentSharing['date_from']));
                $dateTo = date('Y-m-d', strtotime($documentSharing['date_to']));

                $docuRow->addCol($tb->createCol()->setText($dateFrom))
                        ->addCol($tb->createCol()->setText($dateTo))
                        ->addCol($tb->createCol()->setText($documentSharingAuthor->getFullname()))
                ;
            } else {
                $docuRow->addCol($tb->createCol()->setText('-'))
                        ->addCol($tb->createCol()->setText('-'))
                        ->addCol($tb->createCol()->setText('-'))
                ;
            }
                
            $tb->addRow($docuRow);
        }
    }

    echo $tb->build();
}

function generateDocuments() {
    global $documentModel, $user, $app;

    if($user == null ||
       !$app::SYSTEM_DEBUG) {
        exit;
    }

    $id_folder = $_GET['id_folder'];
    $count = $_GET['count'];

    $data = [];
    for($i = 0; $i < $count; $i++) {
        $data[$i] = array(
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
            $data[$i]['id_folder'] = $id_folder;
        }
    }

    foreach($data as $index => $d) {
        $documentModel->insertNewDocument($d);
    }
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