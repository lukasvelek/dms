<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\DocumentStatus;
use DMS\Constants\Metadata\DocumentStatsMetadata;
use DMS\Constants\Metadata\ProcessStatsMetadata;
use DMS\Constants\ProcessStatus;
use DMS\Modules\APresenter;

class WidgetsPresenter extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('Widgets');

        $this->getActionNamesFromClass($this);
    }

    protected function updateDocumentStats() {
        global $app;

        $this->updateDocumentStats();

        $app->redirect('HomePage:showHomepage');
    }

    protected function updateProcessStats() {
        global $app;

        $this->_updateProcessStats();

        $app->redirect('HomePage:showHomepage');
    }

    protected function updateAllStats() {
        global $app;

        $this->_updateProcessStats();
        $this->_updateDocumentStats();

        $app->redirect('HomePage:showHomepage');
    }

    private function _updateProcessStats() {
        global $app;

        $data = array(
            ProcessStatsMetadata::TOTAL_COUNT => $app->processModel->getProcessCountByStatus(),
            ProcessStatsMetadata::FINISHED_COUNT => $app->processModel->getProcessCountByStatus(ProcessStatus::FINISHED),
            ProcessStatsMetadata::IN_PROGRESS_COUNT => $app->processModel->getProcessCountByStatus(ProcessStatus::IN_PROGRESS)
        );

        $app->processModel->beginTran();
        $app->processModel->insertProcessStatsEntry($data);
        $app->processModel->commitTran();
    }

    private function _updateDocumentStats() {
        global $app;

        $data = array(
            DocumentStatsMetadata::TOTAL_COUNT => $app->documentModel->getTotalDocumentCount(null),
            DocumentStatsMetadata::SHREDDED_COUNT => $app->documentModel->getDocumentCountByStatus(DocumentStatus::SHREDDED),
            DocumentStatsMetadata::ARCHIVED_COUNT => $app->documentModel->getDocumentCountByStatus(DocumentStatus::ARCHIVED),
            DocumentStatsMetadata::NEW_COUNT => $app->documentModel->getDocumentCountByStatus(DocumentStatus::NEW),
            DocumentStatsMetadata::WAITING_FOR_ARCHIVATION_COUNT => $app->documentModel->getDocumentCountByStatus(DocumentStatus::ARCHIVATION_APPROVED)
        );

        $app->documentModel->beginTran();
        $app->documentModel->insertDocumentStatsEntry($data);
        $app->documentModel->commitTran();
    }
}

?>