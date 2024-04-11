<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\DocumentReportStatus;
use DMS\Constants\FileStorageTypes;
use DMS\Constants\Metadata\DocumentReportMetadata;
use DMS\Constants\UserActionRights;
use DMS\Entities\DocumentReportEntity;
use DMS\Modules\APresenter;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;

class DocumentReportsPresenter extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('DocumentReports', 'Document reports');

        $this->getActionNamesFromClass($this);
    }

    protected function showReportsForAllUsers() {
        global $app;

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/documents/document-filter-grid.html');

        $documentModel = $app->documentModel;
        $userRepository = $app->userRepository;

        $dataCallback = function() use ($documentModel) {
            return $documentModel->getDocumentReportQueueEntries();
        };

        $gb = new GridBuilder();

        $gb->addColumns(['user' => 'User', 'date_created' => 'Date created', 'date_updated' => 'Date updated', 'status' => 'Status']);
        $gb->addDataSourceCallback($dataCallback);
        $gb->addOnColumnRender('user', function(DocumentReportEntity $dre) use ($userRepository) {
            $user = $userRepository->getUserById($dre->getIdUser());

            return $user->getFullname();
        });
        $gb->addOnColumnRender('date_created', function(DocumentReportEntity $obj) {
            return $obj->getDateCreated();
        });
        $gb->addOnColumnRender('date_updated', function(DocumentReportEntity $obj) {
            return $obj->getDateUpdated();
        });
        $gb->addOnColumnRender('status', function(DocumentReportEntity $obj) {
            return DocumentReportStatus::$texts[$obj->getStatus()];
        });

        $data = [
            '$PAGE_TITLE$' => 'Document reports',
            '$BULK_ACTION_CONTROLLER$' => '',
            '$LINKS$' => [],
            '$FILTER_GRID$' => $gb->build()
        ];

        $this->templateManager->fill($data, $template);

        return $template;
    }
    
    protected function downloadReport() {
        global $app;

        $app->flashMessageIfNotIsset(['path']);

        $path = base64_decode($this->get('path'));

        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary"); 
        header("Content-disposition: attachment; filename=\"" . basename($path) . "\"");

        readfile($path);

        exit;
    }

    protected function showAll() {
        global $app;

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/documents/document-filter-grid.html');

        $idUser = $app->user->getId();
        $documentModel = $app->documentModel;
        $fileManager = $app->fileManager;

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

        $canDeleteDocumentReportQueueEntry = $app->actionAuthorizator->checkActionRight(UserActionRights::DELETE_DOCUMENT_REPORT_QUEUE_ENTRY);
        
        $fileStorageModel = $app->fileStorageModel;

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
        $gb->addAction(function(DocumentReportEntity $obj) use ($fileManager, $fileStorageModel) {
            if($obj->getStatus() == DocumentReportStatus::FINISHED) {
                $location = $fileStorageModel->getLocationById($obj->getIdFileStorageLocation());
                $realServerPath = $location->getPath() . $obj->getFilename();

                if($fileManager->fileExists($realServerPath)) {
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

        $data = [
            '$PAGE_TITLE$' => 'My document reports',
            '$BULK_ACTION_CONTROLLER$' => '',
            '$LINKS$' => [],
            '$FILTER_GRID$' => $gb->build()
        ];

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function deleteGeneratedReport() {
        global $app;

        $app->flashMessageIfNotIsset(['id'], true, ['page' => 'showAll']);

        if(!$app->actionAuthorizator->canDeleteDocumentReports()) {
            $app->flashMessage('You are not authorized to delete document reports.', 'error');
            $app->redirect('showAll');
        }

        $id = $this->get('id');

        $row = $app->documentModel->getDocumentReportQueueEntryById($id);

        if($row === NULL) {
            die('ROW IS NULL');
        }

        $app->documentModel->deleteDocumentReportQueueEntry($id);

        $file = $row['file_name'];

        $location = $app->fsManager->getDefaultLocationForStorageType(FileStorageTypes::DOCUMENT_REPORTS);

        $file = $location->getPath() . $file;

        $app->fileManager->deleteFile($file);

        $app->flashMessage('Deleted generated document report.');
        $app->redirect('showAll');
    }
}

?>