<?php

namespace DMS\Components;

use DMS\Constants\Groups;
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

    public function startProcess(int $type, int $idDocument) {
        global $app;

        if($this->checkIfDocumentIsInProcess($idDocument)) {
            // is in process
            return false;
        }

        $document = $app->documentModel->getDocumentById($idDocument);

        $workflow = [];

        switch($type) {
            case ProcessTypes::DELETE:
                $workflow[] = $document->getIdManager();

                $archmanIdGroup = $app->groupModel->getGroupByCode(Groups::ARCHIVE_MANAGER)->getId();
                $workflow[] = $app->groupUserModel->getGroupUserByIdGroup($archmanIdGroup)->getIdUser();
                //$workflow[] = 
                break;
        }

        $app->processModel->insertNewProcess($idDocument, $type, $workflow);
        
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