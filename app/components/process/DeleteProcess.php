<?php

namespace DMS\Components\Process;

use DMS\Constants\Groups;
use DMS\Entities\Document;
use DMS\Entities\Process;

class DeleteProcess implements IProcessComponent {
    private Process $process;
    private Document $document;

    public function __construct(int $idProcess) {
        global $app;

        $this->process = $app->processModel->getProcessById($idProcess);
        $this->document = $app->documentModel->getDocumentById($this->process->getIdDocument());
    }

    public function work() {
        global $app;

        $app->processComponent->endProcess($this->process->getId());
        $app->documentModel->updateDocument($this->document->getId(), array(
            'is_deleted' => '1',
            'status' => '2'
        ));
        $app->documentModel->nullIdOfficer($this->document->getId());

        return true;
    }

    public function getWorkflow() {
        return $this->createWorkflow();
    }

    public function getProcess() {
        return $this->process;
    }

    public function getDocument() {
        return $this->document;
    }

    private function createWorkflow() {
        global $app;

        $workflow = [];

        $workflow[] = $this->document->getIdManager();

        $archiveManagerIdGroup = $app->groupModel->getGroupByCode(Groups::ARCHIVE_MANAGER)->getId();
        $workflow[] = $app->groupUserModel->getGroupUserByIdGroup($archiveManagerIdGroup)->getIdUser();

        return $workflow;
    }
}

?>