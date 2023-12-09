<?php

use DMS\Helpers\ArrayStringHelper;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

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
                (is_null($canDelete) || $canDelete)) {
                $canDelete = true;
            } else {
                $canDelete = false;
            }

            if($documentBulkActionAuthorizator->canApproveArchivation($idDocument, null, false) && 
                (is_null($canApproveArchivation) || $canApproveArchivation)) {
                $canApproveArchivation = true;
            } else {
                $canApproveArchivation = false;
            }

            if($documentBulkActionAuthorizator->canDeclineArchivation($idDocument, null, false) &&
                (is_null($canDeclineArchivation) || $canDeclineArchivation)) {
                $canDeclineArchivation = true;
            } else {
                $canDeclineArchivation = false;
            }

            if($documentBulkActionAuthorizator->canArchive($idDocument, null, false) &&
                (is_null($canArchive) || $canArchive)) {
                $canArchive = true;
            } else {
                $canArchive = false;
            }

            if($documentBulkActionAuthorizator->canSuggestForShredding($idDocument, null, false) &&
              (is_null($canSuggestShredding) || $canSuggestShredding)) {
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
    global $documentCommentModel, $userModel;

    $idDocument = htmlspecialchars($_GET['idDocument']);
    $canDelete = htmlspecialchars($_GET['canDelete']);

    $comments = $documentCommentModel->getCommentsForIdDocument($idDocument);
    
    $codeArr = [];

    if(empty($comments)) {
        $codeArr[] = '<hr>';
        $codeArr[] = 'No comments found!';
    } else {
        foreach($comments as $comment) {
            $author = $userModel->getUserById($comment->getIdAuthor());

            $authorLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showProfile', 'id' => $comment->getIdAuthor()), $author->getFullname());
            
            $codeArr[] = '<article id="comment' . $comment->getId() . '">';
            $codeArr[] = '<hr>';
            $codeArr[] = '<p class="comment-text">' . $comment->getText() . '</p>';

            if($canDelete == '1') {
                $deleteLink = '<a class="general-link" style="cursor: pointer" onclick="deleteComment(\'' . $comment->getId() . '\', \'' . $idDocument . '\', \'' . $canDelete . '\');">Delete</a>';

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
    global $documentModel, $userModel, $folderModel, $metadataModel;

    $idFolder = htmlspecialchars($_POST['idFolder']);

    if(isset($_POST['q'])) {
        $query = htmlspecialchars($_POST['q']);

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

        if($idFolder == 'null') {
            $idFolder = null;
        }

        $documents = $documentModel->getDocumentsForName($query, $idFolder);

        if(empty($documents)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($documents as $document) {
                $actionLinks = array(
                    '<input type="checkbox" id="select" name="select[]" value="' . $document->getId() . '" onchange="drawBulkActions()">',
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
    } else {
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
    
        if($idFolder == 'null') {
            $idFolder = null;
        }
    
        $documents = [];
    
        if(is_null($idFolder)) {
            $documents = $documentModel->getStandardDocuments();
        } else {
            $documents = $documentModel->getStandardDocumentsInIdFolder($idFolder);
        }
    
        if(empty($documents)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($documents as $document) {
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
    
                $docuRow->addCol($tb->createCol()->setText('<input type="checkbox" id="select" name="select[]" value="' . $document->getId() . '" onupdate="drawBulkActions()" onchange="drawBulkActions()">'));
                
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
            $docuRow->addCol($tb->createCol()->setText('<input type="checkbox" id="select" name="select[]" value="' . $document->getId() . '" onchange="drawBulkActions()">'));

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