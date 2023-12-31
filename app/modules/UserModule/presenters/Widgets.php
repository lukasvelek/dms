<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\DocumentStatus;
use DMS\Constants\ProcessStatus;
use DMS\Core\TemplateManager;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;

class Widgets extends APresenter {
    private string $name;
    private TemplateManager $templateManager;
    private IModule $module;

    public const DRAW_TOPPANEL = true;

    public function __construct() {
        $this->name = 'Widgets';

        $this->templateManager = TemplateManager::getTemporaryObject();
    }

    public function setModule(IModule $module) {
        $this->module = $module;
    }

    public function getModule() {
        return $this->module;
    }

    public function getName() {
        return $this->name;
    }

    protected function updateDocumentStats() {
        global $app;

        $data = array(
            'total_count' => $app->documentModel->getTotalDocumentCount(),
            'shredded_count' => $app->documentModel->getDocumentCountByStatus(DocumentStatus::SHREDDED),
            'archived_count' => $app->documentModel->getDocumentCountByStatus(DocumentStatus::ARCHIVED),
            'new_count' => $app->documentModel->getDocumentCountByStatus(DocumentStatus::NEW),
            'waiting_for_archivation_count' => $app->documentModel->getDocumentCountByStatus(DocumentStatus::ARCHIVATION_APPROVED)
        );

        $app->documentModel->insertDocumentStatsEntry($data);

        $app->redirect('UserModule:HomePage:showHomepage');
    }

    protected function updateProcessStats() {
        global $app;

        $data = array(
            'total_count' => $app->processModel->getProcessCountByStatus(),
            'finished_count' => $app->processModel->getProcessCountByStatus(ProcessStatus::FINISHED),
            'in_progress_count' => $app->processModel->getProcessCountByStatus(ProcessStatus::IN_PROGRESS)
        );

        $app->processModel->insertProcessStatsEntry($data);

        $app->redirect('UserModule:HomePage:showHomepage');
    }
}

?>