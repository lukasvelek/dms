<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\DocumentStatus;
use DMS\Constants\ProcessStatus;
use DMS\Modules\APresenter;

class Widgets extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('Widgets');

        $this->getActionNamesFromClass($this);
    }

    protected function updateDocumentStats() {
        global $app;

        $data = array(
            'total_count' => $app->documentModel->getTotalDocumentCount(null),
            'shredded_count' => $app->documentModel->getDocumentCountByStatus(DocumentStatus::SHREDDED),
            'archived_count' => $app->documentModel->getDocumentCountByStatus(DocumentStatus::ARCHIVED),
            'new_count' => $app->documentModel->getDocumentCountByStatus(DocumentStatus::NEW),
            'waiting_for_archivation_count' => $app->documentModel->getDocumentCountByStatus(DocumentStatus::ARCHIVATION_APPROVED)
        );

        $app->documentModel->beginTran();

        $app->documentModel->insertDocumentStatsEntry($data);

        $app->documentModel->commitTran();

        $app->redirect('UserModule:HomePage:showHomepage');
    }

    protected function updateProcessStats() {
        global $app;

        $data = array(
            'total_count' => $app->processModel->getProcessCountByStatus(),
            'finished_count' => $app->processModel->getProcessCountByStatus(ProcessStatus::FINISHED),
            'in_progress_count' => $app->processModel->getProcessCountByStatus(ProcessStatus::IN_PROGRESS)
        );

        $app->processModel->beginTran();

        $app->processModel->insertProcessStatsEntry($data);

        $app->processModel->commitTran();

        $app->redirect('UserModule:HomePage:showHomepage');
    }

    protected function updateAllStats() {
        global $app;

        $processData = array(
            'total_count' => $app->processModel->getProcessCountByStatus(),
            'finished_count' => $app->processModel->getProcessCountByStatus(ProcessStatus::FINISHED),
            'in_progress_count' => $app->processModel->getProcessCountByStatus(ProcessStatus::IN_PROGRESS)
        );

        $documentData = array(
            'total_count' => $app->documentModel->getTotalDocumentCount(null),
            'shredded_count' => $app->documentModel->getDocumentCountByStatus(DocumentStatus::SHREDDED),
            'archived_count' => $app->documentModel->getDocumentCountByStatus(DocumentStatus::ARCHIVED),
            'new_count' => $app->documentModel->getDocumentCountByStatus(DocumentStatus::NEW),
            'waiting_for_archivation_count' => $app->documentModel->getDocumentCountByStatus(DocumentStatus::ARCHIVATION_APPROVED)
        );

        $app->documentModel->beginTran();
        
        $app->processModel->insertProcessStatsEntry($processData);
        $app->documentModel->insertDocumentStatsEntry($documentData);

        $app->documentModel->commitTran();

        $app->redirect('UserModule:HomePage:showHomepage');
    }
}

?>