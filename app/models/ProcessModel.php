<?php

namespace DMS\Models;

use DMS\Constants\ProcessStatus;
use DMS\Constants\ProcessTypes;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\Process;
use DMS\Widgets\HomeDashboard\ProcessStats;

class ProcessModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getFinishedProcessesWithUserCount(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('processes')
            ->where('WHERE ' . $this->xb()
                                    ->lb()
                                        ->where('status = ?', [ProcessStatus::FINISHED])
                                    ->rb()
                                    ->and()
                                    ->lb()
                                        ->where('workflow1 = ?', [$idUser])
                                        ->orWhere('workflow2 = ?', [$idUser])
                                        ->orWhere('workflow3 = ?', [$idUser])
                                        ->orWhere('workflow4 = ?', [$idUser])
                                    ->rb()
                                    ->build())
            ->execute();

        return $qb->fetchAll()->num_rows;
    }

    public function getProcessesWithUserCount(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('processes')
            ->where('WHERE ' . $this->xb()
                                    ->lb()
                                        ->where('status <> ?', [ProcessStatus::FINISHED])
                                    ->rb()
                                    ->and()
                                    ->lb()
                                        ->where('workflow1 = ?', [$idUser])
                                        ->orWhere('workflow2 = ?', [$idUser])
                                        ->orWhere('workflow3 = ?', [$idUser])
                                        ->orWhere('workflow4 = ?', [$idUser])
                                    ->rb()
                                    ->build())
            ->execute();
                        
        return $qb->fetchAll()->num_rows;
    }

    public function getProcessesWhereUserIsAuthorCount(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('processes')
            ->where('WHERE ' . $this->xb()
                                    ->lb()
                                        ->where('status = ?', [ProcessStatus::FINISHED])
                                    ->rb()
                                    ->and()
                                    ->lb()
                                        ->where('workflow1 = ?', [$idUser])
                                        ->orWhere('workflow2 = ?', [$idUser])
                                        ->orWhere('workflow3 = ?', [$idUser])
                                        ->orWhere('workflow4 = ?', [$idUser])
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
                                        ->where('status = ?', [ProcessStatus::FINISHED])
                                    ->rb()
                                    ->and()
                                    ->lb()
                                        ->where('workflow1 = ?', [$idUser])
                                        ->orWhere('workflow2 = ?', [$idUser])
                                        ->orWhere('workflow3 = ?', [$idUser])
                                        ->orWhere('workflow4 = ?', [$idUser])
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
                                        ->where('status <> ?', [ProcessStatus::FINISHED])
                                    ->rb()
                                    ->and()
                                    ->lb()
                                        ->where('workflow1 = ?', [$idUser])
                                        ->orWhere('workflow2 = ?', [$idUser])
                                        ->orWhere('workflow3 = ?', [$idUser])
                                        ->orWhere('workflow4 = ?', [$idUser])
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
                                        ->where('status = ?', [ProcessStatus::FINISHED])
                                    ->rb()
                                    ->and()
                                    ->lb()
                                        ->where('workflow1 = ?', [$idUser])
                                        ->orWhere('workflow2 = ?', [$idUser])
                                        ->orWhere('workflow3 = ?', [$idUser])
                                        ->orWhere('workflow4 = ?', [$idUser])
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

        $qb ->select(['COUNT(id) AS cnt'])
            ->from('processes')
            ->where('WHERE ' . $this->xb()
                        ->lb()
                            ->lb()
                                ->where('workflow1 = ?', [$idUser])
                                ->andWhere('workflow_status = 1')
                            ->rb()
                            ->or()
                            ->lb()
                                ->where('workflow2 = ?', [$idUser])
                                ->andWhere('workflow_status = 2')
                            ->rb()
                            ->or()
                            ->lb()
                                ->where('workflow3 = ?', [$idUser])
                                ->andWhere('workflow_status = 3')
                            ->rb()
                            ->or()
                            ->lb()
                                ->where('workflow4 = ?', [$idUser])
                                ->andWhere('workflow_status = 4')
                            ->rb()
                        ->rb()
                        ->build())
            ->andWhere('status = ?', [ProcessStatus::IN_PROGRESS])
            ->execute();

        return $qb->fetch('cnt');
    }

    public function getCountProcessesStartedByUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(id) AS cnt'])
            ->from('processes')
            ->where('id_author = ?', [$idUser])
            ->execute();

        return $qb->fetch('cnt');
    }

    public function getCountFinishedProcesses() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(id) AS cnt'])
            ->from('processes')
            ->where('status = ?', [ProcessStatus::FINISHED])
            ->execute();

        return $qb->fetch('cnt');
    }

    public function getFirstIdProcessOnAGridPage(int $gridPage) {
        if($gridPage == 0) $gridPage = 1;
        return $this->getFirstRowWithCount($gridPage, 'processes', ['id']);
    }

    public function getLastProcessStatsEntry() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('process_stats')
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->execute();

        return $qb->fetch();
    }

    public function insertProcessStatsEntry(array $data) {
        return $this->insertNew($data, 'process_stats');
    }

    public function getAllProcessIds() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['id'])
            ->from('processes')
            ->execute();

        $ids = [];
        while($row = $qb->fetchAssoc()) {
            $ids[] = $row['id'];
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
            ->where('id_document = ?', [$idDocument])
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
            ->where('id = ?', [$idProcess])
            ->execute();

        return $qb->fetchAll();
    }

    public function removeProcessesForIdDocument(int $idDocument) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('processes')
            ->where('id_document = ?', [$idDocument])
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
                                ->where('workflow1 = ?', [$idUser])
                                ->andWhere('workflow_status = 1')
                            ->rb()
                            ->or()
                            ->lb()
                                ->where('workflow2 = ?', [$idUser])
                                ->andWhere('workflow_status = 2')
                            ->rb()
                            ->or()
                            ->lb()
                                ->where('workflow3 = ?', [$idUser])
                                ->andWhere('workflow_status = 3')
                            ->rb()
                            ->or()
                            ->lb()
                                ->where('workflow4 = ?', [$idUser])
                                ->andWhere('workflow_status = 4')
                            ->rb()
                        ->rb()
                        ->build())
            ->andWhere('status = ?', [ProcessStatus::IN_PROGRESS]);

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

        $qb ->select(['COUNT(id) AS cnt'])
            ->from('processes');

        switch($status) {
            case 0:
                break;

            default:
                $qb->where('status = ?', [$status]);
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
                                        ->where('status = ?', [ProcessStatus::FINISHED])
                                    ->rb()
                                    ->and()
                                    ->lb()
                                        ->where('workflow1 = ?', [$idUser])
                                        ->orWhere('workflow2 = ?', [$idUser])
                                        ->orWhere('workflow3 = ?', [$idUser])
                                        ->orWhere('workflow4 = ?', [$idUser])
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
            ->where('id_author = ?', [$idUser])
            ->andWhere('status <> ?', [ProcessStatus::FINISHED])
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
            ->set(['status' => $status, 'date_updated' => date(Database::DB_DATE_FORMAT)])
            ->where('id = ?', [$idProcess])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateWorkflowStatus(int $idProcess, int $status) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('processes')
            ->set(['workflow_status' => $status, 'date_updated' => date(Database::DB_DATE_FORMAT)])
            ->where('id = ?', [$idProcess])
            ->execute();

        return $qb->fetchAll();
    }

    public function getProcessById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('processes')
            ->where('id = ?', [$id])
            ->execute();

        return $this->createProcessObjectFromDbRow($qb->fetch());
    }

    public function getProcessesWithIdUser(int $idUser, int $limit = 25) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('processes')
            ->where('WHERE ' . $this->xb()
                                    ->lb()
                                        ->where('status <> ?', [ProcessStatus::FINISHED])
                                    ->rb()
                                    ->and()
                                    ->lb()
                                        ->where('workflow1 = ?', [$idUser])
                                        ->orWhere('workflow2 = ?', [$idUser])
                                        ->orWhere('workflow3 = ?', [$idUser])
                                        ->orWhere('workflow4 = ?', [$idUser])
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

        $qb ->insert('processes', ['type'])
            ->values([$type])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateProcess(int $id, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('processes')
            ->set($data)
            ->where('id = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function getLastInsertedIdProcess() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['id'])
            ->from('processes')
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->execute();

        return $qb->fetch('id');
    }

    public function insertNewProcess(array $data) {
        return $this->insertNew($data, 'processes');
    }

    public function getProcessForIdDocument(int $idDocument, bool $isArchive = false) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('processes')
            ->where('id_document = ?', [$idDocument]);

        if($isArchive) {
            $qb->andWhere('is_archive = 1');
        } else {
            $qb->andWhere('is_archive = 0');
        }

        $qb ->orderBy('id', 'DESC')
            ->limit(1)
            ->execute();

        return $this->createProcessObjectFromDbRow($qb->fetch());
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
        $dateUpdated = $row['date_updated'];
        $isArchive = $row['is_archive'];
        
        if($isArchive == '1') {
            $isArchive = true;
        } else {
            $isArchive = false;
        }

        return new Process($id, $dateCreated, $idDocument, $workflow1, $workflow2, $workflow3, $workflow4, $workflowStatus, $type, $status, $idAuthor, $dateUpdated, $isArchive);
    }
}

?>