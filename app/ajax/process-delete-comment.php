<?php

require_once('Ajax.php');

if(isset($_POST['idComment'])) {
    $idComment = htmlspecialchars($_POST['idComment']);
    $processCommentModel->deleteComment($idComment);

    echo true;
}

?>