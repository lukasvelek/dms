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
        foreach($qb->fetchAll() as $row) {
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
        foreach($qb->fetchAll() as $row) {
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
        foreach($qb->fetchAll() as $row) {
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

        /*$rows = $qb->select('*')
                   ->from('processes')
                   ->explicit(' WHERE ')
                   ->leftBracket()
                   ->leftBracket()
                   ->where('workflow1=:id_user', false, false)
                   ->andWhere('workflow_status=1')
                   ->rightBracket()
                   ->explicit('OR')
                   ->leftBracket()
                   ->where('workflow2=:id_user', false, false)
                   ->andWhere('workflow_status=2')
                   ->rightBracket()
                   ->explicit('OR')
                   ->leftBracket()
                   ->where('workflow3=:id_user', false, false)
                   ->andWhere('workflow_status=3')
                   ->rightBracket()
                   ->explicit('OR')
                   ->leftBracket()
                   ->where('workflow4=:id_user', false, false)
                   ->andWhere('workflow_status=4')
                   ->rightBracket()
                   ->rightBracket()
                   ->andWhere('status=:status')
                   ->setParams(array(
                    ':id_user' => $idUser,
                    ':status' => ProcessStatus::IN_PROGRESS
                   ));

        if($limit > 0) {
            $rows->limit($limit);
        }

        $rows = $rows->execute()->fetch();*/

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
        foreach($qb->fetchAll() as $row) {
            $processes[] = $this->createProcessObjectFromDbRow($row);
        }

        return $processes;
    }

    public function getProcessCountByStatus(int $status = 0) {
        $qb = $this->qb(__METHOD__);

        /*$qb = $qb->selectCount('id', 'cnt')
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

        return $row;*/

        $qb ->select(['COUNT(id)'])
            ->from('processes');

        switch($status) {
            case 0:
                break;

            default:
                $qb->where('status = ?', [$status]);
                break;
        }

        $qb->execute();

        return $qb->fetch();
    }

    public function getFinishedProcessesWithIdUserFromId(?int $idFrom, int $idUser, int $limit) {
        if(is_null($idFrom)) {
            return [];
        }

        $qb = $this->qb(__METHOD__);
        
        /*$rows = $qb->select('*')
                   ->from('processes')
                   ->where('status=:status')
                   ->explicit(' AND')
                   ->leftBracket()
                   ->where('workflow1=:id_user', false, false)
                   ->orWhere('workflow2=:id_user')
                   ->orWhere('workflow3=:id_user')
                   ->orWhere('workflow4=:id_user')
                   ->rightBracket()
                   ->setParams(array(
                    ':id_user' => $idUser,
                    ':status' => ProcessStatus::FINISHED
                   ));

        if($idFrom == 1) {
            $rows->explicit('AND `id` >= ' . $idFrom . ' ');
        } else {
            $rows->explicit('AND `id` > ' . $idFrom . ' ');
        }

        $rows = $rows->execute()->fetch();*/

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
                                    ->build());

        if($idFrom == 1) {
            $qb ->andWhere('id >= ?', [$idFrom]);
        } else {
            $qb ->andWhere('id > ?', [$idFrom]);
        }

        $qb->execute();

        $processes = [];
        foreach($qb->fetchAll() as $row) {
            $processes[] = $this->createProcessObjectFromDbRow($row);
        }

        return $processes;
    }

    public function getFinishedProcessesWithIdUser(int $idUser, int $limit) {
        $qb = $this->qb(__METHOD__);

        /*$rows = $qb->select(['*'])
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
                   ->limit($limit)
                   ->execute()
                   ->fetch();

        $processes = [];
        foreach($rows as $row) {
            $processes[] = $this->createProcessObjectFromDbRow($row);
        }

        return $processes;*/

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
        foreach($qb->fetchAll() as $row) {
            $processes[] = $this->createProcessObjectFromDbRow($row);
        }

        return $processes;
    }

    public function getProcessesWhereIdUserIsAuthorFromId(?int $idFrom, int $idUser, int $limit) {
        if(is_null($idFrom)) {
            return [];
        }

        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('processes')
            ->where('id_author = ?', [$idUser])
            ->andWhere('status <> ?', [ProcessStatus::FINISHED]);

        if($idFrom == 1) {
            $qb->andWhere('id >= ?', [$idFrom]);
            //$rows->explicit('AND `id` >= ' . $idFrom . ' ');
        } else {
            $qb->andWhere('id > ?', [$idFrom]);
            //$rows->explicit('AND `id` > ' . $idFrom . ' ');
        }

        /*$rows = $rows->limit($limit)
                     ->setParams(array(
                        ':id_author' => $idUser,
                        ':status' => ProcessStatus::FINISHED
                     ))
                     ->execute()
                     ->fetch();*/

        $qb ->limit($limit)
            ->execute();

        $processes = [];
        foreach($qb->fetchAll() as $row) {
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
        foreach($qb->fetchAll() as $row) {
            $processes[] = $this->createProcessObjectFromDbRow($row);
        }

        return $processes;
    }

    public function updateStatus(int $idProcess, int $status) {
        $qb = $this->qb(__METHOD__);

        /*$qb ->update('processes')
            ->set(array(
                'status' => ':status',
                'date_updated' => ':date'
            ))
            ->setParam(':status', $status)
            ->setParam(':date', date(Database::DB_DATE_FORMAT))
            ->where('id=:id')
            ->setParam(':id', $idProcess)
            ->execute();*/

        $qb ->update('processes')
            ->set(['status' => $status, 'date_updated' => date(Database::DB_DATE_FORMAT)])
            ->where('id = ?', [$idProcess])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateWorkflowStatus(int $idProcess, int $status) {
        $qb = $this->qb(__METHOD__);

        /*$result = $qb->update('processes')
                     ->set(array(
                        'workflow_status' => ':status',
                        'date_updated' => ':date'
                     ))
                     ->setParam(':status', $status)
                     ->setParam(':date', date(Database::DB_DATE_FORMAT))
                     ->where('id=:id')
                     ->setParam(':id', $idProcess)
                     ->execute()
                     ->fetch();

        return $result;*/

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

        return $this->createProcessObjectFromDbRow($qb->fetchAll());
    }

    public function getProcessesWithIdUserFromId(?int $idFrom, int $idUser, int $limit) {
        if(is_null($idFrom)) {
            return [];
        }

        $qb = $this->qb(__METHOD__);

        /*$rows = $qb->select('*')
                   ->from('processes')
                   ->whereNot('status=:status')
                   ->explicit(' AND ')
                   ->leftBracket()
                   ->where('workflow1=:id_user', false, false)
                   ->orWhere('workflow2=:id_user')
                   ->orWhere('workflow3=:id_user')
                   ->orWhere('workflow4=:id_user')
                   ->rightBracket()
                   ->setParams(array(
                    ':id_user' => $idUser,
                    ':status' => ProcessStatus::FINISHED
                   ));

        if($idFrom == 1) {
            $rows->explicit('AND `id` >= ' . $idFrom . ' ');
        } else {
            $rows->explicit('AND `id` > ' . $idFrom . ' ');
        }

        $rows = $rows->limit($limit)
                     ->execute()
                     ->fetch();*/

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
                                    ->build());

        if($idFrom == 1) {
            $qb ->andWhere('id >= ?', [$idFrom]);
        } else {
            $qb ->andWhere('id > ?', [$idFrom]);
        }

        $qb ->limit($limit)
            ->execute();

        $processes = [];
        foreach($qb->fetchAll() as $row) {
            $processes[] = $this->createProcessObjectFromDbRow($row);
        }

        return $processes;
    }

    public function getProcessesWithIdUser(int $idUser, int $limit = 25) {
        $qb = $this->qb(__METHOD__);

        /*$rows = $qb->select('*')
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
                   ->limit($limit)
                   ->execute()
                   ->fetch();

        $processes = [];
        foreach($rows as $row) {
            $processes[] = $this->createProcessObjectFromDbRow($row);
        }

        return $processes;*/

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
        foreach($qb->fetchAll() as $row) {
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

        /*$sets = [];
        $params = [];

        foreach($data as $k => $v) {
            $sets[] = $k . '=:' . $k;
            $params[':' . $k] = $v;
        }

        if(!array_key_exists('date_updated', $sets)) {
            $sets[] = 'date_updated=:date_updated';
            $params[':date_updated'] = date(Database::DB_DATE_FORMAT);
        }

        $result = $qb->update('processes')
                     ->set($sets)
                     ->setParams($params)
                     ->execute()
                     ->fetch();

        return $result;*/

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

        /*$row = $qb->select('*')
                  ->from('processes')
                  ->where('id_document=:id_document')
                  ->setParam(':id_document', $idDocument);

        if($isArchive) {
            $row->andWhere('is_archive=:is_archive')
                ->setParam(':is_archive', '1');
        } else {
            $row->andWhere('is_archive=:is_archive')
                ->setParam(':is_archive', '0');
        }

        $row = $row->orderBy('id', 'DESC')
                   ->execute()
                   ->fetchSingle();

        return $this->createProcessObjectFromDbRow($row);*/

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