<?php

use DMS\Constants\CacheCategories;
use DMS\Constants\ProcessTypes;
use DMS\Core\AppConfiguration;
use DMS\Core\CacheManager;
use DMS\Entities\Process;
use DMS\Helpers\ArrayStringHelper;
use DMS\Helpers\DatetimeFormatHelper;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

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

function deleteComment() {
    global $processCommentModel;

    $idComment = htmlspecialchars($_POST['idComment']);

    $processCommentModel->deleteComment($idComment);
}

function sendComment() {
    global $processCommentModel, $userModel, $ucm, $user;

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

    $dateCreated = $comment->getDateCreated();
    if(!is_null($user)) {
        $dateCreated = DatetimeFormatHelper::formatDateByUserDefaultFormat($dateCreated, $user);
    } else {
        $dateCreated = DatetimeFormatHelper::formatDateByFormat($dateCreated, AppConfiguration::getDefaultDatetimeFormat());
    }

    if($canDelete == '1') {
        $deleteLink = '<a class="general-link" style="cursor: pointer" onclick="deleteProcessComment(\'' . $comment->getId() . '\', \'' . $idProcess . '\', \'' . $canDelete . '\');">Delete</a>';

        $codeArr[] = '<p class="comment-info">Author: ' . $authorLink . ' | Date posted: ' . $dateCreated . ' | ' . $deleteLink . '</p>';
    } else {
        $codeArr[] = '<p class="comment-info">Author: ' . $authorLink . ' | Date posted: ' . $dateCreated . '</p>';
    }

    $codeArr[] = '</article>';

    echo ArrayStringHelper::createUnindexedStringFromUnindexedArray($codeArr);
}

function getComments() {
    global $processCommentModel, $userModel, $ucm, $user;

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

            $dateCreated = $comment->getDateCreated();
            if(!is_null($user)) {
                $dateCreated = DatetimeFormatHelper::formatDateByUserDefaultFormat($dateCreated, $user);
            } else {
                $dateCreated = DatetimeFormatHelper::formatDateByFormat($dateCreated, AppConfiguration::getDefaultDatetimeFormat());
            }

            if($canDelete == '1') {
                $deleteLink = '<a class="general-link" style="cursor: pointer" onclick="deleteProcessComment(\'' . $comment->getId() . '\', \'' . $idProcess . '\', \'' . $canDelete . '\');">Delete</a>';

                $codeArr[] = '<p class="comment-info">Author: ' . $authorLink . ' | Date posted: ' . $dateCreated . ' | ' . $deleteLink . '</p>';
            } else {
                $codeArr[] = '<p class="comment-info">Author: ' . $authorLink . ' | Date posted: ' . $dateCreated . '</p>';
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

    $dataSourceCallback = null;

    switch($filter) {
        case 'startedByMe':
            if($gridUseFastLoad) {
                $page -= 1;

                $firstIdProcessOnPage = $processModel->getFirstIdProcessOnAGridPage(($page * $gridSize));

                $dataSourceCallback = function() use ($processModel, $idUser, $firstIdProcessOnPage, $gridSize) {
                    return $processModel->getProcessesWhereIdUserIsAuthorFromId($firstIdProcessOnPage, $idUser, $gridSize);
                };
            } else {
                $dataSourceCallback = function() use ($processModel, $idUser, $page, $gridSize) {
                    return $processModel->getProcessesWhereIdUserIsAuthor($idUser, ($page * $gridSize));
                };
            }

            break;

        case 'waitingForMe':
            if($gridUseFastLoad) {
                $page -= 1;

                $firstIdProcessOnPage = $processModel->getFirstIdProcessOnAGridPage(($page * $gridSize));

                $dataSourceCallback = function() use ($processModel, $idUser, $gridSize, $firstIdProcessOnPage) {
                    return $processModel->getProcessesWithIdUserFromId($firstIdProcessOnPage, $idUser, $gridSize);
                };
            } else {
                $dataSourceCallback = function() use ($processModel, $idUser, $page, $gridSize) {
                    return $processModel->getProcessesWithIdUser($idUser, ($page * $gridSize));
                };
            }

            break;

        case 'finished':
            if($gridUseFastLoad) {
                $page -= 1;

                $firstIdProcessOnPage = $processModel->getFirstIdProcessOnAGridPage(($page * $gridSize));

                $dataSourceCallback = function() use ($processModel, $idUser, $firstIdProcessOnPage, $gridSize) {
                    return $processModel->getFinishedProcessesWithIdUserFromId($firstIdProcessOnPage, $idUser, $gridSize);
                };
            } else {
                $dataSourceCallback = function() use ($processModel, $idUser, $page, $gridSize) {
                    return $processModel->getFinishedProcessesWithIdUser($idUser, ($page * $gridSize));
                };
            }

            break;
    }

    $gb = new GridBuilder();

    $gb->addColumns(['type' => 'Name', 'workflow1' => 'Workflow 1', 'workflow2' => 'Workflow 2', 'workflow3' => 'Workflow 3', 'workflow4' => 'Workflow 4', 'workflowStatus' => 'Workflow status', 'currentOfficer' => 'Current officer']);
    $gb->addOnColumnRender('type', function(Process $process) {
        return ProcessTypes::$texts[$process->getType()];
    });
    $gb->addOnColumnRender('workflow1', function(Process $process) use ($userModel, $ucm) {
        if($process->getWorkflowStep(0) !== NULL) {
            $user = $ucm->loadUserByIdFromCache($process->getWorkflowStep(0));

            if(is_null($user)) {
                $user = $userModel->getUserById($process->getWorkflowStep(0));

                $ucm->saveUserToCache($user);
            }

            return $user->getFullname();
        } else {
            return '-';
        }
    });
    $gb->addOnColumnRender('workflow2', function(Process $process) use ($userModel, $ucm){
        if($process->getWorkflowStep(1) !== NULL) {
            $user = $ucm->loadUserByIdFromCache($process->getWorkflowStep(1));

            if(is_null($user)) {
                $user = $userModel->getUserById($process->getWorkflowStep(1));

                $ucm->saveUserToCache($user);
            }

            return $user->getFullname();
        } else {
            return '-';
        }
    });
    $gb->addOnColumnRender('workflow3', function(Process $process) use ($userModel, $ucm) {
        if($process->getWorkflowStep(2) !== NULL) {
            $user = $ucm->loadUserByIdFromCache($process->getWorkflowStep(2));

            if(is_null($user)) {
                $user = $userModel->getUserById($process->getWorkflowStep(2));

                $ucm->saveUserToCache($user);
            }

            return $user->getFullname();
        } else {
            return '-';
        }
    });
    $gb->addOnColumnRender('workflow4', function(Process $process) use ($userModel, $ucm) {
        if($process->getWorkflowStep(3) !== NULL) {
            $user = $ucm->loadUserByIdFromCache($process->getWorkflowStep(3));

            if(is_null($user)) {
                $user = $userModel->getUserById($process->getWorkflowStep(3));

                $ucm->saveUserToCache($user);
            }

            return $user->getFullname();
        } else {
            return '-';
        }
    });
    $gb->addOnColumnRender('currentOfficer', function(Process $process) use ($userModel, $ucm) {
        $idUser = $process->getWorkflowStep(($process->getWorkflowStatus() - 1));

        $user = $ucm->loadUserByIdFromCache($idUser);

        if(is_null($user)) {
            $user = $userModel->getUserById($idUser);
            
            $ucm->saveUserToCache($user);
        }

        return $user->getFullname();
    });
    $gb->addAction(function(Process $process) {
        return LinkBuilder::createAdvLink(['page' => 'UserModule:SingleProcess:showProcess', 'id' => $process->getId()], 'Open');
    });
    $gb->addDataSourceCallback($dataSourceCallback);

    echo $gb->build();
}

exit;

?>