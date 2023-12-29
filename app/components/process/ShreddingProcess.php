<?php

namespace DMS\Components\Process;

use DMS\Components\ProcessComponent;
use DMS\Constants\DocumentAfterShredActions;
use DMS\Constants\DocumentShreddingStatus;
use DMS\Constants\DocumentStatus;
use DMS\Entities\Document;
use DMS\Entities\Process;
use DMS\Models\DocumentCommentModel;
use DMS\Models\DocumentModel;
use DMS\Models\ProcessCommentModel;
use DMS\Models\ProcessModel;

class ShreddingProcess implements IProcessComponent {
    private Process $process;
    private Document $document;
    private int $idAuthor;
    private ProcessModel $processModel;
    private DocumentModel $documentModel;
    private ProcessComponent $processComponent;
    private DocumentCommentModel $documentCommentModel;
    private ProcessCommentModel $processCommentModel;

    public function __construct(int $idProcess, ProcessModel $processModel, DocumentModel $documentModel, ProcessComponent $processComponent, DocumentCommentModel $documentCommentModel, ProcessCommentModel $processCommentModel) {
        $this->processModel = $processModel;
        $this->documentModel = $documentModel;
        $this->processComponent = $processComponent;
        $this->documentCommentModel = $documentCommentModel;
        $this->processCommentModel = $processCommentModel;
        
        $this->process = $this->processModel->getProcessById($idProcess);
        $this->document = $this->documentModel->getDocumentById($this->process->getIdDocument());

        $this->idAuthor = $this->process->getIdAuthor();
    }

    public function work() {
        $this->processComponent->endProcess($this->process->getId());
        $this->documentModel->updateDocument($this->document->getId(), array(
            'shredding_status' => DocumentShreddingStatus::SHREDDED,
            'status' => DocumentStatus::SHREDDED
        ));
        $this->documentModel->nullIdOfficer($this->document->getId());

        switch($this->document->getAfterShredAction()) {
            case DocumentAfterShredActions::DELETE:
                $this->documentModel->deleteDocument($this->document->getId());
                break;

            case DocumentAfterShredActions::TOTAL_DELETE:
                $this->documentModel->deleteDocument($this->document->getId(), false);
                $this->documentCommentModel->removeCommentsForIdDocument($this->document->getId());
                $this->processComponent->deleteProcessesForIdDocument($this->document->getId());
                break;

            default:
            case DocumentAfterShredActions::SHOW_AS_SHREDDED:
                break;
        }

        $this->documentModel->removeDocumentSharingForIdDocument($this->document->getId());

        return true;
    }

    public function getWorkflow() {
        return null;
    }

    public function getIdAuthor() {
        return $this->idAuthor;
    }
}

?>