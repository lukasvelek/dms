<?php

use DMS\Constants\BulkActionRights;
use DMS\Constants\DocumentStatus;

require_once('Ajax.php');

function createLink(string $url, string $text, int $left, int $top) {
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

function createBlankLink(int $left, int $top) {
    $code = '
    <div style="width: 75px; height: 75px; position: relative; left: ' . $left . 'px; top: ' . $top . 'px">
    </div>
';

return $code;
}

$bulkActions = [];

if(isset($_GET['idDocuments'])) {
    $idDocuments = $_GET['idDocuments'];

    $text = '';

    $canDelete = null;
    $canApproveArchivation = null;
    $canDeclineArchivation = null;
    $canArchive = null;
    $canSuggestShredding = null;
    /*$canApproveShredding = null;
    $canDeclineShredding = null;
    $canShred = null;*/
    
    if(!is_null($user)) {
        foreach($idDocuments as $idDocument) {
            $inProcess = $processComponent->checkIfDocumentIsInProcess($idDocument);

            $document = $documentModel->getDocumentById($idDocument);

            if( $documentBulkActionAuthorizator->canDelete($idDocument, null, false) && 
                (is_null($canDelete) || $canDelete)) {
                $canDelete = true;
            } else {
                $canDelete = false;
            }

            if( $documentBulkActionAuthorizator->canApproveArchivation($idDocument, null, false) && 
                (is_null($canApproveArchivation) || $canApproveArchivation)) {
                $canApproveArchivation = true;
            } else {
                $canApproveArchivation = false;
            }

            if( $documentBulkActionAuthorizator->canDeclineArchivation($idDocument, null, false) &&
                (is_null($canDeclineArchivation) || $canDeclineArchivation)) {
                $canDeclineArchivation = true;
            } else {
                $canDeclineArchivation = false;
            }

            if( $documentBulkActionAuthorizator->canArchive($idDocument, null, false) &&
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

            /*if($documentBulkActionAuthorizator->canApproveShredding($idDocument, null, false) &&
              (is_null($canApproveShredding) || $canApproveShredding)) {
                $canApproveShredding = true;
            } else {
                $canApproveShredding = false;
            }

            if($documentBulkActionAuthorizator->canDeclineShredding($idDocument, null, false) &&
              (is_null($canDeclineShredding) || $canDeclineShredding)) {
                $canDeclineShredding = true;
            } else {
                $canDeclineShredding = false;
            }

            if($documentBulkActionAuthorizator->canShred($idDocument, null, false) &&
              (is_null($canShred) || $canShred)) {
                $canShred = true;
            } else {
                $canShred = false;
            }*/
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

    /*if($canApproveShredding) {
        $link = '?page=UserModule:Documents:performBulkAction&';
        
        $i = 0;
        foreach($idDocuments as $idDocument) {
            if(($i + 1) == count($idDocuments)) {
                $link .= 'select[]=' . $idDocument;
            } else {
                $link .= 'select[]=' . $idDocument . '&';
            }
        }

        $link .= '&action=approve_shredding';

        $bulkActions['Approve shredding'] = $link;
    }

    if($canDeclineShredding) {
        $link = '?page=UserModule:Documents:performBulkAction&';
        
        $i = 0;
        foreach($idDocuments as $idDocument) {
            if(($i + 1) == count($idDocuments)) {
                $link .= 'select[]=' . $idDocument;
            } else {
                $link .= 'select[]=' . $idDocument . '&';
            }
        }

        $link .= '&action=decline_shredding';

        $bulkActions['Decline shredding'] = $link;
    }

    if($canShred) {
        $link = '?page=UserModule:Documents:performBulkAction&';
        
        $i = 0;
        foreach($idDocuments as $idDocument) {
            if(($i + 1) == count($idDocuments)) {
                $link .= 'select[]=' . $idDocument;
            } else {
                $link .= 'select[]=' . $idDocument . '&';
            }
        }

        $link .= '&action=shred';

        $bulkActions['Shred'] = $link;
    }*/

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
                $text .= createBlankLink($left, $top);
            } else {
                $text .= createLink($url, $name, $left, $top);
            }
        } else {
            $nextLineTop = $br * -130;
            $left = ($x * 75) + (($x + 1) * 10);
            $top = (($x * -75) + 10) + ($br * -85) + $nextLineTop;
            
            if($name == 'br') {
                $text .= createBlankLink($left, $top);
            } else {
                $text .= createLink($url, $name, $left, $top);
            }
        }

        $i++;
        $x++;
    }

    echo $text;
}

?>