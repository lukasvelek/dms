<?php

namespace DMS\Models;

use DMS\Constants\Metadata\ProcessMetadata;
use DMS\Constants\Metadata\ProcessStatsMetadata;
use DMS\Constants\ProcessStatus;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Process;

class ProcessModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function composeStandardProcessQuery(array $selectValues = ['*']) {
        $qb = $this->qb(__METHOD__);

        $qb ->select($selectValues)
            ->from('processes');

        return $qb;
    }

    public function getFinishedProcessesWithUserCount(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select([ProcessMetadata::ID])
            ->from('processes')
            ->where('WHERE ' . $this->xb()
                                    ->lb()
                                        ->where(ProcessMetadata::STATUS . ' = ?', [ProcessStatus::FINISHED])
                                    ->rb()
                                    ->and()
                                    ->lb()
                                        ->where(ProcessMetadata::WORKFLOW1 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW2 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW3 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW4 . ' = ?', [$idUser])
                                    ->rb()
                                    ->build())
            ->execute();

        return $qb->fetchAll()->num_rows;
    }

    public function getProcessesWithUserCount(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select([ProcessMetadata::ID])
            ->from('processes')
            ->where('WHERE ' . $this->xb()
                                    ->lb()
                                        ->where(ProcessMetadata::STATUS . ' <> ?', [ProcessStatus::FINISHED])
                                    ->rb()
                                    ->and()
                                    ->lb()
                                        ->where(ProcessMetadata::WORKFLOW1 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW2 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW3 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW4 . ' = ?', [$idUser])
                                    ->rb()
                                    ->build())
            ->execute();
                        
        return $qb->fetchAll()->num_rows;
    }

    public function getProcessesWhereUserIsAuthorCount(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select([ProcessMetadata::ID])
            ->from('processes')
            ->where('WHERE ' . $this->xb()
                                    ->lb()
                                        ->where(ProcessMetadata::STATUS . ' = ?', [ProcessStatus::FINISHED])
                                    ->rb()
                                    ->and()
                                    ->lb()
                                        ->where(ProcessMetadata::WORKFLOW1 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW2 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW3 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW4 . ' = ?', [$idUser])
                                    ->rb()
                                    ->build())
            ->execute();

        return $qb->fetchAll()->num_rows;
    }

    public function getFinishedProcessesWithUserWithOffset(int $idUser, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('processes')
            ->where('WHERE ' . $this->xb()
                                    ->lb()
                                        ->where(ProcessMetadata::STATUS . ' = ?', [ProcessStatus::FINISHED])
                                    ->rb()
                                    ->and()
                                    ->lb()
                                        ->where(ProcessMetadata::WORKFLOW1 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW2 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW3 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW4 . ' = ?', [$idUser])
                                    ->rb()
                                    ->build())
            ->limit($limit)
            ->offset($offset)
            ->execute();

        $processes = [];
        while($row = $qb->fetchAssoc()) {
            $processes[] = $this->createProcessObjectFromDbRow($row);
        }

        return $processes;
    }

    public function getProcessesWithUserWithOffset(int $idUser, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('processes')
            ->where('WHERE ' . $this->xb()
                                    ->lb()
                                        ->where(ProcessMetadata::STATUS . ' <> ?', [ProcessStatus::FINISHED])
                                    ->rb()
                                    ->and()
                                    ->lb()
                                        ->where(ProcessMetadata::WORKFLOW1 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW2 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW3 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW4 . ' = ?', [$idUser])
                                    ->rb()
                                    ->build())
            ->limit($limit)
            ->offset($offset)
            ->execute();
                        
        $processes = [];
        while($row = $qb->fetchAssoc()) {
            $processes[] = $this->createProcessObjectFromDbRow($row);
        }
                            
        return $processes;
    }

    public function getProcessesWhereUserIsAuthorWithOffset(int $idUser, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('processes')
            ->where('WHERE ' . $this->xb()
                                    ->lb()
                                        ->where(ProcessMetadata::STATUS . ' = ?', [ProcessStatus::FINISHED])
                                    ->rb()
                                    ->and()
                                    ->lb()
                                        ->where(ProcessMetadata::WORKFLOW1 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW2 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW3 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW4 . ' = ?', [$idUser])
                                    ->rb()
                                    ->build())
            ->limit($limit)
            ->offset($offset)
            ->execute();

        $processes = [];
        while($row = $qb->fetchAssoc()) {
            $processes[] = $this->createProcessObjectFromDbRow($row);
        }
    
        return $processes;
    }

    public function getCountProcessesWaitingForUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(' . ProcessMetadata::ID . ') AS cnt'])
            ->from('processes')
            ->where('WHERE ' . $this->xb()
                        ->lb()
                            ->lb()
                                ->where(ProcessMetadata::WORKFLOW1 . ' = ?', [$idUser])
                                ->andWhere(ProcessMetadata::WORKFLOW_STATUS . ' = 1')
                            ->rb()
                            ->or()
                            ->lb()
                                ->where(ProcessMetadata::WORKFLOW2 . ' = ?', [$idUser])
                                ->andWhere(ProcessMetadata::WORKFLOW_STATUS . ' = 2')
                            ->rb()
                            ->or()
                            ->lb()
                                ->where(ProcessMetadata::WORKFLOW3 . ' = ?', [$idUser])
                                ->andWhere(ProcessMetadata::WORKFLOW_STATUS . ' = 3')
                            ->rb()
                            ->or()
                            ->lb()
                                ->where(ProcessMetadata::WORKFLOW4 . ' = ?', [$idUser])
                                ->andWhere(ProcessMetadata::WORKFLOW_STATUS . ' = 4')
                            ->rb()
                        ->rb()
                        ->build())
            ->andWhere(ProcessMetadata::STATUS . ' = ?', [ProcessStatus::IN_PROGRESS])
            ->execute();

        return $qb->fetch('cnt');
    }

    public function getCountProcessesStartedByUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(' . ProcessMetadata::ID . ') AS cnt'])
            ->from('processes')
            ->where(ProcessMetadata::ID_AUTHOR . ' = ?', [$idUser])
            ->execute();

        return $qb->fetch('cnt');
    }

    public function getCountFinishedProcesses() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(' . ProcessMetadata::ID . ') AS cnt'])
            ->from('processes')
            ->where(ProcessMetadata::STATUS . ' = ?', [ProcessStatus::FINISHED])
            ->execute();

        return $qb->fetch('cnt');
    }

    public function getLastProcessStatsEntry() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('process_stats')
            ->orderBy(ProcessStatsMetadata::ID, 'DESC')
            ->limit(1)
            ->execute();

        return $qb->fetch();
    }

    public function insertProcessStatsEntry(array $data) {
        return $this->insertNew($data, 'process_stats');
    }

    public function getAllProcessIds() {
        $qb = $this->qb(__METHOD__);

        $qb ->select([ProcessMetadata::ID])
            ->from('processes')
            ->execute();

        $ids = [];
        while($row = $qb->fetchAssoc()) {
            $ids[] = $row[ProcessMetadata::ID];
        }

        return $ids;
    }

    public function getAllProcesses() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('processes')
            ->execute();

        $processes = [];
        while($row = $qb->fetchAssoc()) {
            $processes[] = $this->createProcessObjectFromDbRow($row);
        }

        return $processes;
    }

    public function getProcessesForIdDocument(int $idDocument) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('processes')
            ->where(ProcessMetadata::ID_DOCUMENT . ' = ?', [$idDocument])
            ->execute();

        $processes = [];
        while($row = $qb->fetchAssoc()) {
            $processes[] = $this->createProcessObjectFromDbRow($row);
        }

        return $processes;
    }

    public function deleteProcess(int $idProcess) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('processes')
            ->where(ProcessMetadata::ID . ' = ?', [$idProcess])
            ->execute();

        return $qb->fetchAll();
    }

    public function removeProcessesForIdDocument(int $idDocument) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('processes')
            ->where(ProcessMetadata::ID_DOCUMENT . ' = ?', [$idDocument])
            ->execute();

        return $qb->fetchAll();
    }

    public function getProcessesWaitingForUser(int $idUser, int $limit = 0) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('processes')
            ->where('WHERE ' . $this->xb()
                        ->lb()
                            ->lb()
                                ->where(ProcessMetadata::WORKFLOW1 . ' = ?', [$idUser])
                                ->andWhere(ProcessMetadata::WORKFLOW_STATUS . ' = 1')
                            ->rb()
                            ->or()
                            ->lb()
                                ->where(ProcessMetadata::WORKFLOW2 . ' = ?', [$idUser])
                                ->andWhere(ProcessMetadata::WORKFLOW_STATUS . ' = 2')
                            ->rb()
                            ->or()
                            ->lb()
                                ->where(ProcessMetadata::WORKFLOW3 . ' = ?', [$idUser])
                                ->andWhere(ProcessMetadata::WORKFLOW_STATUS . ' = 3')
                            ->rb()
                            ->or()
                            ->lb()
                                ->where(ProcessMetadata::WORKFLOW4 . ' = ?', [$idUser])
                                ->andWhere(ProcessMetadata::WORKFLOW_STATUS . ' = 4')
                            ->rb()
                        ->rb()
                        ->build())
            ->andWhere(ProcessMetadata::STATUS . ' = ?', [ProcessStatus::IN_PROGRESS]);

        if($limit > 0) {
            $qb->limit($limit);
        }

        $qb->execute();

        $processes = [];
        while($row = $qb->fetchAssoc()) {
            $processes[] = $this->createProcessObjectFromDbRow($row);
        }

        return $processes;
    }

    public function getProcessCountByStatus(int $status = 0) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(' . ProcessMetadata::ID . ') AS cnt'])
            ->from('processes');

        switch($status) {
            case 0:
                break;

            default:
                $qb->where(ProcessMetadata::STATUS . ' = ?', [$status]);
                break;
        }

        $qb->execute();

        return $qb->fetch('cnt');
    }

    public function getFinishedProcessesWithIdUser(int $idUser, int $limit) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('processes')
            ->where('WHERE ' . $this->xb()
                                    ->lb()
                                        ->where(ProcessMetadata::STATUS . ' = ?', [ProcessStatus::FINISHED])
                                    ->rb()
                                    ->and()
                                    ->lb()
                                        ->where(ProcessMetadata::WORKFLOW1 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW2 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW3 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW4 . ' = ?', [$idUser])
                                    ->rb()
                                    ->build())
            ->limit($limit)
            ->execute();

        $processes = [];
        while($row = $qb->fetchAssoc()) {
            $processes[] = $this->createProcessObjectFromDbRow($row);
        }

        return $processes;
    }

    public function getProcessesWhereIdUserIsAuthor(int $idUser, int $limit) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('processes')
            ->where(ProcessMetadata::ID_AUTHOR . ' = ?', [$idUser])
            ->andWhere(ProcessMetadata::STATUS . ' <> ?', [ProcessStatus::FINISHED])
            ->limit($limit)
            ->execute();

        $processes = [];
        while($row = $qb->fetchAssoc()) {
            $processes[] = $this->createProcessObjectFromDbRow($row);
        }

        return $processes;
    }

    public function updateStatus(int $idProcess, int $status) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('processes')
            ->set([ProcessMetadata::STATUS => $status, ProcessMetadata::DATE_UPDATED => date(Database::DB_DATE_FORMAT)])
            ->where(ProcessMetadata::ID . ' = ?', [$idProcess])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateWorkflowStatus(int $idProcess, int $status) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('processes')
            ->set([ProcessMetadata::WORKFLOW_STATUS => $status, ProcessMetadata::DATE_UPDATED => date(Database::DB_DATE_FORMAT)])
            ->where(ProcessMetadata::ID . ' = ?', [$idProcess])
            ->execute();

        return $qb->fetchAll();
    }

    public function getProcessById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('processes')
            ->where(ProcessMetadata::ID . ' = ?', [$id])
            ->execute();

        return $this->createProcessObjectFromDbRow($qb->fetch());
    }

    public function getProcessesWithIdUser(int $idUser, int $limit = 25) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('processes')
            ->where('WHERE ' . $this->xb()
                                    ->lb()
                                        ->where(ProcessMetadata::STATUS . ' <> ?', [ProcessStatus::FINISHED])
                                    ->rb()
                                    ->and()
                                    ->lb()
                                        ->where(ProcessMetadata::WORKFLOW1 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW2 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW3 . ' = ?', [$idUser])
                                        ->orWhere(ProcessMetadata::WORKFLOW4 . ' = ?', [$idUser])
                                    ->rb()
                                    ->build())
            ->limit($limit)
            ->execute();

        $processes = [];
        while($row = $qb->fetchAssoc()) {
            $processes[] = $this->createProcessObjectFromDbRow($row);
        }

        return $processes;
    }

    public function insertEmptyProcess(int $type) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('processes', [ProcessMetadata::TYPE])
            ->values([$type])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateProcess(int $id, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('processes')
            ->set($data)
            ->where(ProcessMetadata::ID . ' = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function getLastInsertedIdProcess() {
        $qb = $this->qb(__METHOD__);

        $qb ->select([ProcessMetadata::ID])
            ->from('processes')
            ->orderBy(ProcessMetadata::ID, 'DESC')
            ->limit(1)
            ->execute();

        return $qb->fetch(ProcessMetadata::ID);
    }

    public function insertNewProcess(array $data) {
        return $this->insertNew($data, 'processes');
    }

    public function getProcessForIdDocument(int $idDocument, bool $isArchive = false) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('processes')
            ->where(ProcessMetadata::ID_DOCUMENT . ' = ?', [$idDocument]);

        if($isArchive) {
            $qb->andWhere(ProcessMetadata::IS_ARCHIVE . ' = 1');
        } else {
            $qb->andWhere(ProcessMetadata::IS_ARCHIVE . ' = 0');
        }

        $qb ->orderBy(ProcessMetadata::ID, 'DESC')
            ->limit(1)
            ->execute();

        return $this->createProcessObjectFromDbRow($qb->fetch());
    }

    private function createProcessObjectFromDbRow($row) {
        if($row === NULL) {
            return null;
        }

        $id = $row[ProcessMetadata::ID];
        $dateCreated = $row[ProcessMetadata::DATE_CREATED];
        $idDocument = $row[ProcessMetadata::ID_DOCUMENT];
        $type = $row[ProcessMetadata::TYPE];
        $status = $row[ProcessMetadata::STATUS];
        $workflow1 = $row[ProcessMetadata::WORKFLOW1];
        $workflow2 = $row[ProcessMetadata::WORKFLOW2];
        $workflow3 = $row[ProcessMetadata::WORKFLOW3];
        $workflow4 = $row[ProcessMetadata::WORKFLOW4];
        $workflowStatus = $row[ProcessMetadata::WORKFLOW_STATUS];
        $idAuthor = $row[ProcessMetadata::ID_AUTHOR];
        $dateUpdated = $row[ProcessMetadata::DATE_UPDATED];
        $isArchive = $row[ProcessMetadata::IS_ARCHIVE];
        
        if($isArchive == '1') {
            $isArchive = true;
        } else {
            $isArchive = false;
        }

        return new Process($id, $dateCreated, $idDocument, $workflow1, $workflow2, $workflow3, $workflow4, $workflowStatus, $type, $status, $idAuthor, $dateUpdated, $isArchive);
    }
}

?>