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
            '$ARCHIVE_PAGE_CONTROL$' => $this->internalCreateGridPageControl($id, $page, 'show' . ArchiveType::$texts[$archiveEntity->getType()] . 'Content')
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

        if($entityCount <= ($page * $app->getGridSize())) {
            $nextPageLink .= ' hidden';
        }

        $nextPageLink .= '>&gt;</a>';

        $lastPageLink .= '&grid_page=' . (ceil($entityCount / $app->getGridSize()));
        $lastPageLink .= '"';

        if($entityCount <= ($page * $app->getGridSize())) {
            $lastPageLink .= ' hidden';
        }

        $lastPageLink .= '>&gt;&gt;</a>';

        if($entityCount > $app->getGridSize()) {
            if($pageCheck * $app->getGridSize() >= $entityCount) {
                $pageControl = (1 + ($page * $app->getGridSize()));
            } else {
                $pageControl = (1 + ($pageCheck * $app->getGridSize())) . '-' . ($app->getGridSize() + ($pageCheck * $app->getGridSize()));
            }
        } else {
            $pageControl = $entityCount;
        }

        $pageControl .= ' | ' . $firstPageLink . ' ' . $previousPageLink . ' ' . $nextPageLink . ' ' . $lastPageLink;

        return $pageControl;
    }
}

?>