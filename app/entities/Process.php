<?php

namespace DMS\Entities;

/**
 * Process entity
 * 
 * @author Lukas Velek
 */
class Process extends AEntity {
    private array $workflow;
    private ?int $idDocument;
    private ?int $workflowStatus;
    private int $type;
    private int $status;
    private int $idAuthor;
    private bool $isArchive;

    /**
     * Class constructor
     * 
     * @param int $id Process ID
     * @param string $dateCreated Date created
     * @param null|int $idDocument Document ID or null
     * @param null|int $workflow1 Workflow1 user ID or null
     * @param null|int $workflow2 Workflow2 user ID or null
     * @param null|int $workflow3 Workflow3 user ID or null
     * @param null|int $workflow4 Workflow4 user ID or null
     * @param null|int $workflowStatus Workflow status (1-4)
     * @param int $type Process type
     * @param int $status Process status
     * @param int $idAuthor Author ID
     * @param bool $isArchive Is archive process
     */
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

    /**
     * Returns document ID
     * 
     * @return null|int Document ID or null
     */
    public function getIdDocument() {
        return $this->idDocument;
    }

    /**
     * Returns workflow as array
     * 
     * @return array Workflow array
     */
    public function getWorkflow() {
        return $this->workflow;
    }

    /**
     * Returns workflow step
     * 
     * @param int $step Workflow step
     * @return null|int Workflow user ID or null
     */
    public function getWorkflowStep(int $step) {
        if(!empty($this->workflow)) {
            return $this->workflow[$step];
        } else {
            return null;
        }
    }

    /**
     * Returns workflow status
     * 
     * @return null|int Workflow status or null
     */
    public function getWorkflowStatus() {
        return $this->workflowStatus;
    }

    /**
     * Returns process type
     * 
     * @return int Process type
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Returns process status
     * 
     * @return int Process status
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Returns position for ID user
     * 
     * @param int $idUser User ID
     * @return int ID user position in workflow or -1 if the user is not present in workflow
     */
    public function getWorkflowIdUserPosition(int $idUser) {
        $i = -1;
        foreach($this->workflow as $w) {
            if($w == $idUser) {
                break;
            }

            $i++;
        }

        return $i;
    }

    /**
     * Returns author ID
     * 
     * @return int Author ID
     */
    public function getIdAuthor() {
        return $this->idAuthor;
    }

    /**
     * Returns whether the process is archive or not
     * 
     * @return bool True if the process is archive or false if not
     */
    public function isArchive() {
        return $this->isArchive;
    }
}

?>