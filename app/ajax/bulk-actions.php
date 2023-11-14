<?php

use DMS\Constants\BulkActionRights;

require_once('Ajax.php');

function createLink(string $url, string $text) {
    $code = '<a style="color: black; text-decoration: none" href="' . $url . '">';
    $code .= '<div style="position: relative; left: 10px; top: 10px; width: 75px; height: 75px; background-color: white; border: 1px solid black; border-radius: 25px; text-align: center">';
    $code .= '<span style="position: relative; top: 22.5px;">' . $text . '</span>';
    $code .= '</div></a>';

    return $code;
}

if(isset($_GET['idDocuments'])) {
    $idDocuments = $_GET['idDocuments'];

    $text = '';

    $canDelete = null;
    
    if(!is_null($user)) {
        foreach($idDocuments as $idDocument) {
            $inProcess = $processComponent->checkIfDocumentIsInProcess($idDocument);

            if($bulkActionAuthorizator->checkBulkActionRight(BulkActionRights::DELETE_DOCUMENTS, null, false) && !$processComponent->checkIfDocumentIsInProcess($idDocument) && (is_null($canDelete) || $canDelete)) {
                //$text = createLink("?page=UserModule:Documents:showAll", 'Delete');
                $canDelete = true;
            } else {
                $canDelete = false;
            }
        }
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

        $text .= createLink($link, 'Delete');
    }

    echo $text;
}

?>