<?php

namespace DMS\Components;

use DMS\Components\Process\DeleteProcess;
use DMS\Constants\Groups;
use DMS\Constants\ProcessStatus;
use DMS\Constants\ProcessTypes;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Models\DocumentModel;
use DMS\Models\GroupModel;
use DMS\Models\GroupUserModel;
use DMS\Models\ProcessModel;

class ProcessComponent extends AComponent {
    private ProcessModel $processModel;
    private GroupModel $groupModel;
    private GroupUserModel $groupUserModel;
    private DocumentModel $documentModel;

    public function __construct(Database $db, Logger $logger, ProcessModel $processModel, GroupModel $groupModel, GroupUserModel $groupUserModel, DocumentModel $documentModel) {
        parent::__construct($db, $logger);

        $this->processModel = $processModel;
        $this->groupModel = $groupModel;
        $this->groupUserModel = $groupUserModel;
        $this->documentModel = $documentModel;
    }

    public function getProcessesWhereIdUserIsCurrentOfficer(int $idUser) {
        $userProcesses = $this->processModel->getProcessesWithIdUser($idUser);

        $processes = [];

        if(count($userProcesses) > 0) {
            foreach($userProcesses as $up) {
                $userPos = -1;
    
                $i = 0;
                foreach($up->getWorkflow() as $wf) {
                    if($wf == $idUser) {
                        $userPos = $i;
    
                        break;
                    }
    
                    $i++;
                }
    
                if($userPos == -1) {
                    break;
                }
    
                if($up->getWorkflowStatus() == $userPos) {
                    $processes[] = $up;
                }
            }
        }

        return $processes;
    }

    public function startProcess(int $type, int $idDocument, int $idAuthor) {
        $start = true;

        $data = [];

        if($this->checkIfDocumentIsInProcess($idDocument)) {
            // is in process
            return false;
        }

        switch($type) {
            case ProcessTypes::DELETE:
                $archmanIdGroup = $this->groupModel->getGroupByCode(Groups::ARCHIVE_MANAGER)->getId();
                $groupUsers = $this->groupUserModel->getGroupUsersByGroupId($archmanIdGroup);

                $document = $this->documentModel->getDocumentById($idDocument);
                $data['workflow1'] = $document->getIdManager();

                if(count($groupUsers) > 0) {
                    foreach($groupUsers as $gu) {
                        if($gu->getIsManager()) {
                            $data['workflow2'] = $gu->getIdUser();
                            
                            break;
                        }
                    }
                } else {
                    $start = false;
                }

                break;

            case ProcessTypes::SHREDDING:
                $archmanIdGroup = $this->groupModel->getGroupByCode(Groups::ARCHIVE_MANAGER)->getId();
                $groupUsers = $this->groupUserModel->getGroupUsersByGroupId($archmanIdGroup);

                $document = $this->documentModel->getDocumentById($idDocument);
                $data['workflow1'] = $document->getIdAuthor();
                $data['workflow2'] = $document->getIdManager();

                if(count($groupUsers) > 0) {
                    foreach($groupUsers as $gu) {
                        if($gu->getIsManager()) {
                            $data['workflow3'] = $gu->getIdUser();
                            
                            break;
                        }
                    }
                } else {
                    $start = false;
                }

                break;
        }

        $data['id_document'] = $idDocument;
        $data['type'] = $type;
        $data['workflow_status'] = '1';
        $data['id_author'] = $idAuthor;

        if($start) {
            $this->processModel->insertNewProcess($data);
            $this->logger->info('Started new process for document #' . $idDocument . ' of type \'' . ProcessTypes::$texts[$type] . '\'', __METHOD__);

            return true;
        } else {
            return false;
        }
    }

    public function moveProcessToNextWorkflowUser(int $idProcess) {
        $process = $this->processModel->getProcessById($idProcess);

        if(is_null($process)) {
            return false;
        }

        $newWfStatus = $process->getWorkflowStatus() + 1;

        $this->processModel->updateWorkflowStatus($idProcess, $newWfStatus);

        $this->logger->info('Updated workflow status of process #' . $idProcess, __METHOD__);

        return true;
    }

    public function endProcess(int $idProcess) {
        $this->processModel->updateStatus($idProcess, ProcessStatus::FINISHED);

        $this->logger->info('Ended process #' . $idProcess, __METHOD__);

        return true;
    }

    public function checkIfDocumentIsInProcess(int $idDocument) {
        $process = $this->processModel->getProcessForIdDocument($idDocument);

        if(!is_null($process) && $process->getStatus() == ProcessStatus::IN_PROGRESS) {
            return true;
        } else {
            return false;
        }
    }
}

?>