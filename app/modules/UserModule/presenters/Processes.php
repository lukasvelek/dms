<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\ProcessTypes;
use DMS\Core\TemplateManager;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

class Processes extends APresenter {
    private string $name;
    private TemplateManager $templateManager;
    private IModule $module;

    public const DRAW_TOPPANEL = true;

    public function __construct() {
        $this->name = 'Processes';
        
        $this->templateManager = TemplateManager::getTemporaryObject();
    }

    public function setModule(IModule $module) {
        $this->module = $module;
    }

    public function getModule() {
        return $this->module;
    }

    public function getName() {
        return $this->name;
    }

    protected function showAll() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/processes/process-grid.html');

        $data = array(
            '$PAGE_TITLE$' => 'Processes'
        );

        $table = $this->internalCreateStandardProcessGrid();

        $data['$PROCESS_GRID$'] = $table;

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateStandardProcessGrid() {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $headers = array(
            'Actions',
            'Name',
            'Workflow 1',
            'Workflow 2',
            'Workflow 3',
            'Workflow 4',
            'Workflow Status',
            'Current officer',
            'Type'
        );

        $headerRow = null;

        $processes = $app->processModel->getProcessesWithIdUser($app->user->getId());

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
                    $workflow1User = $app->userModel->getUserById($process->getWorkflowStep(0))->getFullname();
                } else {
                    $workflow1User = '-';
                }

                if($process->getWorkflowStep(1) != null) {
                    $workflow2User = $app->userModel->getUserById($process->getWorkflowStep(1))->getFullname();
                } else {
                    $workflow2User = '-';
                }

                if($process->getWorkflowStep(2) != null) {
                    $workflow3User = $app->userModel->getUserById($process->getWorkflowStep(2))->getFullname();
                } else {
                    $workflow3User = '-';
                }

                if($process->getWorkflowStep(3) != null) {
                    $workflow4User = $app->userModel->getUserById($process->getWorkflowStep(3))->getFullname();
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

        return $tb->build();
    }
}

?>