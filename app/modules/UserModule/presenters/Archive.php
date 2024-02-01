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
            '$ARCHIVE_PAGE_CONTROL$' => '',
            '$LINKS$' => []
        );

        if($app->actionAuthorizator->checkActionRight(UserActionRights::CREATE_ARCHIVE_DOCUMENT)) {
            $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:Archive:showNewDocumentForm', 'New archive document');
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showNewDocumentForm() {
        global $app;

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/archive/new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'New archive entity',
            '$NEW_ENTITY_FORM$' => $this->internalCreateNewDocumentForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
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

        $fb = new FormBuilder();

        $fb ->setMethod('POST')->setAction('UserModule:Archive:processNewDocumentForm')

            ->addElement($fb->createLabel()->setText('Name')->setFor('name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->setMaxLength('256')->require())

            ->addElement($fb->createLabel()->setText('Parent entity')->setFor('parent_entity'))
            ->addElement($fb->createSelect()->setName('parent_entity')->addOptionsBasedOnArray($entityArr))

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
}

?>