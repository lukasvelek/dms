<?php

use DMS\Constants\BulkActionRights;

require_once('Ajax.php');

if(isset($_GET['idDocuments'])) {
    $idDocuments = $_GET['idDocuments'];

    $text = '';
    
    if(!is_null($user)) {
        foreach($idDocuments as $idDocument) {
            $inProcess = $processComponent->checkIfDocumentIsInProcess($idDocument);

            if($bulkActionAuthorizator->ajaxCheckRight(BulkActionRights::DELETE_DOCUMENTS, $user->getId(), $userRightModel, $groupRightModel, $groupUserModel) && !$inProcess) {
                $text = 'DELETE';
            }  
        }
        /*if($bulkActionAuthorizator->ajaxCheckRight(BulkActionRights::DELETE_DOCUMENTS, $user->getId(), $userRightModel, $groupRightModel, $groupUserModel)) {
            $text
        }*/
    }

    echo $text;
}

?>