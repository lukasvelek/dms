<?php

namespace DMS\Components\Process;

use DMS\Components\ProcessComponent;
use DMS\Constants\Groups;
use DMS\Entities\Document;
use DMS\Entities\Process;
use DMS\Models\DocumentModel;
use DMS\Models\GroupModel;
use DMS\Models\GroupUserModel;
use DMS\Models\ProcessModel;

class DeleteProcess implements IProcessComponent {
    private Process $process;
    private Document $document;
    private int $idAuthor;
    private ProcessComponent $processComponent;
    private DocumentModel $documentModel;
    private ProcessModel $processModel;
    private GroupModel $groupModel;
    private GroupUserModel $groupUserModel;

    public function __construct(int $idProcess, ProcessComponent $processComponent, DocumentModel $documentModel, ProcessModel $processModel, GroupModel $groupModel, GroupUserModel $groupUserModel) {
        $this->processComponent = $processComponent;
        $this->documentModel = $documentModel;
        $this->processModel = $processModel;
        $this->groupModel = $groupModel;
        $this->groupUserModel = $groupUserModel;

        $this->process = $this->processModel->getProcessById($idProcess);
        $this->document = $this->documentModel->getDocumentById($this->process->getIdDocument());

        $this->idAuthor = $this->process->getIdAuthor();
    }

    public function work() {
        $this->processComponent->endProcess($this->process->getId());
        $this->documentModel->updateDocument($this->document->getId(), array(
            'is_deleted' => '1',
            'status' => '2'
        ));
        $this->documentModel->nullIdOfficer($this->document->getId());

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

    public function getIdAuthor() {
        return $this->idAuthor;
    }

    private function createWorkflow() {
        $workflow = [];

        $workflow[] = $this->document->getIdManager();

        $archiveManagerIdGroup = $this->groupModel->getGroupByCode(Groups::ARCHIVE_MANAGER)->getId();
        $workflow[] = $this->groupUserModel->getGroupUserByIdGroup($archiveManagerIdGroup)->getIdUser();

        return $workflow;
    }
}

?>