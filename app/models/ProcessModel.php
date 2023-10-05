<?php

namespace DMS\Models;

use DMS\Constants\ProcessStatus;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Process;

class ProcessModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getProcessesWithIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('processes')
                   ->where('workflow1=:id_user')
                   ->orWhere('workflow2=:id_user')
                   ->orWhere('workflow3=:id_user')
                   ->orWhere('workflow4=:id_user')
                   ->setParam(':id_user', $idUser)
                   ->execute()
                   ->fetch();

        $processes = [];
        foreach($rows as $row) {
            $processes[] = $this->createProcessObjectFromDbRow($row);
        }

        return $processes;
    }

    public function insertNewProcess(int $idDocument, int $type, ?array $workflow) {
        $qb = $this->qb(__METHOD__);

        $keys = array(
            'id_document',
            'type',
            'status',
            'workflow_status'
        );

        $values = array(
            ':id_document',
            ':type',
            ':status',
            ':workflow_status'
        );

        $params = array(
            ':id_document' => $idDocument,
            ':type' => $type,
            ':status' => ProcessStatus::IN_PROGRESS,
            ':workflow_status' => '1'
        );

        for($i = 0; $i < count($workflow); $i++) {
            $keys[] = 'workflow' . ($i + 1);
            $values[] = ':workflow' . ($i + 1);
            $params[':workflow' . ($i + 1)] = $workflow[$i];
        }

        $result = $qb->insertArr('processes', $keys)
                     ->valuesArr($values)
                     ->setParams($params)
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function getProcessForIdDocument(int $idDocument) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('processes')
                  ->where('id_document=:id_document')
                  ->setParam(':id_document', $idDocument)
                  ->execute()
                  ->fetchSingle();

        return $this->createProcessObjectFromDbRow($row);
    }

    private function createProcessObjectFromDbRow($row) {
        if($row === NULL) {
            return null;
        }

        $id = $row['id'];
        $dateCreated = $row['date_created'];
        $idDocument = $row['id_document'];
        $type = $row['type'];
        $status = $row['status'];
        $workflow1 = $row['workflow1'];
        $workflow2 = $row['workflow2'];
        $workflow3 = $row['workflow3'];
        $workflow4 = $row['workflow4'];
        $workflowStatus = $row['workflow_status'];

        return new Process($id, $dateCreated, $idDocument, $workflow1, $workflow2, $workflow3, $workflow4, $workflowStatus, $type, $status);
    }
}

?>