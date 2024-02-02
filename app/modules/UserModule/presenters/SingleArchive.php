<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\ArchiveType;
use DMS\Modules\APresenter;

class SingleArchive extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('SingleArchive', 'Archive');

        $this->getActionNamesFromClass($this);
    }

    protected function showContent() {
        global $app;

        $app->flashMessageIfNotIsset(['id'], true, ['page' => 'UserModule:Archive:showDocuments']);

        $id = htmlspecialchars($_GET['id']);
        $archiveEntity = $app->archiveModel->getArchiveEntityById($id);

        $page = 1;
        if(isset($_GET['grid_page'])) {
            $page = (int)(htmlspecialchars($_GET['grid_page']));
        }

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/archive/archive-content-grid.html');

        $data = [
            '$PAGE_TITLE$' => ArchiveType::$texts[$archiveEntity->getType()] . ' content',
            '$LINKS$' => [],
            '$CONTENT_GRID$' => $this->internalCreateContentGrid($id, $page),
            '$ARCHIVE_PAGE_CONTROL$' => ''
        ];

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateContentGrid(int $id, int $page) {
        $code = '<script type="text/javascript">';
        $code .= 'loadArchiveEntityContent("' . $id . '", "' . $page . '");';
        $code .= '</script>';
        $code .= '<table border="1"><img id="documents-loading" style="position: fixed; top: 50%; left: 49%;" src="img/loading.gif" width="32" height="32"></table>';

        return $code;
    }
}

?>