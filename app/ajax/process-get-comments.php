<?php

use DMS\Helpers\ArrayStringHelper;
use DMS\UI\LinkBuilder;

require_once('Ajax.php');

if(isset($_GET['idProcess']) && isset($_GET['canDelete'])) {
    $idProcess = htmlspecialchars($_GET['idProcess']);
    $canDelete = htmlspecialchars($_GET['canDelete']);

    $comments = $processCommentModel->getCommentsForIdProcess($idProcess);

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
                //$deleteLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:askToDeleteComment', 'id_document' => $idDocument, 'id_comment' => $comment->getId()), 'Delete');
                $deleteLink = '<a class="general-link" style="cursor: pointer" onclick="deleteComment(\'' . $comment->getId() . '\', \'' . $idProcess . '\', \'' . $canDelete . '\');">Delete</a>';

                $codeArr[] = '<p class="comment-info">Author: ' . $authorLink . ' | Date posted: ' . $comment->getDateCreated() . ' | ' . $deleteLink . '</p>';
            } else {
                $codeArr[] = '<p class="comment-info">Author: ' . $authorLink . ' | Date posted: ' . $comment->getDateCreated() . '</p>';
            }

            $codeArr[] = '</article>';
        }
    }

    echo ArrayStringHelper::createUnindexedStringFromUnindexedArray($codeArr);
}

?>