<?php

use DMS\Constants\ProcessTypes;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

require_once('Ajax.php');

$filter = 'waitingForMe';

if(is_null($user)) {
    echo 'User is null';
    return;
}

$idUser = $user->getId();

if(isset($_POST['filter'])) {
    $filter = htmlspecialchars($_POST['filter']);
}

$tb = TableBuilder::getTemporaryObject();

$headers = array(
    'Actions',
    'Name',
    'Workflow 1',
    'Workflow 2',
    'Workflow 3',
    'Workflow 4',
    'Workflow status',
    'Current officer',
    'Type'
);

$headerRow = null;

$processes = [];

switch($filter) {
    case 'startedByMe':
        $processes = $processModel->getProcessesWhereIdUserIsAuthor($idUser);
        break;

    case 'waitingForMe':
        $processes = $processModel->getProcessesWithIdUser($idUser);
        break;

    case 'finished':
        $processes = $processModel->getFinishedProcessesWithIdUser($idUser);
        break;
}

if(empty($processes)) {
    $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
} else {
    foreach($processes as $process) {
        $actionLinks = array(
            LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleProcess:showProcess', 'id' => $process->getId()), 'Open')
        );

        if(is_null($headerRow)) {
            $row = $tb->createRow();

            foreach($headers as $header) {
                $col = $tb->createCol()->setText($header)
                                       ->setBold();

                if($header == 'Actions') {
                    $col->setColspan(count($actionLinks));
                }

                $row->addCol($col);
            }

            $headerRow = $row;

            $tb->addRow($row);
        }

        $procRow = $tb->createRow();

        foreach($actionLinks as $actionLink) {
            $procRow->addCol($tb->createCol()->setText($actionLink));
        }

        if($process->getWorkflowStep(0) != null) {
            $workflow1User = $userModel->getUserById($process->getWorkflowStep(0))->getFullname();
        } else {
            $workflow1User = '-';
        }

        if($process->getWorkflowStep(1) != null) {
            $workflow2User = $userModel->getUserById($process->getWorkflowStep(1))->getFullname();
        } else {
            $workflow2User = '-';
        }

        if($process->getWorkflowStep(2) != null) {
            $workflow3User = $userModel->getUserById($process->getWorkflowStep(2))->getFullname();
        } else {
            $workflow3User = '-';
        }

        if($process->getWorkflowStep(3) != null) {
            $workflow4User = $userModel->getUserById($process->getWorkflowStep(3))->getFullname();
        } else {
            $workflow4User = '-';
        }

        $procRow->addCol($tb->createCol()->setText(ProcessTypes::$texts[$process->getType()]))
                ->addCol($tb->createCol()->setText($workflow1User))
                ->addCol($tb->createCol()->setText($workflow2User))
                ->addCol($tb->createCol()->setText($workflow3User))
                ->addCol($tb->createCol()->setText($workflow4User))
                ->addCol($tb->createCol()->setText($process->getWorkflowStatus() ?? '-'))
                ->addCol($tb->createCol()->setText(${'workflow' . $process->getWorkflowStatus() . 'User'}))
                ->addCol($tb->createCol()->setText(ProcessTypes::$texts[$process->getType()]))
        ;

        $tb->addRow($procRow);
    }
}

echo $tb->build();

?>