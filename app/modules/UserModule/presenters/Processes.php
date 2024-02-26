<?php

namespace DMS\Modules\UserModule;

use DMS\Components\Process\HomeOffice;
use DMS\Constants\ProcessTypes;
use DMS\Core\AppConfiguration;
use DMS\Modules\APresenter;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

class Processes extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('Processes');

        $this->getActionNamesFromClass($this);
    }

    protected function showMenu() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/processes/process-grid.html');

        $data = array(
            '$PAGE_TITLE$' => 'Process menu',
            '$PROCESS_PANEL$' => ''
        );

        $table = $this->internalCreateProcessMenuGrid();

        $data['$PROCESS_GRID$'] = $table;

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showAll() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/processes/process-grid.html');

        $filter = 'waitingForMe';
        $page = 1;

        if(isset($_GET['filter'])) {
            $filter = $this->get('filter');
        }

        if(isset($_GET['grid_page'])) {
            $page = (int)($this->get('grid_page'));
        }

        $processGrid = '<!--<script type="text/javascript" src="js/ProcessAjaxSearch.js"></script>-->';

        $app->logger->logFunction(function() use (&$processGrid, $filter, $page) {
            $processGrid .= $this->internalCreateStandardProcessGrid($filter, $page);
        }, __METHOD__);

        $data = array(
            '$PAGE_TITLE$' => 'Processes',
            '$PROCESS_PANEL$' => '',
            '$PROCESS_GRID$' => $processGrid,
            '$PROCESS_PAGE_CONTROL$' => $this->internalCreateGridPageControl($page, $filter)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function newProcess() {
        global $app;

        $app->flashMessageIfNotIsset(['type']);

        $type = $this->get('type');
        $name = ProcessTypes::$texts[$type];

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/processes/new-process.html');

        $data = array(
            '$PAGE_TITLE$' => 'New process: <i>' . $name . '</i>',
            '$PROCESS_PANEL$' => '',
            '$PROCESS_FORM$' => $this->internalCreateProcessForm($type)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    /**
     * Currently not in use
     */
    private function internalCreateProcessForm(int $type) {
        /*$form = '';
        $action = '?page=UserModule:Processes:startProcess&type=' . $type;

        switch($type) {
            case ProcessTypes::HOME_OFFICE:
                $form = HomeOffice::getForm($action);

                break;
        }

        return $form;*/

        global $app;

        $app->redirect('showAll');
    }

    /**
     * Currently not in use
     */
    private function internalCreateProcessMenuGrid() {
        $tb = TableBuilder::getTemporaryObject();

        $processes = array(
            array('name' => 'Home office', 'link' => array('page' => 'newProcess', 'type' => ProcessTypes::HOME_OFFICE))
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

    private function internalCreateStandardProcessGrid(string $filter = 'waitingForMe', int $page) {
        return '
            <script type="text/javascript">
            loadProcesses("' . $page . '", "' . $filter . '");
            </script>
            <table border="1"><img id="processes-loading" style="position: fixed; top: 50%; left: 49%;" src="img/loading.gif" width="32" height="32"></table>
        ';
    }

    private function internalCreateGridPageControl(int $page, string $filter) {
        global $app;

        $processCount = 0;

        switch($filter) {
            case 'waitingForMe':
                $processCount = $app->processModel->getCountProcessesWaitingForUser($app->user->getId());
                break;

            case 'startedByMe':
                $processCount = $app->processModel->getCountProcessesStartedByUser($app->user->getId());
                break;

            case 'finished':
                $processCount = $app->processModel->getCountFinishedProcesses();
                break;
        }

        $add = function(string $key, string $value, string &$link) {
            $link .= $key . '=' . $value;
        };

        $processPageControl = '';
        $firstPageLink = '<a class="general-link" title="First page" href="?page=UserModule:Processes:showAll';
        $previousPageLink = '<a class="general-link" title="Previous page" href="?page=UserModule:Processes:showAll';
        $nextPageLink = '<a class="general-link" title="Next page" href="?page=UserModule:Processes:showAll';
        $lastPageLink = '<a class="general-link" title="Last page" href="?page=UserModule:Processes:showAll';

        if($filter != 'waitingForMe') {
            $add('filter', $filter, $firstPageLink);
            $add('filter', $filter, $previousPageLink);
            $add('filter', $filter, $nextPageLink);
            $add('filter', $filter, $lastPageLink);
        }

        $firstPageLink .= '"';

        if($page == 1) {
            $firstPageLink .= ' hidden';
        }

        $firstPageLink .= '>&lt;&lt;</a>';

        if($page > 2) {
            $previousPageLink .= '&grid_page=' . ($page - 1);
        }
        $previousPageLink .= '"';

        if($page == 1) {
            $previousPageLink .= ' hidden';
        }

        $previousPageLink .= '>&lt;</a>';

        $nextPageLink .= '&grid_page=' . ($page + 1);
        $nextPageLink .= '"';

        if($processCount <= ($page * AppConfiguration::getGridSize())) {
            $nextPageLink .= ' hidden';
        }

        $nextPageLink .= '>&gt;</a>';

        $lastPageLink .= '&grid_page=' . (ceil($processCount / AppConfiguration::getGridSize()));
        $lastPageLink .= '"';

        if($processCount <= ($page * AppConfiguration::getGridSize())) {
            $lastPageLink .= ' hidden';
        }

        $lastPageLink .= '>&gt;&gt;</a>';

        if($processCount > AppConfiguration::getGridSize()) {
            if(($page * AppConfiguration::getGridSize()) >= $processCount) {
                $processPageControl = $processCount;
            } else {
                $processPageControl = ($page * AppConfiguration::getGridSize()) . '+';
            }
        } else {
            $processPageControl = $processCount;
        }

        $processPageControl .= ' | ' . $firstPageLink . ' ' . $previousPageLink . ' ' . $nextPageLink . ' ' . $lastPageLink;

        return $processPageControl;
    }
}

?>