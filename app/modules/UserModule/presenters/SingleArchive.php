<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\ArchiveType;
use DMS\Core\AppConfiguration;
use DMS\Modules\APresenter;
use DMS\UI\LinkBuilder;

class SingleArchive extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('SingleArchive', 'Archive');

        $this->getActionNamesFromClass($this);
    }

    protected function showContent() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'type'], true, ['page' => 'UserModule:Archive:showDocuments']);

        $id = htmlspecialchars($_GET['id']);
        $type = htmlspecialchars($_GET['type']);
        
        $backLink = '';
        $archiveEntity = null;
        switch($type) {
            case ArchiveType::DOCUMENT:
                $archiveEntity = $app->archiveModel->getDocumentById($id);
                $backLink = 'UserModule:Archive:showDocuments';
                break;
            
            case ArchiveType::BOX:
                $archiveEntity = $app->archiveModel->getBoxById($id);
                $backLink = 'UserModule:Archive:showBoxes';
                break;

            case ArchiveType::ARCHIVE:
                $archiveEntity = $app->archiveModel->getArchiveById($id);
                $backLink = 'UserModule:Archive:showArchives';
                break;
        }

        $page = 1;
        if(isset($_GET['grid_page'])) {
            $page = (int)(htmlspecialchars($_GET['grid_page']));
        }

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/archive/archive-content-grid.html');

        $data = [
            '$PAGE_TITLE$' => ArchiveType::$texts[$archiveEntity->getType()] . ' content',
            '$LINKS$' => [],
            '$CONTENT_GRID$' => $this->internalCreateContentGrid($id, $page, $type),
            '$ARCHIVE_PAGE_CONTROL$' => $this->internalCreateGridPageControl($id, $page, 'show' . ArchiveType::$texts[$archiveEntity->getType()] . 'Content')
        ];

        $data['$LINKS$'][] = LinkBuilder::createLink($backLink, '<-');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateContentGrid(int $id, int $page, int $type) {
        $code = '<script type="text/javascript">';
        $code .= 'loadArchiveEntityContent("' . $id . '", "' . $page . '", "' . $type . '");';
        $code .= '</script>';
        $code .= '<table border="1"><img id="documents-loading" style="position: fixed; top: 50%; left: 49%;" src="img/loading.gif" width="32" height="32"></table>';

        return $code;
    }

    private function internalCreateGridPageControl(int $id, int $page, string $action) {
        global $app;

        $entityCount = 0;
        $link = 'showContent';

        switch($action) {
            case 'showDocumentContent':
                $entityCount = $app->documentModel->getDocumentCountInArchiveDocument($id);
                $link .= '&id=' . $id;
                break;
        }

        $pageControl = '';
        $firstPageLink = '<a class="general-link" title="First page" href="?page=UserModule:SingleArchive:' . $link;
        $previousPageLink = '<a class="general-link" title="Previous page" href="?page=UserModule:SingleArchive:' . $link;
        $nextPageLink = '<a class="general-link" title="Next page" href="?page=UserModule:SingleArchive:' . $link;
        $lastPageLink = '<a class="general-link" title="Last page" href="?page=UserModule:SingleArchive:' . $link;

        $pageCheck = $page - 1;

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

        if($entityCount <= ($page * AppConfiguration::getGridSize())) {
            $nextPageLink .= ' hidden';
        }

        $nextPageLink .= '>&gt;</a>';

        $lastPageLink .= '&grid_page=' . (ceil($entityCount / AppConfiguration::getGridSize()));
        $lastPageLink .= '"';

        if($entityCount <= ($page * AppConfiguration::getGridSize())) {
            $lastPageLink .= ' hidden';
        }

        $lastPageLink .= '>&gt;&gt;</a>';

        if($entityCount > AppConfiguration::getGridSize()) {
            if($pageCheck * AppConfiguration::getGridSize() >= $entityCount) {
                $pageControl = (1 + ($page * AppConfiguration::getGridSize()));
            } else {
                $pageControl = (1 + ($pageCheck * AppConfiguration::getGridSize())) . '-' . (AppConfiguration::getGridSize() + ($pageCheck * AppConfiguration::getGridSize()));
            }
        } else {
            $pageControl = $entityCount;
        }

        $pageControl .= ' | ' . $firstPageLink . ' ' . $previousPageLink . ' ' . $nextPageLink . ' ' . $lastPageLink;

        return $pageControl;
    }
}

?>