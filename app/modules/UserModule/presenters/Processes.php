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
            '$PROCESS_PANEL$' => /*Panels::createProcessesPanel()*/ ''
        );

        $this->drawSubpanel = true;
        $this->subpanel = Panels::createProcessesPanel();

        $table = $this->internalCreateProcessMenuGrid();

        $data['$PROCESS_GRID$'] = $table;

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showAll() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/processes/process-grid.html');

        $filter = 'waitingForMe';

        if(isset($_GET['filter'])) {
            $filter = htmlspecialchars($_GET['filter']);
        }

        $processGrid = '<!--<script type="text/javascript" src="js/ProcessAjaxSearch.js"></script>-->';

        $app->logger->logFunction(function() use (&$processGrid, $filter) {
            $processGrid .= $this->internalCreateStandardProcessGrid($filter);
        }, __METHOD__);

        $data = array(
            '$PAGE_TITLE$' => 'Processes',
            '$PROCESS_PANEL$' => /*Panels::createProcessesPanel()*/ '',
            '$PROCESS_GRID$' => $processGrid
        );

        $this->drawSubpanel = true;
        $this->subpanel = Panels::createProcessesPanel();

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function newProcess() {
        $type = htmlspecialchars($_GET['type']);
        $name = ProcessTypes::$texts[$type];

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/processes/new-process.html');

        $data = array(
            '$PAGE_TITLE$' => 'New process: <i>' . $name . '</i>',
            '$PROCESS_PANEL$' => /*Panels::createProcessesPanel()*/ '',
            '$PROCESS_FORM$' => $this->internalCreateProcessForm($type)
        );

        $this->drawSubpanel = true;
        $this->subpanel = Panels::createProcessesPanel();

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

    private function internalCreateStandardProcessGrid(string $filter = 'waitingForMe') {
        return '
            <script type="text/javascript">
            loadProcesses("' . $filter . '");
            </script>
            <table border="1"><img id="processes-loading" style="position: fixed; top: 50%; left: 49%;" src="img/loading.gif" width="32" height="32"></table>
        ';
    }
}

?>