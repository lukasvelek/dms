<?php

namespace DMS\Components;

use DMS\Components\Process\DeleteProcess;
use DMS\Constants\Groups;
use DMS\Constants\Notifications;
use DMS\Constants\ProcessStatus;
use DMS\Constants\ProcessTypes;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Models\DocumentModel;
use DMS\Models\GroupModel;
use DMS\Models\GroupUserModel;
use DMS\Models\ProcessCommentModel;
use DMS\Models\ProcessModel;

class ProcessComponent extends AComponent {
    private ProcessModel $processModel;
    private GroupModel $groupModel;
    private GroupUserModel $groupUserModel;
    private DocumentModel $documentModel;
    private NotificationComponent $notificationComponent;
    private ProcessCommentModel $processCommentModel;

    public function __construct(Database $db, Logger $logger, ProcessModel $processModel, GroupModel $groupModel, GroupUserModel $groupUserModel, DocumentModel $documentModel, NotificationComponent $notificationComponent, ProcessCommentModel $processCommentModel) {
        parent::__construct($db, $logger);

        $this->processModel = $processModel;
        $this->groupModel = $groupModel;
        $this->groupUserModel = $groupUserModel;
        $this->documentModel = $documentModel;
        $this->notificationComponent = $notificationComponent;
        $this->processCommentModel = $processCommentModel;
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
            
            $idProcess = $this->processModel->getLastInsertedIdProcess();

            $this->notificationComponent->createNewNotification(Notifications::PROCESS_ASSIGNED_TO_USER, array(
                'id_process' => $idProcess,
                'id_user' => $_SESSION['id_current_user']
            ));

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

        $this->notificationComponent->createNewNotification(Notifications::PROCESS_ASSIGNED_TO_USER, array(
            'id_user' => $process->getWorkflow()[$newWfStatus - 1],
            'id_process' => $idProcess
        ));

        $this->logger->info('Updated workflow status of process #' . $idProcess, __METHOD__);

        return true;
    }

    public function endProcess(int $idProcess) {
        $this->processModel->updateStatus($idProcess, ProcessStatus::FINISHED);

        $process = $this->processModel->getProcessById($idProcess);

        $this->notificationComponent->createNewNotification(Notifications::PROCESS_FINISHED, array(
            'id_user' => $process->getIdAuthor(),
            'id_process' => $idProcess
        ));

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

    public function deleteProcess(int $idProcess) {
        $this->processModel->deleteProcess($idProcess);
        $this->processCommentModel->removeProcessCommentsForIdProcess($idProcess);

        return true;
    }

    public function deleteProcessesForIdDocument(int $idDocument) {
        $processes = $this->processModel->getProcessesForIdDocument($idDocument);
        $this->processModel->removeProcessesForIdDocument($idDocument);

        foreach($processes as $process) {
            $this->processCommentModel->removeProcessCommentsForIdProcess($process->getId());
        }

        return true;
    }
}

?>