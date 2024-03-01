<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\DocumentReportStatus;
use DMS\Constants\FileStorageTypes;
use DMS\Constants\UserActionRights;
use DMS\Core\AppConfiguration;
use DMS\Modules\APresenter;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;

class DocumentReports extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('DocumentReports', 'Document reports');

        $this->getActionNamesFromClass($this);
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
                if(isset($row['file_src'])) {
                    $fileSrc = $row['file_src'];
                }
                $entry = new class($row['id'], $row['id_user'], $row['date_created'], $row['date_updated'], $row['status'], $row['sql_string'], $fileSrc) {
                    private int $id;
                    private int $idUser;
                    private string $dateCreated;
                    private string $dateUpdated;
                    private int $status;
                    private string $sqlString;
                    private ?string $fileSrc;

                    public function __construct(int $id, int $idUser, string $dateCreated, string $dateUpdated, int $status, string $sqlString, ?string $fileSrc) {
                        $this->id = $id;
                        $this->idUser = $idUser;
                        $this->dateCreated = $dateCreated;
                        $this->dateUpdated = $dateUpdated;
                        $this->status = $status;
                        $this->sqlString = $sqlString;
                        $this->fileSrc = $fileSrc;
                    }

                    public function getId() {
                        return $this->id;
                    }

                    public function getIdUser() {
                        return $this->idUser;
                    }

                    public function getDateCreated() {
                        return $this->dateCreated;
                    }

                    public function getDateUpdated() {
                        return $this->dateUpdated;
                    }

                    public function getStatus() {
                        return $this->status;
                    }

                    public function getSqlString() {
                        return $this->sqlString;
                    }

                    public function getFileSrc() {
                        return $this->fileSrc;
                    }
                };

                $entries[] = $entry;
            }

            return $entries;
        };

        $canDeleteDocumentReportQueueEntry = $app->actionAuthorizator->checkActionRight(UserActionRights::DELETE_DOCUMENT_REPORT_QUEUE_ENTRY);

        $gb = new GridBuilder();

        $gb->addColumns(['date_created' => 'Date created', 'date_updated' => 'Date updated', 'status' => 'Status']);
        $gb->addDataSourceCallback($dataCallback);
        $gb->addOnColumnRender('date_created', function(object $obj) {
            return $obj->getDateCreated();
        });
        $gb->addOnColumnRender('date_updated', function(object $obj) {
            return $obj->getDateUpdated();
        });
        $gb->addOnColumnRender('status', function(object $obj) {
            return DocumentReportStatus::$texts[$obj->getStatus()];
        });
        $gb->addAction(function(object $obj) use ($fileManager) {
            if($obj->getStatus() == DocumentReportStatus::FINISHED && $fileManager->fileExists($obj->getFileSrc())) {
                return '<a class="general-link" href="' . $obj->getFileSrc() . '">Download</a>';
            } else {
                return '-';
            }
        });
        $gb->addAction(function(object $obj) use ($canDeleteDocumentReportQueueEntry) {
            if($canDeleteDocumentReportQueueEntry &&
                in_array($obj->getStatus(), [DocumentReportStatus::FINISHED, DocumentReportStatus::IN_PROGRESS])) {
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

        $app->flashMessageIfNotIsset(['id']);

        $id = $this->get('id');

        $row = $app->documentModel->getDocumentReportQueueEntryById($id);

        if($row === NULL) {
            die('ROW IS NULL');
        }

        $app->documentModel->deleteDocumentReportQueueEntry($id);

        $app->flashMessage('Deleted generated document report.');
        $app->redirect('showAll');
    }
}

?>