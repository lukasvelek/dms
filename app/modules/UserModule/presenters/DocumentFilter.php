<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\CacheCategories;
use DMS\Core\CacheManager;
use DMS\Modules\APresenter;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

class DocumentFilter extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('DocumentFilter', 'Document filters');

        $this->getActionNamesFromClass($this);
    }

    protected function showFilters() {
        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/documents/document-filter-grid.html');

        $data = array(
            '$PAGE_TITLE$' => 'Document filters',
            '$LINKS$' => array(
                LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumetnFilter:showNewFilterForm'), 'New filter')
            ),
            '$FILTER_GRID$' => $this->internalCreateStandardFilterGrid()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateStandardFilterGrid() {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $ucm = CacheManager::getTemporaryObject(CacheCategories::USERS);

        $headers = array(
            'Actions',
            'Name',
            'Description',
            'Author'
        );

        $headerRow = null;

        $filters = $app->filterModel->getAllDocumentFilters();

        if(empty($filters)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($filters as $filter) {
                $actionLinks = array(
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:showSingleFilter', 'id_filter' => $filter->getId()), 'Open')
                );

                if(is_null($headerRow)) {
                    $row = $tb->createRow();

                    foreach($headers as $header) {
                        $col = $tb->createCol()->setText($header)->setBold();

                        if($header == 'Actions') {
                            $col->setColspan(count($actionLinks));
                        }

                        $row->addCol($col);
                    }

                    $headerRow = $row;

                    $tb->addRow($row);
                }

                $filterRow = $tb->createRow();

                foreach($actionLinks as $actionLink) {
                    $filterRow->addCol($tb->createCol()->setText($actionLink));
                }

                $authorName = 'System';

                if(!is_null($filter->getIdAuthor())) {
                    $cacheData = $ucm->loadUserByIdFromCache($filter->getIdAuthor());
                    $author = null;

                    if(!is_null($cacheData)) {
                        $author = $cacheData;
                    } else {
                        $author = $app->userModel->getUserById($filter->getIdAuthor());
                    }

                    $authorName = $author->getFullname();
                }

                $filterData = array(
                    $filter->getName(),
                    $filter->getDescription() ?? '-',
                    $authorName
                );

                foreach($filterData as $fd) {
                    $filterRow->addCol($tb->createCol()->setText($fd));
                }

                $tb->addRow($filterRow);
            }
        }

        return $tb->build();
    }
}

?>