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
        $userProcesses = [];

        $this->logger->logFunction(function() use ($idUser, &$userProcesses) {
            $userProcesses = $this->processModel->getProcessesWithIdUser($idUser);
        }, __METHOD__);

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

    public function startProcess(int $type, int $idDocument, int $idAuthor, bool $isArchive = false) {
        $start = true;

        $data = [];

        if($this->checkIfDocumentIsInProcess($idDocument)) {
            // is in process
            return false;
        }

        switch($type) {
            case ProcessTypes::DELETE:
                $groupUsers = [];
                $document = null;

                $this->logger->logFunction(function() use (&$groupUsers) {
                    $archmanIdGroup = $this->groupModel->getGroupByCode(Groups::ARCHIVE_MANAGER)->getId();
                    $groupUsers = $this->groupUserModel->getGroupUsersByGroupId($archmanIdGroup);
                }, __METHOD__);

                $this->logger->logFunction(function() use (&$document, $idDocument) {
                    $document = $this->documentModel->getDocumentById($idDocument);
                }, __METHOD__);

                if($document == null) {
                    die();
                }

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
                $groupUsers = [];
                $document = null;

                $this->logger->logFunction(function() use (&$groupUsers) {
                    $archmanIdGroup = $this->groupModel->getGroupByCode(Groups::ARCHIVE_MANAGER)->getId();
                    $groupUsers = $this->groupUserModel->getGroupUsersByGroupId($archmanIdGroup);
                }, __METHOD__);

                $this->logger->logFunction(function() use (&$document, $idDocument) {
                    $document = $this->documentModel->getDocumentById($idDocument);
                }, __METHOD__);

                if($document == null) {
                    die();
                }

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

        if($isArchive) {
            $data['is_archive'] = $isArchive ? '1' : '0';
        }

        if($start) {
            $this->processModel->insertNewProcess($data);
            $this->logger->info('Started new process for document #' . $idDocument . ' of type \'' . ProcessTypes::$texts[$type] . '\'', __METHOD__);
            
            $idProcess = null;

            $this->logger->logFunction(function() use (&$idProcess) {
                $idProcess = $this->processModel->getLastInsertedIdProcess();
            }, __METHOD__);

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
        $process = null;

        $this->logger->logFunction(function() use (&$process, $idProcess) {
            $process = $this->processModel->getProcessById($idProcess);
        }, __METHOD__);

        if(is_null($process)) {
            return false;
        }

        $newWfStatus = $process->getWorkflowStatus() + 1;

        $this->processModel->updateWorkflowStatus($idProcess, $newWfStatus);

        $notify = false;
        if(isset($_SESSION['id_current_user']) && $process !== NULL) {
            if($process->getWorkflowStep($newWfStatus - 1) != $_SESSION['id_current_user']) {
                $notify = true;
            }
        } else {
            $notify = true;
        }

        if($notify === TRUE) {
            $this->notificationComponent->createNewNotification(Notifications::PROCESS_ASSIGNED_TO_USER, array(
                'id_user' => $process->getWorkflow()[$newWfStatus - 1],
                'id_process' => $idProcess
            ));
        }

        $this->logger->info('Updated workflow status of process #' . $idProcess, __METHOD__);

        return true;
    }

    public function endProcess(int $idProcess) {
        $this->processModel->updateStatus($idProcess, ProcessStatus::FINISHED);

        $process = null;

        $this->logger->logFunction(function() use (&$process, $idProcess) {
            $process = $this->processModel->getProcessById($idProcess);
        }, __METHOD__);

        if(is_null($process)) {
            return false;
        }

        $this->notificationComponent->createNewNotification(Notifications::PROCESS_FINISHED, array(
            'id_user' => $process->getIdAuthor(),
            'id_process' => $idProcess
        ));

        $this->logger->info('Ended process #' . $idProcess, __METHOD__);

        return true;
    }

    public function checkIfDocumentIsInProcess(int $idDocument) {
        $process = null;

        $this->logger->logFunction(function() use (&$process, $idDocument) {
            $process = $this->processModel->getProcessForIdDocument($idDocument);
        }, __METHOD__);

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
        $processes = [];

        $this->logger->logFunction(function() use (&$processes, $idDocument) {
            $processes = $this->processModel->getProcessesForIdDocument($idDocument);
        }, __METHOD__);
        
        $this->processModel->removeProcessesForIdDocument($idDocument);

        foreach($processes as $process) {
            $this->processCommentModel->removeProcessCommentsForIdProcess($process->getId());
        }

        return true;
    }

    /*public function checkIfArchiveArchiveIsInProcess(int $idArchive) {
        $process = null;

        $this->logger->logFunction(function() use (&$process, $idArchive) {
            $process = $this->processModel->getProcessForIdDocument($idArchive, true);
        });

        if(!is_null($process) && ($process->getStatus() == ProcessStatus::IN_PROGRESS)) {
            return true;
        }

        return false;
    }*/
}

?>