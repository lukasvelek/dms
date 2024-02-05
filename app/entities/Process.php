<?php

namespace DMS\Entities;

class Process extends AEntity {
    private array $workflow;
    private ?int $idDocument;
    private ?int $workflowStatus;
    private int $type;
    private int $status;
    private int $idAuthor;
    private bool $isArchive;

    public function __construct(int $id, string $dateCreated, ?int $idDocument, ?int $workflow1, ?int $workflow2, ?int $workflow3, ?int $workflow4, ?int $workflowStatus, int $type, int $status, int $idAuthor, string $dateUpdated, bool $isArchive) {
        parent::__construct($id, $dateCreated, $dateUpdated);
        
        $this->idDocument = $idDocument;
        $this->type = $type;
        $this->status = $status;
        $this->workflow = [];
        $this->workflow[] = $workflow1;
        $this->workflow[] = $workflow2;
        $this->workflow[] = $workflow3;
        $this->workflow[] = $workflow4;
        $this->workflowStatus = $workflowStatus;
        $this->idAuthor = $idAuthor;
        $this->isArchive = $isArchive;
    }

    public function getIdDocument() {
        return $this->idDocument;
    }

    public function getWorkflow() {
        return $this->workflow;
    }

    public function getWorkflowStep(int $step) {
        return $this->workflow[$step];
    }

    public function getWorkflowStatus() {
        return $this->workflowStatus;
    }

    public function getType() {
        return $this->type;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getWorkflowIdUserPosition(int $idUser) {
        $i = 0;
        foreach($this->workflow as $w) {
            if($w == $idUser) {
                break;
            }

            $i++;
        }

        return $i;
    }

    public function getIdAuthor() {
        return $this->idAuthor;
    }

    public function isArchive() {
        return $this->isArchive;
    }
}

?>