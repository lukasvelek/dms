<?php

namespace DMS\Services;

use DMS\Components\DocumentReportGeneratorComponent;
use DMS\Constants\DocumentReportStatus;
use DMS\Core\CacheManager;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Models\DocumentModel;
use DMS\Models\ServiceModel;

class DocumentReportGeneratorService extends AService {
    private DocumentModel $documentModel;
    private DocumentReportGeneratorComponent $drgc;

    public function __construct(Logger $logger, ServiceModel $serviceModel, CacheManager $cm, DocumentModel $documentModel, DocumentReportGeneratorComponent $drgc) {
        parent::__construct('DocumentReportGeneratorService', 'Generates document reports', $logger, $serviceModel, $cm);

        $this->documentModel = $documentModel;
        $this->drgc = $drgc;
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

            $result = $this->drgc->generateReport($sqlResult, $q['id_user']);

            if($result === FALSE) {
                $this->updateStatusToError($id);
                continue;
            }

            $this->log('Generated report for queue entry #' . $id, __METHOD__);
            $this->documentModel->updateDocumentReportQueueEntry($id, ['file_src' => $result]);
            $this->updateStatusToFinished($id);
        }

        $this->stopService();
    }

    private function updateStatusToInProgress(int $id) {
        return $this->documentModel->updateDocumentReportQueueEntry($id, ['status' => DocumentReportStatus::IN_PROGRESS, 'date_updated' => date(Database::DB_DATE_FORMAT)]);
    }

    private function updateStatusToFinished(int $id) {
        return $this->documentModel->updateDocumentReportQueueEntry($id, ['status' => DocumentReportStatus::FINISHED, 'date_updated' => date(Database::DB_DATE_FORMAT)]);
    }

    private function updateStatusToError(int $id) {
        return $this->documentModel->updateDocumentReportQueueEntry($id, ['status' => DocumentReportStatus::ERROR, 'date_updated' => date(Database::DB_DATE_FORMAT)]);
    }
}

?>