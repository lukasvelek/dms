<?php

namespace DMS\Components;

use DMS\Components\Process\DeleteProcess;
use DMS\Constants\Groups;
use DMS\Constants\ProcessStatus;
use DMS\Constants\ProcessTypes;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class ProcessComponent extends AComponent {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getProcessesWhereIdUserIsCurrentOfficer(int $idUser) {
        global $app;

        $userProcesses = $app->processModel->getProcessesWithIdUser($idUser);

        $processes = [];

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

        return $processes;
    }

    public function startProcess(int $type, ?int $idDocument = null) {
        global $app;

        if($this->checkIfDocumentIsInProcess($idDocument)) {
            // is in process
            return false;
        }

        $app->processModel->insertEmptyProcess($type);

        $idProcess = $app->processModel->getLastInsertedIdProcess();

        $data = [];

        switch($type) {
            case ProcessTypes::DELETE:
                $deleteProcess = new DeleteProcess($idProcess);
                $workflow = $deleteProcess->getWorkflow();

                $i = 1;
                foreach($workflow as $w) {
                    $data['workflow' . $i] = $w;

                    $i++;
                }

                $data['id_document'] = $deleteProcess->getDocument()->getId();
                $data['workflow_status'] = '1';

                break;
        }

        $app->processModel->updateProcess($idProcess, $data);

        //$app->processModel->insertNewProcess($idDocument, $type, $workflow);
        
        return true;
    }

    public function moveProcessToNextWorkflowUser(int $idProcess) {
        global $app;

        $process = $app->processModel->getProcessById($idProcess);

        $newWfStatus = $process->getStatus() + 1;

        $app->processModel->updateWorkflowStatus($idProcess, $newWfStatus);

        return true;
    }

    public function endProcess(int $idProcess) {
        global $app;

        $app->processModel->updateStatus($idProcess, ProcessStatus::FINISHED);

        return true;
    }

    private function checkIfDocumentIsInProcess(int $idDocument) {
        global $app;

        $process = $app->processModel->getProcessForIdDocument($idDocument);

        if($process === NULL) {
            return false;
        } else {
            return true;
        }
    }
}

?>