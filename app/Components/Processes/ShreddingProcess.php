<?php

namespace DMS\Components\Process;

use DMS\Components\ProcessComponent;
use DMS\Constants\DocumentAfterShredActions;
use DMS\Constants\DocumentShreddingStatus;
use DMS\Constants\DocumentStatus;
use DMS\Entities\Document;
use DMS\Entities\Process;
use DMS\Models\DocumentCommentModel;
use DMS\Models\DocumentMetadataHistoryModel;
use DMS\Models\DocumentModel;
use DMS\Models\ProcessCommentModel;
use DMS\Models\ProcessModel;

/**
 * Shredding process component
 * 
 * @author Lukas Velek
 */
class ShreddingProcess implements IProcessComponent {
    private Process $process;
    private Document $document;
    private int $idAuthor;
    private ProcessModel $processModel;
    private DocumentModel $documentModel;
    private ProcessComponent $processComponent;
    private DocumentCommentModel $documentCommentModel;
    private ProcessCommentModel $processCommentModel;
    private DocumentMetadataHistoryModel $dmhm;

    /**
     * Class constructor
     * 
     * @param int $idProcess Process ID
     * @param ProcessModel $processModel ProcessModel instance
     * @param DocumentModel $documentModel DocumentModel instance
     * @param ProcessComponent $processComponent ProcessComponent instance
     * @param DocumentCommentModel $documentCommentModel DocumentCommentModel instance
     * @param ProcessCommentModel $processCommentModel ProcessCommentModel instance
     */
    public function __construct(int $idProcess, 
                                ProcessModel $processModel,
                                DocumentModel $documentModel,
                                ProcessComponent $processComponent,
                                DocumentCommentModel $documentCommentModel,
                                ProcessCommentModel $processCommentModel,
                                DocumentMetadataHistoryModel $dmhm) {
        $this->processModel = $processModel;
        $this->documentModel = $documentModel;
        $this->processComponent = $processComponent;
        $this->documentCommentModel = $documentCommentModel;
        $this->processCommentModel = $processCommentModel;
        $this->dmhm = $dmhm;
        
        $this->process = $this->processModel->getProcessById($idProcess);
        $this->document = $this->documentModel->getDocumentById($this->process->getIdDocument());

        $this->idAuthor = $this->process->getIdAuthor();
    }

    /**
     * This method performs the process actions.
     * It ends the process, updates the document, performs the after shredding action and removes document sharings
     * 
     * @return true Returns true
     */
    public function work() {
        $this->processComponent->endProcess($this->process->getId());

        if($this->document->getAfterShredAction() !== DocumentAfterShredActions::TOTAL_DELETE) {
            $data = array(
                'shredding_status' => DocumentShreddingStatus::SHREDDED,
                'status' => DocumentStatus::SHREDDED
            );
            $this->documentModel->updateDocument($this->document->getId(), $data);
            $this->documentModel->nullIdOfficer($this->document->getId());
            $data['id_current_officer'] = 'NULL';
            $this->dmhm->insertNewMetadataHistoryEntriesBasedOnDocumentMetadataArray($data, $this->document->getId(), $_SESSION['id_current_user']);
        }

        switch($this->document->getAfterShredAction()) {
            case DocumentAfterShredActions::DELETE:
                $this->dmhm->deleteEntriesForIdDocument($this->document->getId());
                $this->documentModel->deleteDocument($this->document->getId());
                break;

            case DocumentAfterShredActions::TOTAL_DELETE:
                $this->documentModel->deleteDocument($this->document->getId(), false);
                $this->documentCommentModel->removeCommentsForIdDocument($this->document->getId());
                $this->dmhm->deleteEntriesForIdDocument($this->document->getId());
                $this->processComponent->deleteProcessesForIdDocument($this->document->getId());
                break;

            default:
            case DocumentAfterShredActions::SHOW_AS_SHREDDED:
                break;
        }

        $this->documentModel->removeDocumentSharingForIdDocument($this->document->getId());

        return true;
    }

    /**
     * Returns the process workflow.
     * This process has no workflow.
     * 
     * @return null
     */
    public function getWorkflow() {
        return null;
    }

    /**
     * Returns the process' author ID
     * 
     * @return int Author ID
     */
    public function getIdAuthor() {
        return $this->idAuthor;
    }
}

?>