<?php

use DMS\Constants\CacheCategories;
use DMS\Constants\ProcessTypes;
use DMS\Core\CacheManager;
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

function deleteComment() {
    global $processCommentModel;

    $idComment = htmlspecialchars($_POST['idComment']);

    $processCommentModel->deleteComment($idComment);
}

function sendComment() {
    global $processCommentModel, $userModel, $ucm;

    $text = htmlspecialchars($_POST['commentText']);
    $idAuthor = htmlspecialchars($_POST['idAuthor']);
    $idProcess = htmlspecialchars($_POST['idProcess']);
    $canDelete = htmlspecialchars($_POST['canDelete']);

    $data = array(
        'id_author' => $idAuthor,
        'id_process' => $idProcess,
        'text' => $text
    );

    $processCommentModel->insertComment($data);
    $comment = $processCommentModel->getLastInsertedCommentForIdUserAndIdProcess($idAuthor, $idProcess);

    $author = null;

    $cacheAuthor = $ucm->loadUserByIdFromCache($comment->getIdAuthor());

    if(is_null($cacheAuthor)) {
        $author = $userModel->getUserById($idAuthor);
        
        $ucm->saveUserToCache($author);
    } else {
        $author = $cacheAuthor;
    }

    $authorLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showProfile', 'id' => $comment->getIdAuthor()), $author->getFullname());

    $codeArr[] = '<hr>';
    $codeArr[] = '<article id="comment' . $comment->getId() . '">';
    $codeArr[] = '<p class="comment-text">' . $comment->getText() . '</p>';

    if($canDelete == '1') {
        $deleteLink = '<a class="general-link" style="cursor: pointer" onclick="deleteProcessComment(\'' . $comment->getId() . '\', \'' . $idProcess . '\', \'' . $canDelete . '\');">Delete</a>';

        $codeArr[] = '<p class="comment-info">Author: ' . $authorLink . ' | Date posted: ' . $comment->getDateCreated() . ' | ' . $deleteLink . '</p>';
    } else {
        $codeArr[] = '<p class="comment-info">Author: ' . $authorLink . ' | Date posted: ' . $comment->getDateCreated() . '</p>';
    }

    $codeArr[] = '</article>';

    echo ArrayStringHelper::createUnindexedStringFromUnindexedArray($codeArr);
}

function getComments() {
    global $processCommentModel, $userModel, $ucm;

    $idProcess = htmlspecialchars($_GET['idProcess']);
    $canDelete = htmlspecialchars($_GET['canDelete']);

    $comments = $processCommentModel->getCommentsForIdProcess($idProcess);

    if(empty($comments)) {
        $codeArr[] = '<hr>';
        $codeArr[] = 'No comments found!';
    } else {
        foreach($comments as $comment) {
            $author = null;

            $cacheAuthor = $ucm->loadUserByIdFromCache($comment->getIdAuthor());

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
                $deleteLink = '<a class="general-link" style="cursor: pointer" onclick="deleteProcessComment(\'' . $comment->getId() . '\', \'' . $idProcess . '\', \'' . $canDelete . '\');">Delete</a>';

                $codeArr[] = '<p class="comment-info">Author: ' . $authorLink . ' | Date posted: ' . $comment->getDateCreated() . ' | ' . $deleteLink . '</p>';
            } else {
                $codeArr[] = '<p class="comment-info">Author: ' . $authorLink . ' | Date posted: ' . $comment->getDateCreated() . '</p>';
            }

            $codeArr[] = '</article>';
        }
    }

    echo ArrayStringHelper::createUnindexedStringFromUnindexedArray($codeArr);
}

function search() {
    global $userModel, $processModel, $user, $ucm, $gridSize, $gridUseFastLoad;

    $filter = 'waitingForMe';
    $page = 1;

    if(is_null($user)) {
        echo 'User is null';
        return;
    }

    $idUser = $user->getId();

    if(isset($_POST['filter'])) {
        $filter = htmlspecialchars($_POST['filter']);
    }

    if(isset($_POST['page'])) {
        $page = (int)(htmlspecialchars($_POST['page']));
    }

    $tb = TableBuilder::getTemporaryObject();

    $headers = array(
        'Actions',
        'Name',
        'Workflow 1',
        'Workflow 2',
        'Workflow 3',
        'Workflow 4',
        'Workflow status',
        'Current officer',
        'Type'
    );

    $headerRow = null;

    $processes = [];

    switch($filter) {
        case 'startedByMe':
            if($gridUseFastLoad) {
                $page -= 1;

                $firstIdProcessOnPage = $processModel->getFirstIdProcessOnAGridPage(($page * $gridSize));

                $processes = $processModel->getProcessesWhereIdUserIsAuthorFromId($firstIdProcessOnPage, $idUser, $gridSize);
            } else {
                $processes = $processModel->getProcessesWhereIdUserIsAuthor($idUser, ($page * $gridSize));
            }

            break;

        case 'waitingForMe':
            if($gridUseFastLoad) {
                $page -= 1;

                $firstIdProcessOnPage = $processModel->getFirstIdProcessOnAGridPage(($page * $gridSize));

                $processes = $processModel->getProcessesWithIdUserFromId($firstIdProcessOnPage, $idUser, $gridSize);
            } else {
                $processes = $processModel->getProcessesWithIdUser($idUser, ($page * $gridSize));
            }

            break;

        case 'finished':
            if($gridUseFastLoad) {
                $page -= 1;

                $firstIdProcessOnPage = $processModel->getFirstIdProcessOnAGridPage(($page * $gridSize));

                $processes = $processModel->getProcessesWithIdUserFromId($firstIdProcessOnPage, $idUser, $gridSize);
            } else {
                $processes = $processModel->getFinishedProcessesWithIdUser($idUser, ($page * $gridSize));
            }

            break;
    }

    $skip = 0;
    $maxSkip = ($page - 1) * $gridSize;

    if(empty($processes)) {
        $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
    } else {
        foreach($processes as $process) {
            if($skip < $maxSkip) {
                $skip++;
                continue;
            }

            $actionLinks = array(
                LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleProcess:showProcess', 'id' => $process->getId()), 'Open')
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

            $procRow = $tb->createRow();

            foreach($actionLinks as $actionLink) {
                $procRow->addCol($tb->createCol()->setText($actionLink));
            }

            if($process->getWorkflowStep(0) != null) {
                $user = null;

                $cacheUser = $ucm->loadUserByIdFromCache($process->getWorkflowStep(0));

                if(is_null($cacheUser)) {
                    $user = $userModel->getUserById($process->getWorkflowStep(0));

                    $ucm->saveUserToCache($user);
                } else {
                    $user = $cacheUser;
                }

                $workflow1User = $user->getFullname();
            } else {
                $workflow1User = '-';
            }

            if($process->getWorkflowStep(1) != null) {
                $user = null;

                $cacheUser = $ucm->loadUserByIdFromCache($process->getWorkflowStep(1));

                if(is_null($cacheUser)) {
                    $user = $userModel->getUserById($process->getWorkflowStep(1));

                    $ucm->saveUserToCache($user);
                } else {
                    $user = $cacheUser;
                }

                $workflow2User = $user->getFullname();
            } else {
                $workflow2User = '-';
            }

            if($process->getWorkflowStep(2) != null) {
                $user = null;

                $cacheUser = $ucm->loadUserByIdFromCache($process->getWorkflowStep(2));

                if(is_null($cacheUser)) {
                    $user = $userModel->getUserById($process->getWorkflowStep(2));

                    $ucm->saveUserToCache($user);
                } else {
                    $user = $cacheUser;
                }

                $workflow3User = $user->getFullname();
            } else {
                $workflow3User = '-';
            }

            if($process->getWorkflowStep(3) != null) {
                $user = null;

                $cacheUser = $ucm->loadUserByIdFromCache($process->getWorkflowStep(3));

                if(is_null($cacheUser)) {
                    $user = $userModel->getUserById($process->getWorkflowStep(3));

                    $ucm->saveUserToCache($user);
                } else {
                    $user = $cacheUser;
                }

                $workflow4User = $user->getFullname();
            } else {
                $workflow4User = '-';
            }

            $procRow->addCol($tb->createCol()->setText(ProcessTypes::$texts[$process->getType()]))
                    ->addCol($tb->createCol()->setText($workflow1User))
                    ->addCol($tb->createCol()->setText($workflow2User))
                    ->addCol($tb->createCol()->setText($workflow3User))
                    ->addCol($tb->createCol()->setText($workflow4User))
                    ->addCol($tb->createCol()->setText($process->getWorkflowStatus() ?? '-'))
                    ->addCol($tb->createCol()->setText(${'workflow' . $process->getWorkflowStatus() . 'User'}))
                    ->addCol($tb->createCol()->setText(ProcessTypes::$texts[$process->getType()]))
            ;

            $tb->addRow($procRow);
        }
    }

    echo $tb->build();
}

exit;

?>