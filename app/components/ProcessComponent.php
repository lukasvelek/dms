<?php

namespace DMS\Components;

use DMS\Constants\Groups;
use DMS\Constants\Notifications;
use DMS\Constants\ProcessStatus;
use DMS\Constants\ProcessTypes;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\DocumentLockEntity;

/**
 * Component that contains methods or operations that are regarded to process part of the application
 * 
 * @author Lukas Velek
 */
class ProcessComponent extends AComponent {
    private NotificationComponent $notificationComponent;
    private DocumentLockComponent $documentLockComponent;

    private array $models;

    /**
     * Class constructor
     * 
     * @param Database $db Database instance
     * @param Logger $logger Logger instance
     * @param array $models Document models array
     * @param NotificationComponent $notificationComponent NotificationComponent instance
     */
    public function __construct(Database $db, Logger $logger, array $models, NotificationComponent $notificationComponent, DocumentLockComponent $documentLockComponent) {
        parent::__construct($db, $logger);

        $this->models = $models;
        $this->notificationComponent = $notificationComponent;
        $this->documentLockComponent = $documentLockComponent;
    }

    /**
     * Returns all process instances where the given ID user is the current officer
     * 
     * @param int $idUser User ID
     * @return array Process instances array
     */
    public function getProcessesWhereIdUserIsCurrentOfficer(int $idUser) {
        $userProcesses = [];

        $this->logger->logFunction(function() use ($idUser, &$userProcesses) {
            $userProcesses = $this->models['processModel']->getProcessesWithIdUser($idUser);
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

    /**
     * Starts a process
     * 
     * @param int $type Process type
     * @param int $idDocument Document ID
     * @param int $idAuthor Author ID
     * @param bool $isArchive True if the process regards to a archive document or false if not
     * @return bool True if the process has been started or false if not
     */
    public function startProcess(int $type, int $idDocument, int $idAuthor, bool $isArchive = false) {
        $start = true;

        $data = [];

        if($this->checkIfDocumentIsInProcess($idDocument)) {
            // is in process
            return false;
        }

        if($this->checkIfDocumentIsLocked($idDocument)) {
            // is locked
            return false;
        }

        switch($type) {
            case ProcessTypes::DELETE:
                $groupUsers = [];
                $document = null;

                $this->logger->logFunction(function() use (&$groupUsers) {
                    $archmanIdGroup = $this->models['groupModel']->getGroupByCode(Groups::ARCHIVE_MANAGER)->getId();
                    $groupUsers = $this->models['groupUserModel']->getGroupUsersByGroupId($archmanIdGroup);
                }, __METHOD__);

                $this->logger->logFunction(function() use (&$document, $idDocument) {
                    $document = $this->models['documentModel']->getDocumentById($idDocument);
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
                    $archmanIdGroup = $this->models['groupModel']->getGroupByCode(Groups::ARCHIVE_MANAGER)->getId();
                    $groupUsers = $this->models['groupUserModel']->getGroupUsersByGroupId($archmanIdGroup);
                }, __METHOD__);

                $this->logger->logFunction(function() use (&$document, $idDocument) {
                    $document = $this->models['documentModel']->getDocumentById($idDocument);
                }, __METHOD__);

                if($document == null) {
                    die();
                }

                $document = $this->models['documentModel']->getDocumentById($idDocument);
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
            $this->models['processModel']->insertNewProcess($data);
            $this->logger->info('Started new process for document #' . $idDocument . ' of type \'' . ProcessTypes::$texts[$type] . '\'', __METHOD__);
            
            $idProcess = null;

            $this->logger->logFunction(function() use (&$idProcess) {
                $idProcess = $this->models['processModel']->getLastInsertedIdProcess();
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

    /**
     * Moves process to the next user in the workflow
     * 
     * @param int $idProcess Process ID
     * @return true
     */
    public function moveProcessToNextWorkflowUser(int $idProcess) {
        $process = null;

        $this->logger->logFunction(function() use (&$process, $idProcess) {
            $process = $this->models['processModel']->getProcessById($idProcess);
        }, __METHOD__);

        if(is_null($process)) {
            return false;
        }

        $newWfStatus = $process->getWorkflowStatus() + 1;

        $this->models['processModel']->updateWorkflowStatus($idProcess, $newWfStatus);

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

    /**
     * Ends the given process
     * 
     * @param int $idProcess Process ID to be ended
     * @return true
     */
    public function endProcess(int $idProcess) {
        $this->models['processModel']->updateStatus($idProcess, ProcessStatus::FINISHED);

        $process = null;

        $this->logger->logFunction(function() use (&$process, $idProcess) {
            $process = $this->models['processModel']->getProcessById($idProcess);
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

    /**
     * Checks if a given document is contained in a process
     * 
     * @param int $idDocument Document ID to be checked
     * @return bool True if the given document is contained in a process or false if not
     */
    public function checkIfDocumentIsInProcess(int $idDocument) {
        $process = null;

        $this->logger->logFunction(function() use (&$process, $idDocument) {
            $process = $this->models['processModel']->getProcessForIdDocument($idDocument);
        }, __METHOD__);

        if(!is_null($process) && $process->getStatus() == ProcessStatus::IN_PROGRESS) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if a given document is locked
     * 
     * @param int $idDocument Document ID to be checked
     * @return bool True if the given document is locked or false if not
     */
    public function checkIfDocumentIsLocked(int $idDocument) {
        $lock = null;

        $this->logger->logFunction(function() use (&$lock, $idDocument) {
            $lock = $this->documentLockComponent->isDocumentLocked($idDocument);
        });

        if($lock instanceof DocumentLockEntity) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Deletes a process
     * 
     * @param int $idProcess Process ID to be deleted
     * @return true
     */
    public function deleteProcess(int $idProcess) {
        $this->models['processModel']->deleteProcess($idProcess);
        $this->models['processCommentModel']->removeProcessCommentsForIdProcess($idProcess);

        return true;
    }

    /**
     * Deletes all processes for a given ID document
     * 
     * @param int $idDocument Document ID
     * @return true
     */
    public function deleteProcessesForIdDocument(int $idDocument) {
        $processes = [];

        $this->logger->logFunction(function() use (&$processes, $idDocument) {
            $processes = $this->models['processModel']->getProcessesForIdDocument($idDocument);
        }, __METHOD__);
        
        $this->models['processModel']->removeProcessesForIdDocument($idDocument);

        foreach($processes as $process) {
            $this->models['processCommentModel']->removeProcessCommentsForIdProcess($process->getId());
        }

        return true;
    }
}

?>