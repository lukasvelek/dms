<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\ArchiveType;
use DMS\Constants\UserActionRights;
use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\LinkBuilder;

class Archive extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('Archive');

        $this->getActionNamesFromClass($this);
    }

    protected function showDocuments() {
        global $app;

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/archive/archive-grid.html');
        
        $page = 1;

        if(isset($_GET['grid_page'])) {
            $page = (int)(htmlspecialchars($_GET['grid_page']));
        }

        $grid = '';

        $app->logger->logFunction(function() use (&$grid, $page) {
            $grid = $this->internalCreateDocumentGrid($page);
        });

        $data = array(
            '$PAGE_TITLE$' => 'Archive documents',
            '$BULK_ACTION_CONTROLLER$' => '',
            '$SEARCH_FIELD$' => '',
            '$ARCHIVE_GRID$' => $grid,
            '$ARCHIVE_PAGE_CONTROL$' => $this->internalCreateGridPageControl($page, 'showDocuments'),
            '$LINKS$' => []
        );

        if($app->actionAuthorizator->checkActionRight(UserActionRights::CREATE_ARCHIVE_DOCUMENT)) {
            $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:Archive:showNewDocumentForm', 'New archive document');
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showNewDocumentForm() {
        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/archive/new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'New archive document',
            '$NEW_ENTITY_FORM$' => $this->internalCreateNewDocumentForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function processNewDocumentForm() {
        global $app;

        $app->flashMessageIfNotIsset(['name'], true, ['page' => 'UserModule:Archive:showNewDocumentForm']);

        $name = htmlspecialchars($_POST['name']);

        $idRibbon = '';
        if(isset($_GET['id_ribbon'])) {
            $idRibbon = htmlspecialchars($_GET['id_ribbon']);
        }

        $data = [
            'name' => $name,
            'type' => ArchiveType::DOCUMENT
        ];

        $app->archiveModel->insertNewArchiveEntity($data);

        $app->flashMessage('Created new archive document', 'success');
        if($idRibbon == '') {
            $app->redirect('UserModule:Archive:showDocuments');
        } else {
            $app->redirect('UserModule:Archive:showDocuments', ['id_ribbon' => $idRibbon]);
        }
    }

    private function internalCreateNewDocumentForm() {
        global $app;

        $parentEntities = $app->archiveModel->getAllArchiveEntitiesByType(ArchiveType::BOX);

        $entityArr = [];
        $entityArr[] = array(
            'value' => 'null',
            'text' => '-'
        );

        foreach($parentEntities as $entity) {
            $entityArr[] = array(
                'value' => $entity->getId(),
                'text' => $entity->getName()
            );
        }

        $idRibbon = '';
        if(isset($_GET['id_ribbon'])) {
            $idRibbon = '&id_ribbon=' . htmlspecialchars($_GET['id_ribbon']);
        }

        $fb = new FormBuilder();

        $fb ->setMethod('POST')->setAction('?page=UserModule:Archive:processNewDocumentForm' . $idRibbon)

            ->addElement($fb->createLabel()->setText('Name')->setFor('name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->setMaxLength('256')->require())

            ->addElement($fb->createSubmit('Create'))
        ;

        return $fb->build();
    }

    private function internalCreateDocumentGrid(int $page) {
        $code = '<script type="text/javascript">';
        $code .= 'loadArchiveDocuments("' . $page . '");';
        $code .= '</script>';
        $code .= '<table border="1"><img id="documents-loading" style="position: fixed; top: 50%; left: 49%;" src="img/loading.gif" width="32" height="32"></table>';

        return $code;
    }

    private function internalCreateGridPageControl(int $page, string $action) {
        global $app;

        $entityCount = 0;

        switch($action) {
            case 'showDocuments':
                $entityCount = $app->archiveModel->getDocumentCount();
                break;
        }

        $pageControl = '';
        $firstPageLink = '<a class="general-link" title="First page" href="?page=UserModule:Archive:' . $action;
        $previousPageLink = '<a class="general-link" title="Previous page" href="?page=UserModule:Archive:' . $action;
        $nextPageLink = '<a class="general-link" title="Next page" href="?page=UserModule:Archive:' . $action;
        $lastPageLink = '<a class="general-link" title="Last page" href="?page=UserModule:Archive:' . $action;

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