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

    public function getProcessesWaitingForUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('processes')
                   ->explicit(' WHERE ')
                   ->leftBracket()
                   ->leftBracket()
                   ->where('workflow1=:id_user', false, false)
                   ->orWhere('workflow_status=:w1')
                   ->rightBracket()
                   ->explicit('OR')
                   ->leftBracket()
                   ->where('workflow2=:id_user', false, false)
                   ->orWhere('workflow_status=:w2')
                   ->rightBracket()
                   ->explicit('OR')
                   ->leftBracket()
                   ->where('workflow3=:id_user', false, false)
                   ->orWhere('workflow_status=:w3')
                   ->rightBracket()
                   ->explicit('OR')
                   ->leftBracket()
                   ->where('workflow4=:id_user', false, false)
                   ->orWhere('workflow_status=:w4')
                   ->rightBracket()
                   ->rightBracket()
                   ->andWhere('status=:status')
                   ->setParams(array(
                    ':id_user' => $idUser,
                    ':w1' => '1',
                    ':w2' => '2',
                    ':w3' => '3',
                    ':w4' => '4',
                    ':status' => ProcessStatus::IN_PROGRESS
                   ))
                   ->execute()
                   ->fetch();

        $processes = [];
        foreach($rows as $row) {
            $processes[] = $this->createProcessObjectFromDbRow($row);
        }

        return $processes;
    }

    public function getProcessCountByStatus(int $status = 0) {
        $qb = $this->qb(__METHOD__);

        $qb = $qb->selectCount('id', 'cnt')
                 ->from('processes');

        switch($status) {
            case 0:
                break;

            default:
                $qb->where('status=:status')
                   ->setParam(':status', $status);

                break;
        }

        $row = $qb->execute()
                  ->fetchSingle('cnt');

        return $row;
    }

    public function getFinishedProcessesWithIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('processes')
                   ->where('status=:status')
                   ->setParam(':status', ProcessStatus::FINISHED)
                   ->explicit(' AND')
                   ->leftBracket()
                   ->where('workflow1=:id_user', false, false)
                   ->orWhere('workflow2=:id_user')
                   ->orWhere('workflow3=:id_user')
                   ->orWhere('workflow4=:id_user')
                   ->rightBracket()
                   ->setParam(':id_user', $idUser)
                   ->execute()
                   ->fetch();

        $processes = [];
        foreach($rows as $row) {
            $processes[] = $this->createProcessObjectFromDbRow($row);
        }

        return $processes;
    }

    public function getProcessesWhereIdUserIsAuthor(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('processes')
                   ->where('id_author=:id_author')
                   ->andWhereNot('status=:status')
                   ->setParam(':id_author', $idUser)
                   ->setParam(':status', ProcessStatus::FINISHED)
                   ->execute()
                   ->fetch();

        $processes = [];
        foreach($rows as $row) {
            $processes[] = $this->createProcessObjectFromDbRow($row);
        }

        return $processes;
    }

    public function updateStatus(int $idProcess, int $status) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->update('processes')
                     ->set(array(
                        'status' => ':status'
                     ))
                     ->setParam(':status', $status)
                     ->where('id=:id')
                     ->setParam(':id', $idProcess)
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function updateWorkflowStatus(int $idProcess, int $status) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->update('processes')
                     ->set(array(
                        'workflow_status' => ':status'
                     ))
                     ->setParam(':status', $status)
                     ->where('id=:id')
                     ->setParam(':id', $idProcess)
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function getProcessById(int $id) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('processes')
                  ->where('id=:id')
                  ->setParam(':id', $id)
                  ->execute()
                  ->fetchSingle();

        return $this->createProcessObjectFromDbRow($row);
    }

    public function getProcessesWithIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('processes')
                   ->whereNot('status=:status')
                   ->setParam(':status', ProcessStatus::FINISHED)
                   ->explicit(' AND')
                   ->leftBracket()
                   ->where('workflow1=:id_user', false, false)
                   ->orWhere('workflow2=:id_user')
                   ->orWhere('workflow3=:id_user')
                   ->orWhere('workflow4=:id_user')
                   ->rightBracket()
                   ->setParam(':id_user', $idUser)
                   ->execute()
                   ->fetch();

        $processes = [];
        foreach($rows as $row) {
            $processes[] = $this->createProcessObjectFromDbRow($row);
        }

        return $processes;
    }

    public function insertEmptyProcess(int $type) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->insert('processes', 'type')
                     ->values(':type')
                     ->setParam(':type', $type)
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function updateProcess(int $id, array $data) {
        $qb = $this->qb(__METHOD__);

        $sets = [];
        $params = [];

        foreach($data as $k => $v) {
            $sets[] = $k . '=:' . $k;
            $params[':' . $k] = $v;
        }

        $result = $qb->update('processes')
                     ->set($sets)
                     ->setParams($params)
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function getLastInsertedIdProcess() {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('id')
                  ->from('processes')
                  ->orderBy('id', 'DESC')
                  ->limit('1')
                  ->execute()
                  ->fetchSingle('id');

        return $row;
    }

    public function insertNewProcess(array $data) {
        return $this->insertNew($data, 'processes');
    }

    public function getProcessForIdDocument(int $idDocument) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('processes')
                  ->where('id_document=:id_document')
                  ->setParam(':id_document', $idDocument)
                  ->orderBy('id', 'DESC')
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
        $idAuthor = $row['id_author'];

        return new Process($id, $dateCreated, $idDocument, $workflow1, $workflow2, $workflow3, $workflow4, $workflowStatus, $type, $status, $idAuthor);
    }
}

?>