<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\ArchiveStatus;
use DMS\Constants\ArchiveType;
use DMS\Constants\DocumentStatus;
use DMS\Constants\Metadata\ArchiveMetadata;
use DMS\Constants\ProcessTypes;
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

    protected function performBulkAction() {
        global $app;

        $app->flashMessageIfNotIsset(['select', 'action']);

        $ids = $this->get('select', false);
        $action = $this->get('action');

        if($action == '-') {
            $app->redirect('showDocuments');
        }

        if(method_exists($this, '_' . $action)) {
            return $this->{'_' . $action}($ids);
        } else {
            die('Method does not exist!');
        }
    }

    protected function showArchives() {
        global $app;

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/archive/archive-grid.html');
        
        $page = 1;

        if(isset($_GET['grid_page'])) {
            $page = (int)($this->get('grid_page'));
        }

        $grid = '';

        $app->logger->logFunction(function() use (&$grid, $page) {
            $grid = $this->internalCreateArchiveGrid($page);
        });

        $data = array(
            '$PAGE_TITLE$' => 'Archive archives',
            '$BULK_ACTION_CONTROLLER$' => '',
            '$SEARCH_FIELD$' => '',
            '$ARCHIVE_GRID$' => $grid,
            '$LINKS$' => []
        );

        if($app->actionAuthorizator->checkActionRight(UserActionRights::CREATE_ARCHIVE_DOCUMENT)) {
            $data['$LINKS$'][] = LinkBuilder::createLink('showNewArchiveForm', 'New archive');
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showBoxes() {
        global $app;

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/archive/archive-grid.html');
        
        $page = 1;

        if(isset($_GET['grid_page'])) {
            $page = (int)($this->get('grid_page'));
        }

        $grid = '';

        $app->logger->logFunction(function() use (&$grid, $page) {
            $grid = $this->internalCreateBoxGrid($page);
        });

        $data = array(
            '$PAGE_TITLE$' => 'Archive boxes',
            '$BULK_ACTION_CONTROLLER$' => '',
            '$SEARCH_FIELD$' => '',
            '$ARCHIVE_GRID$' => $grid,
            '$LINKS$' => []
        );

        if($app->actionAuthorizator->checkActionRight(UserActionRights::CREATE_ARCHIVE_DOCUMENT)) {
            $data['$LINKS$'][] = LinkBuilder::createLink('showNewBoxForm', 'New box');
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showDocuments() {
        global $app;

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/archive/archive-grid.html');
        
        $page = 1;

        if(isset($_GET['grid_page'])) {
            $page = (int)($this->get('grid_page'));
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
            '$LINKS$' => []
        );

        if($app->actionAuthorizator->checkActionRight(UserActionRights::CREATE_ARCHIVE_DOCUMENT)) {
            $data['$LINKS$'][] = LinkBuilder::createLink('showNewDocumentForm', 'New document');
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

    protected function showNewBoxForm() {
        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/archive/new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'New archive box',
            '$NEW_ENTITY_FORM$' => $this->internalCreateNewBoxForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showNewArchiveForm() {
        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/archive/new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'New archive',
            '$NEW_ENTITY_FORM$' => $this->internalCreateNewArchiveForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function processNewDocumentForm() {
        global $app;

        $app->flashMessageIfNotIsset(['name'], true, ['page' => 'showNewDocumentForm']);

        $name = $this->post('name');

        $idRibbon = '';
        if(isset($_GET['id_ribbon'])) {
            $idRibbon = $this->get('id_ribbon');
        }

        $data = [
            ArchiveMetadata::NAME => $name
        ];

        $app->archiveModel->insertNewDocument($data);

        $app->flashMessage('Created new archive document', 'success');
        if($idRibbon == '') {
            $app->redirect('showDocuments');
        } else {
            $app->redirect('showDocuments', ['id_ribbon' => $idRibbon]);
        }
    }

    protected function processNewBoxForm() {
        global $app;

        $app->flashMessageIfNotIsset(['name'], true, ['page' => 'showNewBoxForm']);

        $name = $this->post('name');

        $idRibbon = '';
        if(isset($_GET['id_ribbon'])) {
            $idRibbon = $this->get('id_ribbon');
        }

        $data = [
            ArchiveMetadata::NAME => $name
        ];

        $app->archiveModel->insertNewBox($data);

        $app->flashMessage('Created new archive box', 'success');
        if($idRibbon == '') {
            $app->redirect('showBoxes');
        } else {
            $app->redirect('showBoxes', ['id_ribbon' => $idRibbon]);
        }
    }

    protected function processNewArchiveForm() {
        global $app;

        $app->flashMessageIfNotIsset(['name'], true, ['page' => 'showNewArchiveForm']);

        $name = $this->post('name');

        $idRibbon = '';
        if(isset($_GET['id_ribbon'])) {
            $idRibbon = $this->get('id_ribbon');
        }

        $data = [
            ArchiveMetadata::NAME => $name
        ];

        $app->archiveModel->insertNewArchive($data);

        $app->flashMessage('Created new archive', 'success');
        if($idRibbon == '') {
            $app->redirect('showArchives');
        } else {
            $app->redirect('showArchives', ['id_ribbon' => $idRibbon]);
        }
    }

    private function internalCreateNewDocumentForm() {
        $idRibbon = '';
        if(isset($_GET['id_ribbon'])) {
            $idRibbon = '&id_ribbon=' . $this->get('id_ribbon');
        }

        $fb = new FormBuilder();

        $fb ->setMethod('POST')->setAction('?page=UserModule:Archive:processNewDocumentForm' . $idRibbon)

            ->addElement($fb->createLabel()->setText('Name')->setFor('name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->setMaxLength('256')->require())

            ->addElement($fb->createSubmit('Create'))
        ;

        return $fb->build();
    }

    private function internalCreateNewBoxForm() {
        $idRibbon = '';
        if(isset($_GET['id_ribbon'])) {
            $idRibbon = '&id_ribbon=' . $this->get('id_ribbon');
        }

        $fb = new FormBuilder();

        $fb ->setMethod('POST')->setAction('?page=UserModule:Archive:processNewBoxForm' . $idRibbon)

            ->addElement($fb->createLabel()->setText('Name')->setFor('name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->setMaxLength('256')->require())

            ->addElement($fb->createSubmit('Create'))
        ;

        return $fb->build();
    }

    private function internalCreateNewArchiveForm() {
        $idRibbon = '';
        if(isset($_GET['id_ribbon'])) {
            $idRibbon = '&id_ribbon=' . $this->get('id_ribbon');
        }

        $fb = new FormBuilder();

        $fb ->setMethod('POST')->setAction('?page=UserModule:Archive:processNewArchiveForm' . $idRibbon)

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
        $code .= '<div id="grid-loading"><img src="img/loading.gif" width="32" height="32"></div><table border="1"></table>';

        return $code;
    }

    private function internalCreateBoxGrid(int $page) {
        $code = '<script type="text/javascript">';
        $code .= 'loadArchiveBoxes("' . $page . '");';
        $code .= '</script>';
        $code .= '<div id="grid-loading"><img src="img/loading.gif" width="32" height="32"></div><table border="1"></table>';

        return $code;
    }

    private function internalCreateArchiveGrid(int $page) {
        $code = '<script type="text/javascript">';
        $code .= 'loadArchiveArchives("' . $page . '");';
        $code .= '</script>';
        $code .= '<div id="grid-loading"><img src="img/loading.gif" width="32" height="32"></div><table border="1"></table>';

        return $code;
    }

    private function internalCreateMoveDocumentToBoxForm(array $ids) {
        global $app;

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/archive/new-entity-form.html');

        $url = '?page=UserModule:Archive:performBulkAction&action=move_document_to_box&';
        $i = 0;
        foreach($ids as $id) {
            if(($i + 1) == count($ids)) {
                $url .= 'select[]=' . $id;
            } else {
                $url .= 'select[]=' . $id . '&';
            }
        }

        $boxes = $app->archiveModel->getAllAvailableArchiveEntitiesByType(ArchiveType::BOX);
        $boxesArr = [];
        foreach($boxes as $box) {
            $boxesArr[] = [
                'value' => $box->getId(),
                'text' => $box->getName()
            ];
        }

        $fb = new FormBuilder();

        $fb ->setMethod('POST')->setAction($url)

            ->addElement($fb->createLabel()->setText('Box')->setFor('box'))
            ->addElement($fb->createSelect()->setName('box')->addOptionsBasedOnArray($boxesArr))

            ->addElement($fb->createSubmit('Move'))
        ;

        $form = $fb->build();

        $data = [
            '$PAGE_TITLE$' => 'Move document to box',
            '$NEW_ENTITY_FORM$' => $form
        ];

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateMoveBoxToArchiveForm(array $ids) {
        global $app;

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/archive/new-entity-form.html');

        $url = '?page=UserModule:Archive:performBulkAction&action=move_box_to_archive&';
        $i = 0;
        foreach($ids as $id) {
            if(($i + 1) == count($ids)) {
                $url .= 'select[]=' . $id;
            } else {
                $url .= 'select[]=' . $id . '&';
            }
        }

        $archives = $app->archiveModel->getAllAvailableArchiveEntitiesByType(ArchiveType::ARCHIVE);
        $archivesArr = [];
        foreach($archives as $archive) {
            $archivesArr[] = [
                'value' => $archive->getId(),
                'text' => $archive->getName()
            ];
        }

        $fb = new FormBuilder();

        $fb ->setMethod('POST')->setAction($url)

            ->addElement($fb->createLabel()->setText('Archive')->setFor('archive'))
            ->addElement($fb->createSelect()->setName('archive')->addOptionsBasedOnArray($archivesArr))

            ->addElement($fb->createSubmit('Move'))
        ;

        $form = $fb->build();

        $data = [
            '$PAGE_TITLE$' => 'Move box to archive',
            '$NEW_ENTITY_FORM$' => $form
        ];

        $this->templateManager->fill($data, $template);

        return $template;
    }

    /**
     * BULK ACTIONS
     */
    private function _move_document_to_box(array $ids) {
        global $app;

        if(isset($_POST['box'])) {
            $box = $this->post('box');

            foreach($ids as $id) {
                $app->archiveModel->moveDocumentToBox($id, $box);
            }

            $app->flashMessage('Moved documents to the box', 'success');
            $app->redirect('showDocuments');
        } else {
            return $this->internalCreateMoveDocumentToBoxForm($ids);
        }
    }

    private function _move_document_from_box(array $ids) {
        global $app;

        foreach($ids as $id) {
            $app->archiveModel->moveDocumentFromBox($id);
        }

        $app->flashMessage('Removed documents from the box', 'success');
        $app->redirect('showDocuments');
    }

    private function _move_box_to_archive(array $ids) {
        global $app;

        if(isset($_POST['archive'])) {
            $archive = $this->post('archive');

            foreach($ids as $id) {
                $app->archiveModel->moveBoxToArchive($id, $archive);
            }

            $app->flashMessage('Moved boxes to archive', 'success');
            $app->redirect('showBoxes');
        } else {
            return $this->internalCreateMoveBoxToArchiveForm($ids);
        }
    }

    private function _move_box_from_archive(array $ids) {
        global $app;
        
        foreach($ids as $id) {
            $app->archiveModel->moveBoxFromArchive($id);
        }

        $app->flashMessage('Removed boxes from archive', 'success');
        $app->redirect('showBoxes');
    }

    private function _close_archive(array $ids) {
        global $app;

        $fileCount = 0;
        $documentCount = 0;
        $boxCount = 0;
        $archiveCount = count($ids);

        $totalCount = 0;

        foreach($ids as $id) {
            // archives
            $app->archiveModel->closeArchive($id);

            $boxes = $app->archiveModel->getBoxesForIdParent($id);
            $boxCount = $boxCount + count($boxes);

            foreach($boxes as $box) {
                $app->archiveModel->updateBox($box->getId(), [ArchiveMetadata::STATUS => ArchiveStatus::FINISHED]);

                $documents = $app->archiveModel->getDocumentsForIdParent($box->getId());
                $documentCount = $documentCount + count($documents);
                
                foreach($documents as $document) {
                    $app->archiveModel->updateDocument($document->getId(), [ArchiveMetadata::STATUS => ArchiveStatus::FINISHED]);

                    $files = $app->documentModel->getDocumentForIdArchiveEntity($document->getId());
                    $fileCount = $fileCount + count($files);

                    foreach($files as $file) {
                        $app->documentModel->updateDocument($file->getId(), [ArchiveMetadata::STATUS => DocumentStatus::FINISHED]);
                    }
                }
            }
        }

        $totalCount = $fileCount + $documentCount + $boxCount + $archiveCount;

        $app->flashMessage('Finished ' . $totalCount . ' entities');
        $app->redirect('showArchives');
    }

    private function _suggest_for_shredding(array $ids) {
        global $app;

        foreach($ids as $id) {
            $app->processComponent->startProcess(ProcessTypes::SHREDDING, $id, $app->user->getId(), true);

            $app->archiveModel->updateArchive($id, [ArchiveMetadata::STATUS => ArchiveStatus::SUGGESTED_FOR_SHREDDING]);
        }

        $app->flashMessage('Archives suggested for shredding', 'success');
        $app->redirect('showArchives');
    }
}

?>