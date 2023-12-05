<?php

use DMS\Constants\UserActionRights;
use DMS\Helpers\ArrayStringHelper;
use DMS\UI\LinkBuilder;

require_once('Ajax.php');

if(isset($_POST['commentText']) && isset($_POST['idAuthor']) && isset($_POST['idProcess']) && isset($_POST['canDelete'])) {
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

    $author = $userModel->getUserById($idAuthor);

    $authorLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showProfile', 'id' => $comment->getIdAuthor()), $author->getFullname());

    $codeArr[] = '<hr>';
    $codeArr[] = '<article id="comment' . $comment->getId() . '">';
    $codeArr[] = '<p class="comment-text">' . $comment->getText() . '</p>';

    if($canDelete == '1') {
        //$deleteLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:askToDeleteComment', 'id_document' => $idDocument, 'id_comment' => $comment->getId()), 'Delete');
        $deleteLink = '<a class="general-link" style="cursor: pointer" onclick="deleteComment(\'' . $comment->getId() . '\', \'' . $idProcess . '\', \'' . $canDelete . '\');">Delete</a>';

        $codeArr[] = '<p class="comment-info">Author: ' . $authorLink . ' | Date posted: ' . $comment->getDateCreated() . ' | ' . $deleteLink . '</p>';
    } else {
        $codeArr[] = '<p class="comment-info">Author: ' . $authorLink . ' | Date posted: ' . $comment->getDateCreated() . '</p>';
    }

    $codeArr[] = '</article>';

    echo ArrayStringHelper::createUnindexedStringFromUnindexedArray($codeArr);
}

?>