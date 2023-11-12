<?php

require_once('Ajax.php');

if(isset($_POST['idComment'])) {
    $idComment = htmlspecialchars($_POST['idComment']);
    $documentCommentModel->deleteComment($idComment);

    echo true;
}

?>