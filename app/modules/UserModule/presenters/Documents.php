<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\DocumentStatus;
use DMS\Core\TemplateManager;
use DMS\Helpers\ArrayStringHelper;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;
use DMS\Modules\IPresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

class Documents extends APresenter {
    private $name;

    private $templateManager;

    private $module;

    public const DRAW_TOPPANEL = true;

    public function __construct() {
        $this->name = 'Documents';

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

    protected function showAll() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/document-grid.html');

        $data = array(
            '$PAGE_TITLE$' => 'Documents'
        );

        $table = $this->internalCreateStandardDocumentGrid();

        $data['$DOCUMENT_GRID$'] = $table;
        
        $bulkActions = $this->internalPrepareDocumentBulkActions();

        $data['$BULK_ACTION_CONTROLLER$'] = $bulkActions;
        $data['$FORM_ACTION$'] = '?page=UserModule:Documents:performBulkAction';
        $data['$NEW_DOCUMENT_LINK$'] = LinkBuilder::createLink('UserModule:NewDocument:showForm', 'New document');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalPrepareDocumentBulkActions() {
        $bulkActions = array(
            '-' => '-',
            'delete' => 'Delete'
        );

        $select = '<select name="action">';

        foreach($bulkActions as $bAction => $bName) {
            $select .= '<option value="' . $bAction . '">' . $bName . '</option>';
        }

        $select .= '</select>';

        $submit = '<input type="submit" value="Perform">';

        $code = $select . $submit;

        return $code;
    }

    private function internalCreateStandardDocumentGrid() {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $headers = array(
            'Actions',
            'Name',
            'Author',
            'Status'
        );

        $headerRow = null;

        $documents = $app->documentModel->getStandardDocuments();

        if(empty($documents)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($documents as $document) {
                $actionLinks = array(
                    '<input type="checkbox" name="select" value="' . $document->getId() . '">',
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showInfo', 'id' => $document->getId()), 'Information'),
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showEdit', 'id' => $document->getId()), 'Edit')
                );

                if(is_null($headerRow)) {
                    $row = $tb->createRow();

                    foreach($headers as $header) {
                        $col = $tb->createCol()->setText($header)
                                               ->setBold();

                        if($header == 'Actions') {
                            $col->setColspan(count($actionLinks));
                        }

                        $row->addCol($col);
                    }

                    $headerRow = $row;

                    $tb->addRow($row);
                }

                $docuRow = $tb->createRow();

                foreach($actionLinks as $actionLink) {
                    $docuRow->addCol($tb->createCol()->setText($actionLink));
                }

                $docuRow->addCol($tb->createCol()->setText($document->getName()))
                        ->addCol($tb->createCol()->setText($app->userModel->getUserById($document->getIdAuthor())->getFullname()))
                        ->addCol($tb->createCol()->setText(DocumentStatus::$texts[$document->getStatus()]))
                ;

                $tb->addRow($docuRow);
            }
        }

        return $tb->build();
    }

    protected function performBulkAction() {
        global $app;

        if(!isset($_POST['select'])) {
            $app->redirect('UserModule:Documents:showAll');
        }

        $ids = htmlspecialchars($_POST['select']);
        $action = htmlspecialchars($_POST['action']);

        if($action == '-') {
            $app->redirect('UserModule:Documents:showAll');
        }


    }
}

?>