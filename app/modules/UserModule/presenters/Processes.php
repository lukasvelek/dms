<?php

namespace DMS\Modules\UserModule;

use DMS\Components\Process\HomeOffice;
use DMS\Constants\ProcessTypes;
use DMS\Core\TemplateManager;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;
use DMS\Panels\Panels;
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

    protected function showMenu() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/processes/process-grid.html');

        $data = array(
            '$PAGE_TITLE$' => 'Process menu',
            '$PROCESS_PANEL$' => Panels::createProcessesPanel()
        );

        $table = $this->internalCreateProcessMenuGrid();

        $data['$PROCESS_GRID$'] = $table;

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showAll() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/processes/process-grid.html');

        $data = array(
            '$PAGE_TITLE$' => 'Processes',
            '$PROCESS_PANEL$' => Panels::createProcessesPanel()
        );

        $table = $this->internalCreateStandardProcessGrid();

        $data['$PROCESS_GRID$'] = $table;

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function newProcess() {
        $type = htmlspecialchars($_GET['type']);
        $name = ProcessTypes::$texts[$type];

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/processes/new-process.html');

        $data = array(
            '$PAGE_TITLE$' => 'New process: <i>' . $name . '</i>',
            '$PROCESS_PANEL$' => Panels::createProcessesPanel(),
            '$PROCESS_FORM$' => $this->internalCreateProcessForm($type)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateProcessForm(int $type) {
        $form = '';
        $action = '?page=UserModule:Processes:startProcess&type=' . $type;

        switch($type) {
            case ProcessTypes::HOME_OFFICE:
                $form = HomeOffice::getForm($action);

                break;
        }

        return $form;
    }

    private function internalCreateProcessMenuGrid() {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $processes = array(
            array('name' => 'Home office', 'link' => array('page' => 'UserModule:Processes:newProcess', 'type' => ProcessTypes::HOME_OFFICE))
        );

        $cnt = count($processes);
        $ccnt = 0;
        for(;;) {
            // rows

            $row = $tb->createRow();

            $max = 5;

            if($cnt < $max) {
                $max = count($processes);
            }

            for($cols = 0; $cols < $max; $cols++) {
                $col = $tb->createCol();

                $process = $processes[$ccnt];

                $link = LinkBuilder::createAdvLink($process['link'], $process['name']);

                $text = $link;

                $col->setText($text);

                $row->addCol($col);
            }

            $tb->addRow($row);

            $ccnt++;
            
            if($ccnt == $cnt) break;
        }

        $table = $tb->build();

        return $table;
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