<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\ArchiveType;
use DMS\Modules\APresenter;
use DMS\UI\LinkBuilder;

class SingleArchivePresenter extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('SingleArchive', 'Archive');

        $this->getActionNamesFromClass($this);
    }

    protected function showContent() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'type'], true, ['page' => 'Archive:showDocuments']);

        $id = $this->get('id');
        $type = $this->get('type');
        
        $backLink = '';
        $archiveEntity = null;
        switch($type) {
            case ArchiveType::DOCUMENT:
                $archiveEntity = $app->archiveModel->getDocumentById($id);
                $backLink = 'Archive:showDocuments';
                break;
            
            case ArchiveType::BOX:
                $archiveEntity = $app->archiveModel->getBoxById($id);
                $backLink = 'Archive:showBoxes';
                break;

            case ArchiveType::ARCHIVE:
                $archiveEntity = $app->archiveModel->getArchiveById($id);
                $backLink = 'Archive:showArchives';
                break;
        }

        $page = 1;
        if(isset($_GET['grid_page'])) {
            $page = (int)($this->get('grid_page'));
        }

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/archive/archive-content-grid.html');

        $data = [
            '$PAGE_TITLE$' => ArchiveType::$texts[$archiveEntity->getType()] . ' content',
            '$LINKS$' => [],
            '$CONTENT_GRID$' => $this->internalCreateContentGrid($id, $page, $type)
        ];

        $data['$LINKS$'][] = LinkBuilder::createLink($backLink, '&larr;');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateContentGrid(int $id, int $page, int $type) {
        $code = '<script type="text/javascript">';
        $code .= 'loadArchiveEntityContent("' . $id . '", "' . $page . '", "' . $type . '");';
        $code .= '</script>';
        $code .= '<div id="grid-loading"><img src="img/loading.gif" width="32" height="32"></div><table border="1"></table>';

        return $code;
    }
}

?>