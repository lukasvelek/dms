<?php

namespace DMS\Services;

use DMS\Components\DocumentReportGeneratorComponent;
use DMS\Components\NotificationComponent;
use DMS\Constants\DocumentReportStatus;
use DMS\Constants\Metadata\DocumentReportMetadata;
use DMS\Constants\Notifications;
use DMS\Core\CacheManager;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Models\DocumentModel;
use DMS\Models\ServiceModel;

class DocumentReportGeneratorService extends AService {
    private DocumentModel $documentModel;
    private DocumentReportGeneratorComponent $drgc;
    private NotificationComponent $notificationComponent;

    public function __construct(Logger $logger, ServiceModel $serviceModel, CacheManager $cm, DocumentModel $documentModel, DocumentReportGeneratorComponent $drgc, NotificationComponent $notificationComponent) {
        parent::__construct('DocumentReportGeneratorService', $logger, $serviceModel, $cm);

        $this->documentModel = $documentModel;
        $this->drgc = $drgc;
        $this->notificationComponent = $notificationComponent;
    }

    public function run() {
        $this->startService();

        $queue = $this->documentModel->getDocumentReportQueueEntriesForStatus(DocumentReportStatus::NEW);

        $this->log('Found ' . $queue->num_rows . ' queue entries', __METHOD__);

        foreach($queue as $q) {
            if($q['status'] != DocumentReportStatus::NEW) {
                $this->log('Skipping entry #' . $q['id'] . ' because it is not new', __METHOD__);
                continue;
            }

            $id = $q['id'];

            $this->updateStatusToInProgress($id);

            $sqlResult = $this->documentModel->query($q['sql_string']);

            $result = $this->drgc->generateReport($q['id'], $sqlResult, $q['id_user'], $q['file_format']);

            if($result === FALSE) {
                $this->updateStatusToError($id);
                continue;
            }

            $this->log('Generated report for queue entry #' . $id, __METHOD__);
            $this->documentModel->updateDocumentReportQueueEntry($id, $result);
            $this->updateStatusToFinished($id);

            $this->notificationComponent->createNewNotification(Notifications::DOCUMENT_REPORT_GENERATED, ['id_user' => $q['id_user']]);
        }

        $this->stopService();
    }

    private function updateStatusToInProgress(int $id) {
        return $this->documentModel->updateDocumentReportQueueEntry($id, [DocumentReportMetadata::STATUS => DocumentReportStatus::IN_PROGRESS, DocumentReportMetadata::DATE_UPDATED => date(Database::DB_DATE_FORMAT)]);
    }

    private function updateStatusToFinished(int $id) {
        return $this->documentModel->updateDocumentReportQueueEntry($id, [DocumentReportMetadata::STATUS => DocumentReportStatus::FINISHED, DocumentReportMetadata::PERCENT_FINISHED => '100', DocumentReportMetadata::DATE_UPDATED => date(Database::DB_DATE_FORMAT)]);
    }

    private function updateStatusToError(int $id) {
        return $this->documentModel->updateDocumentReportQueueEntry($id, [DocumentReportMetadata::STATUS => DocumentReportStatus::ERROR, DocumentReportMetadata::PERCENT_FINISHED => '0', DocumentReportMetadata::DATE_UPDATED => date(Database::DB_DATE_FORMAT)]);
    }
}

?>