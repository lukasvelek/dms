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
        $data['$NEW_DOCUMENT_LINK$'] = LinkBuilder::createLink('UserModule:Documents:showNewForm', 'New document');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalPrepareDocumentBulkActions() {
        global $app;

        $bulkActions = array(
            '-' => '-'
        );

        $dbBulkActions = $app->userRightModel->getAllBulkActionRightsForIdUser($app->user->getId());

        foreach($dbBulkActions as $dba) {
            $name = '';

            if(!$app->bulkActionAuthorizator->checkBulkActionRight($dba)) {
                continue;
            }

            switch($dba) {
                case 'delete_documents':
                    $name = 'Delete documents';
                    break;
            }

            $bulkActions[$dba] = $name;
        }

        $code = [];
        $code[] = '<select name="action">';

        foreach($bulkActions as $bAction => $bName) {
            $code[] = '<option value="' . $bAction . '">' . $bName . '</option>';
        }

        $code[] = '</select>';
        $code[] = '<input type="submit" value="Perform">';

        $singleLineCode = ArrayStringHelper::createUnindexedStringFromUnindexedArray($code);

        return $singleLineCode;
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

        if(method_exists($this, '_' . $action)) {
            //echo('hello');
            $this->{'_' . $action}();
        } else {
            die('Method does not exist!');
        }
    }

    protected function showNewForm() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/new-document-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'New document'
        );

        $users = $app->userModel->getAllUsers();

        $managers = array(
            array(
                'value' => $app->user->getId(),
                'text' => '&lt;&lt;Me&gt;&gt;',
                'selected'
            )
        );

        foreach($users as $user) {
            $managers[] = array(
                'value' => $user->getId(),
                'text' => $user->getFullname()
            );
        }

        $statuses = [];
        foreach(DocumentStatus::$texts as $k => $v) {
            $statuses[] = array(
                'value' => $k,
                'text' => $v
            );
        }

        $dbGroups = $app->groupModel->getAllGroups();

        $groups = [];
        foreach($dbGroups as $dbg) {
            $groups[] = array(
                'value' => $dbg->getId(),
                'text' => $dbg->getName()
            );
        }

        $fb = FormBuilder::getTemporaryObject();

        $fb->setMethod('POST')->setAction('?page=UserModule:Documents:createNewDocument')
           ->addElement($fb->createLabel()->setText('Document name')
                                          ->setFor('name'))
           ->addElement($fb->createInput()->setType('text')
                                          ->setName('name')
                                          ->require())
           ->addElement($fb->createLabel()->setText('Manager')
                                          ->setFor('manager'))
           ->addElement($fb->createSelect()->setName('manager')
                                           ->addOptionsBasedOnArray($managers))
           ->addElement($fb->createLabel()->setText('Status')
                                          ->setFor('status'))
           ->addElement($fb->createSelect()->setName('status')
                                           ->addOptionsBasedOnArray($statuses))
           ->addElement($fb->createLabel()->setText('Group')
                                          ->setFor('group'))
           ->addElement($fb->createSelect()->setName('group')
                                           ->addOptionsBasedOnArray($groups))
           ->addElement($fb->createSubmit('Create'))
           ;

        $form = $fb->build();

        $data['$NEW_DOCUMENT_FORM$'] = $form;

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function createNewDocument() {
        global $app;

        $name = htmlspecialchars($_POST['name']);
        $idManager = htmlspecialchars($_POST['manager']);
        $status = htmlspecialchars($_POST['status']);
        $idGroup = htmlspecialchars($_POST['group']);

        echo $name . ' ';
        echo $idManager . ' ';
        echo $status . ' ';
        echo $idGroup;

        $app->documentModel->insertNewDocument($name, $idManager, $app->user->getId(), $status, $idGroup);

        $idDocument = $app->documentModel->getLastInsertedDocumentForIdUser($app->user->getId())->getId();

        $documentGroupUsers = $app->groupUserModel->getGroupUsersByGroupId($idGroup);
        $documentIdManager = null;

        foreach($documentGroupUsers as $dgu) {
            if($dgu->getIsManager() == true) {
                $documentIdManager = $dgu->getIdUser();
            }
        }

        if(is_null($documentIdManager)) {
            die('Document group has no manager!');
        }

        $app->documentModel->updateOfficer($idDocument, $documentIdManager);

        $app->redirect('UserModule:Documents:showAll');
    }

    private function _delete_documents() {

    }
}

?>