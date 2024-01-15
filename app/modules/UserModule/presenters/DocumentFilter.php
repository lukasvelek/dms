<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\CacheCategories;
use DMS\Constants\FlashMessageTypes;
use DMS\Constants\UserActionRights;
use DMS\Core\CacheManager;
use DMS\Entities\DocumentFilter as EntitiesDocumentFilter;
use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

class DocumentFilter extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('DocumentFilter', 'Document filters');

        $this->getActionNamesFromClass($this);
    }

    protected function deleteFilter() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_filter'), true, array('page' => 'UserModule:DocumentFilter:showFilters'));

        $idFilter = htmlspecialchars($_GET['id_filter']);

        $app->filterModel->deleteDocumentFilter($idFilter);
        
        $app->flashMessage('Document filter #' . $idFilter . ' deleted', 'success');
        $app->redirect('UserModule:DocumentFilter:showFilters');
    }

    protected function showFilterResults() {
        global $app;

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/documents/document-filter-grid.html');

        $app->flashMessageIfNotIsset(array('id_filter'), true, array('page' => 'UserModule:DocumentFilter:showFilters'));

        $idFilter = htmlspecialchars($_GET['id_filter']);
        $filter = $app->filterModel->getDocumentFilterById($idFilter);

        $data = array(
            '$PAGE_TITLE$' => 'Document filter #' . $idFilter . ' results',
            '$LINKS$' => array(
                LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:showFilters'), '<-')
            ),
            '$FILTER_GRID$' => $this->internalCreateFilterResultsGrid($filter)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showSingleFilter() {
        global $app;

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/documents/document-filter-form.html');

        $app->flashMessageIfNotIsset(array('id_filter'), true, array('page' => 'UserModule:DocumentFilter:showFilters'));

        $idFilter = htmlspecialchars($_GET['id_filter']);
        $filter = $app->filterModel->getDocumentFilterById($idFilter);

        $data = array(
            '$PAGE_TITLE$' => 'Filter <i>' . $filter->getName() . '</i>',
            '$LINKS$' => array(
                LinkBuilder::createLink('UserModule:DocumentFilter:showFilters', '<-')
            ),
            '$FILTER_FORM$' => $this->internalCreateEditFilterForm($filter)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showFilters() {
        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/documents/document-filter-grid.html');

        $data = array(
            '$PAGE_TITLE$' => 'Document filters',
            '$LINKS$' => array(
                LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:showNewFilterForm'), 'New filter')
            ),
            '$FILTER_GRID$' => $this->internalCreateStandardFilterGrid()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function processEditFilterForm() {
        global $app;

        $app->flashMessageIfNotIsset(array('name', 'id_filter'), true, array('page' => 'UserModule:DocumentFilter:showNewFilterForm'));
        $idFilter = htmlspecialchars($_GET['id_filter']);

        $data = [];
        $data['name'] = htmlspecialchars($_POST['name']);

        if(isset($_POST['description']) && $_POST['description'] != '') {
            $data['description'] = htmlspecialchars($_POST['description']);
        }

        if(isset($_POST['filter_sql'])) {
            $data['filter_sql'] = htmlspecialchars($_POST['filter_sql']);
        }

        $app->filterModel->updateDocumentFilter($data, $idFilter);

        $app->flashMessage('Filter #' . $idFilter . ' updated successfully', FlashMessageTypes::SUCCESS);
        $app->redirect('UserModule:DocumentFilter:showFilters');
    }

    protected function processNewFilterForm() {
        global $app;

        $app->flashMessageIfNotIsset(array('name'), true, array('page' => 'UserModule:DocumentFilter:showNewFilterForm'));

        $data = [];
        $data['name'] = htmlspecialchars($_POST['name']);

        if(isset($_POST['description']) && $_POST['description'] != '') {
            $data['description'] = htmlspecialchars($_POST['description']);
        }

        if(isset($_POST['filter_sql'])) {
            $data['filter_sql'] = htmlspecialchars($_POST['filter_sql']);
        }

        $data['id_author'] = $app->user->getId();

        /*if(!isset($data['filter_sql'])) {
            // generate sql from settings defined below
            $customMetadata = $app->metadataModel->getAllMetadataForTableName('documents');
            $metadata = [];
            $sql = 'SELECT * FROM `documents` WHERE ';

            if(count($customMetadata) > 0) {
                foreach($customMetadata as $cm) {
                    if(isset($_POST[$cm->getName()]) && $_POST[$cm->getName()] != '') {
                        $metadata[] = $cm->getName();
                    }
                }
            } else {
                $app->flashMessage('No filter parameters defined', 'error');
                $app->redirect('UserModule:DocumentFilter:showFilters');
                exit;
            }

            $i = 0;
            foreach($metadata as $m) {
                $val = htmlspecialchars($_POST[$m]);

                if(($i + 1) == count($metadata)) {
                    $sql .= ' (`' . $m . '` = ' . $val . ') ';
                } else {
                    $sql .= ' (`' . $m . '` = ' . $val . ') AND';
                }
            }
        }*/

        $app->filterModel->insertNewDocumentFilter($data);

        $app->flashMessage('Filter created successfully', FlashMessageTypes::SUCCESS);
        $app->redirect('UserModule:DocumentFilter:showFilters');
    }

    protected function showNewFilterForm() {
        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/documents/document-filter-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'New filter',
            '$LINKS$' => '',
            '$FILTER_FORM$' => $this->internalCreateNewFilterForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateNewFilterForm() {
        global $app;

        $fb = FormBuilder::getTemporaryObject();

        $fb ->setMethod('POST')->setAction('?page=UserModule:DocumentFilter:processNewFilterForm')
            
            ->addElement($fb->createLabel()->setText('Name')->setFor('name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->setMaxLength('256')->require())

            ->addElement($fb->createLabel()->setText('Description')->setFor('description'))
            ->addElement($fb->createTextArea()->setName('description'))
        ;

        /*$customMetadata = $app->metadataModel->getAllMetadataForTableName('documents');
        $metadata = [];

        if(count($customMetadata) > 0) {
            foreach($customMetadata as $cm) {
                if($cm->getInputType() == 'select_external') {
                    $name = $cm->getName();
                    $text = $cm->getText();
                    $values = $app->externalEnumComponent->getEnumByName($cm->getSelectExternalEnumName())->getValues();

                    $options = array(
                        array(
                            'value' => '-',
                            'text' => '-'
                        ),
                        array(
                            'value' => 'null',
                            'text' => 'Empty'
                        )
                    );
                    foreach($values as $value => $vtext) {
                        $options[] = array(
                            'value' => $value,
                            'text' => $vtext
                        );
                    }

                    $metadata[$name] = array('text' => $text, 'options' => $options, 'type' => 'select', 'length' => $cm->getInputLength());
                } else {
                    $name = $cm->getName();
                    $text = $cm->getText();
                    $values = $app->metadataModel->getAllValuesForIdMetadata($cm->getId());
    
                    $options = array(
                        array(
                            'value' => '-',
                            'text' => '-'
                        ),
                        array(
                            'value' => 'null',
                            'text' => 'Empty'
                        )
                    );
                    foreach($values as $v) {
                        $options[] = array(
                            'value' => $v->getValue(),
                            'text' => $v->getName()
                        );
                    }
    
                    $metadata[$name] = array('text' => $text, 'options' => $options, 'type' => $cm->getInputType(), 'length' => $cm->getInputLength());
                }
            }
        }

        foreach($metadata as $name => $d) {
            $text = $d['text'];
            $options = $d['options'];
            $inputType = $d['type'];
            $inputLength = $d['length'];

            $fb->addElement($fb->createLabel()->setText($text)->setFor($name));

            switch($inputType) {
                case 'select':
                    $fb ->addElement($fb->createSelect()->setName($name)->addOptionsBasedOnArray($options));
                    
                    break;

                case 'text':
                    if($inputLength > 256) {
                        $fb->addElement($fb->createTextArea()->setName($name));
                    } else {
                        $fb->addElement($fb->createInput()->setType($inputType)->setMaxLength($inputLength)->setName($name));
                    }

                    break;

                case 'number':
                    $fb ->addElement($fb->createInput()->setType($inputType)->setMaxLength($inputLength)->setName($name));

                    break;

                case 'boolean':
                    $fb ->addElement($fb->createInput()->setType('checkbox')->setName($name));

                    break;

                case 'date':
                    $fb ->addElement($fb->createInput()->setType('date')->setName($name));

                    break;

                case 'datetime':
                    $fb ->addElement($fb->createInput()->setType('datetime')->setName($name));

                    break;
            }
        }*/

        $fb ->addElement($fb->createLabel()->setText('SQL query ')->setFor('filter_sql'))
            ->addElement($fb->createTextArea()->setName('filter_sql'))
            
            ->addElement($fb->createSubmit('Create filter'));

        return $fb->build();
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
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:showFilterResults', 'id_filter' => $filter->getId()), 'Show results'),
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:showSingleFilter', 'id_filter' => $filter->getId()), 'Edit'),
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:deleteFilter', 'id_filter' => $filter->getId()), 'Delete')
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

    private function internalCreateEditFilterForm(EntitiesDocumentFilter $filter) {
        $fb = FormBuilder::getTemporaryObject();

        $fb ->setMethod('POST')->setAction('?page=UserModule:DocumentFilter:processEditFilterForm&id_filter=' . $filter->getId())
            
            ->addElement($fb->createLabel()->setText('Name')->setFor('name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->setMaxLength('256')->require()->setValue($filter->getName()))

            ->addElement($fb->createLabel()->setText('Description')->setFor('description'))
            ->addElement($fb->createTextArea()->setName('description')->setText($filter->getDescription() ?? ''))
        
            ->addElement($fb->createLabel()->setText('SQL query ')->setFor('filter_sql'))
            ->addElement($fb->createTextArea()->setName('filter_sql')->setText($filter->getSql()))
            
            ->addElement($fb->createSubmit('Save'));

        return $fb->build();
    }

    private function internalCreateFilterResultsGrid(EntitiesDocumentFilter $filter) {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $tb->showRowBorder();

        $headers = array(
            '<input type="checkbox" id="select-all" onchange="selectAllDocumentEntries()">',
            'Actions',
            'Name',
            'Author',
            'Status',
            'Folder'
        );
    
        $headerRow = null;

        $dbStatuses = $app->metadataModel->getAllValuesForIdMetadata($app->metadataModel->getMetadataByName('status', 'documents')->getId());

        $documents = $app->documentModel->getDocumentsBySQL($filter->getSql());
    
        if(empty($documents)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($documents as $document) {
                $actionLinks = [];

                if($app->actionAuthorizator->checkActionRight(UserActionRights::SEE_DOCUMENT_INFORMATION, null, false)) {
                    $actionLinks[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showInfo', 'id' => $document->getId()), 'Information');
                } else {
                    $actionLinks[] = '-';
                }

                if($app->actionAuthorizator->checkActionRight(UserActionRights::EDIT_DOCUMENT, null, false)) {
                    $actionLinks[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showEdit', 'id' => $document->getId()), 'Edit');
                } else {
                    $actionLinks[] = '-';
                }

                $shared = false;

                if(!$shared && $app->actionAuthorizator->checkActionRight(UserActionRights::SHARE_DOCUMENT, null, false)) {
                    $actionLinks[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showShare', 'id' => $document->getId()), 'Share');
                } else {
                    $actionLinks[] = '-';
                }
    
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
    
                $docuRow->addCol($tb->createCol()->setText('<input type="checkbox" id="select" name="select[]" value="' . $document->getId() . '" onupdate="drawDocumentBulkActions()" onchange="drawDocumentBulkActions()">'));
                
                foreach($actionLinks as $actionLink) {
                    $docuRow->addCol($tb->createCol()->setText($actionLink));
                }

                $author = $app->userModel->getUserById($document->getIdAuthor());

                $docuRow->addCol($tb->createCol()->setText($document->getName()))
                        ->addCol($tb->createCol()->setText($author->getFullname()))
                ;
    
                foreach($dbStatuses as $dbs) {
                    if($dbs->getValue() == $document->getStatus()) {
                        $docuRow->addCol($tb->createCol()->setText($dbs->getName()));
                    }
                }
    
                $folderName = '-';

                if(!is_null($document->getIdFolder())) {
                    $folder = $app->folderModel->getFolderById($document->getIdFolder());
                    $folderName = $folder->getName();
                }
    
                $docuRow->addCol($tb->createCol()->setText($folderName));
                    
                $tb->addRow($docuRow);
            }
        }
    
        return $tb->build();
    }
}

?>