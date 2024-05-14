<?php

namespace DMS\Modules\UserModule;

use DMS\Modules\APresenter;

class MailQueuePresenter extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('MailQueue', 'Mail queue');

        $this->getActionNamesFromClass($this);
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