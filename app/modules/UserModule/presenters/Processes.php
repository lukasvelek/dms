<?php

namespace DMS\Modules\UserModule;

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
            '$PROCESS_GRID$' => $processGrid
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateStandardProcessGrid(string $filter = 'waitingForMe', int $page) {
        return '
            <script type="text/javascript">
            loadProcesses("' . $page . '", "' . $filter . '");
            </script>
            <table border="1"><img id="grid-loading" style="position: fixed; top: 50%; left: 49%;" src="img/loading.gif" width="32" height="32"></table>
        ';
    }
}

?>