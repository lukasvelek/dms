<?php

use DMS\Constants\DocumentReportStatus;
use DMS\Constants\Metadata\DocumentReportMetadata;
use DMS\Constants\UserActionRights;
use DMS\Entities\DocumentReportEntity;
use DMS\Exceptions\AException;
use DMS\Exceptions\ValueIsNullException;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;

require_once('Ajax.php');
require_once('AjaxCommonMethods.php');

$action = null;

if(isset($_GET['action'])) {
    $action = htmlspecialchars($_GET['action']);
} else if(isset($_POST['action'])) {
    $action = htmlspecialchars($_POST['action']);
}

if($action == null) {
    throw new ValueIsNullException('$action');
}

try {
    echo($action());
} catch(AException $e) {
    echo('<b>Exception: </b>' . $e->getMessage() . '<br><b>Stack trace: </b>' . $e->getTraceAsString());
    exit;
}

function loadProgress() {
    global $user, $documentModel, $fm, $actionAuthorizator, $fileStorageModel;

    $idUser = $user->getId();

    $dataCallback = function() use ($idUser, $documentModel) {
        $rows = $documentModel->getDocumentReportQueueEntriesForIdUser($idUser);

        $entries = [];
        foreach($rows as $row) {
            $fileSrc = null;
            if(isset($row[DocumentReportMetadata::FILE_SRC])) {
                $fileSrc = $row[DocumentReportMetadata::FILE_SRC];
            }

            $entries[] = new DocumentReportEntity($row[DocumentReportMetadata::ID], $row[DocumentReportMetadata::ID_USER], $row[DocumentReportMetadata::DATE_CREATED], $row[DocumentReportMetadata::DATE_UPDATED], $row[DocumentReportMetadata::STATUS], $row[DocumentReportMetadata::SQL_STRING], $fileSrc, $row[DocumentReportMetadata::FILE_NAME], $row[DocumentReportMetadata::ID_FILE_STORAGE_LOCATION], $row[DocumentReportMetadata::PERCENT_FINISHED]);
        }

        return $entries;
    };

    $canDeleteDocumentReportQueueEntry = $actionAuthorizator->checkActionRight(UserActionRights::DELETE_DOCUMENT_REPORT_QUEUE_ENTRY, null, false);

    $gb = new GridBuilder();

    $gb->addColumns(['date_created' => 'Date created', 'date_updated' => 'Date updated', 'percentFinished' => '% Finished', 'status' => 'Status']);
    $gb->addDataSourceCallback($dataCallback);
    $gb->addOnColumnRender('date_created', function(DocumentReportEntity $obj) {
        return $obj->getDateCreated();
    });
    $gb->addOnColumnRender('date_updated', function(DocumentReportEntity $obj) {
        return $obj->getDateUpdated();
    });
    $gb->addOnColumnRender('status', function(DocumentReportEntity $obj) {
        return DocumentReportStatus::$texts[$obj->getStatus()];
    });
    $gb->addOnColumnRender('percentFinished', function(DocumentReportEntity $obj) {
        return $obj->getFinishedPercent() . '%';
    });
    $gb->addAction(function(DocumentReportEntity $obj) use ($fm, $fileStorageModel) {
        if($obj->getStatus() == DocumentReportStatus::FINISHED) {
            $location = $fileStorageModel->getLocationById($obj->getIdFileStorageLocation());
            if($location === NULL) {
                return '-';
            }
            $realServerPath = $location->getPath() . $obj->getFilename();

            if($fm->fileExists($realServerPath)) {
                return LinkBuilder::createAdvLink(['page' => 'downloadReport', 'path' => base64_encode($realServerPath)], 'Download');
            } else {
                return '-';
            }
        } else {
            return '-';
        }
    });
    $gb->addAction(function(DocumentReportEntity $obj) use ($canDeleteDocumentReportQueueEntry) {
        if($canDeleteDocumentReportQueueEntry) {
            return LinkBuilder::createAdvLink(['page' => 'deleteGeneratedReport', 'id' => $obj->getId()], 'Delete');
        } else {
            return '-';
        }
    });

    echo $gb->build();
}

?>