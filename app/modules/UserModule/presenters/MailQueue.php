<?php

namespace DMS\Modules\UserModule;

use DMS\Core\TemplateManager;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;

class MailQueue extends APresenter {
    private string $name;
    private TemplateManager $templateManager;
    private IModule $module;

    public const DRAW_TOPPANEL = true;

    public function __construct() {
        $this->name = 'MailQueue';

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

    protected function showQueue() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/mailqueue/mailqueue-grid.html');

        $mailQueueGrid = '';

        $app->logger->logFunction(function() use (&$mailQueueGrid) {
            $mailQueueGrid = $this->internalCreateMailQueueGrid();
        }, __METHOD__);

        $data = array(
            '$PAGE_TITLE$' => 'Mail queue',
            '$MAILQUEUE_GRID$' => $mailQueueGrid
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateMailQueueGrid() {
        return '
            <script type="text/javascript">
                loadMailQueue();
            </script>
            <table border="1"><img id="mailqueue-loading" style="position: fixed; top: 50%; left: 49%;" src="img/loading.gif" width="32" height="32"></table>
        ';
    }
}

?>