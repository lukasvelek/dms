<?php

namespace DMS\Components\Process;

use DMS\Components\ProcessComponent;
use DMS\Constants\Groups;
use DMS\Entities\Document;
use DMS\Entities\Process;
use DMS\Models\DocumentMetadataHistoryModel;
use DMS\Models\DocumentModel;
use DMS\Models\GroupModel;
use DMS\Models\GroupUserModel;
use DMS\Models\ProcessModel;

/**
 * Document delete process component
 * 
 * @author Lukas Velek
 */
class DeleteProcess implements IProcessComponent {
    private Process $process;
    private Document $document;
    private int $idAuthor;
    private ProcessComponent $processComponent;
    private DocumentModel $documentModel;
    private ProcessModel $processModel;
    private GroupModel $groupModel;
    private GroupUserModel $groupUserModel;
    private DocumentMetadataHistoryModel $dmhm;

    /**
     * Class constructor
     * 
     * @param int $idProcess Process ID
     * @param ProcessComponent $processComponent ProcessComponent instance
     * @param DocumentModel $documentModel DocumentModel instance
     * @param ProcessModel $processModel ProcessModel instance
     * @param GroupModel $groupModel GroupModel instance
     * @param GroupUserModel $groupUserModel GroupUserMOdel instance
     */
    public function __construct(int $idProcess,
                                ProcessComponent $processComponent,
                                DocumentModel $documentModel,
                                ProcessModel $processModel,
                                GroupModel $groupModel,
                                GroupUserModel $groupUserModel,
                                DocumentMetadataHistoryModel $dmhm) {
        $this->processComponent = $processComponent;
        $this->documentModel = $documentModel;
        $this->processModel = $processModel;
        $this->groupModel = $groupModel;
        $this->groupUserModel = $groupUserModel;
        $this->dmhm = $dmhm;

        $this->process = $this->processModel->getProcessById($idProcess);
        $this->document = $this->documentModel->getDocumentById($this->process->getIdDocument());

        $this->idAuthor = $this->process->getIdAuthor();
    }

    /**
     * This method performs the process actions.
     * It ends the process, deletes the document, nulls ID officer of the document and removes document sharings for the document.
     * 
     * @return true Returns true
     */
    public function work() {
        $this->processComponent->endProcess($this->process->getId());
        $this->documentModel->deleteDocument($this->document->getId());
        $this->dmhm->deleteEntriesForIdDocument($this->document->getId());
        $this->documentModel->nullIdOfficer($this->document->getId());
        $this->documentModel->removeDocumentSharingForIdDocument($this->document->getId());

        return true;
    }

    /**
     * Returns the process workflow
     * 
     * @return array Array of users (their IDs respectively) that make up the process workflow
     */
    public function getWorkflow() {
        return $this->createWorkflow();
    }

    /**
     * Returns the process instance
     * 
     * @return Process Process instance
     */
    public function getProcess() {
        return $this->process;
    }

    /**
     * Returns the document instance
     * 
     * @return Document Document instance
     */
    public function getDocument() {
        return $this->document;
    }

    /**
     * Returns the process' author ID
     * 
     * @return int Author ID
     */
    public function getIdAuthor() {
        return $this->idAuthor;
    }

    /**
     * Creates a workflow for the process
     * 
     * @return array Array of users (their IDs respectively) that make up the process workflow
     */
    private function createWorkflow() {
        $workflow = [];

        $workflow[] = $this->document->getIdManager();

        $archiveManagerIdGroup = $this->groupModel->getGroupByCode(Groups::ARCHIVE_MANAGER)->getId();
        $workflow[] = $this->groupUserModel->getGroupUserByIdGroup($archiveManagerIdGroup)->getIdUser();

        return $workflow;
    }
}

?>