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

    protected function unpinFilter() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_filter'));

        $idFilter = htmlspecialchars($_GET['id_filter']);
        $ribbon = $app->ribbonModel->getRibbonForIdDocumentFilter($idFilter);

        $app->ribbonModel->deleteRibbonForIdDocumentFilter($idFilter);
        $app->ribbonRightsModel->deleteAllUserRibbonRights($ribbon->getId());
        $app->ribbonRightsModel->deleteAllGroupRibbonRights($ribbon->getId());

        $rcm = CacheManager::getTemporaryObject(CacheCategories::RIBBONS);
        $rucm = CacheManager::getTemporaryObject(CacheCategories::RIBBON_USER_RIGHTS);
        $rgcm = CacheManager::getTemporaryObject(CacheCategories::RIBBON_GROUP_RIGHTS);

        $rcm->invalidateCache();
        $rucm->invalidateCache();
        $rgcm->invalidateCache();

        unset($rcm, $rucm, $rgcm);

        $app->flashMessage('Successfully unpinned selected filter', 'success');
        $app->redirect('UserModule:DocumentFilter:showFilters');
    }

    protected function pinFilter() {
        global $app;

        $app->flashMessageIfNotIsset(array('id_filter'));

        $idFilter = htmlspecialchars($_GET['id_filter']);
        $filter = $app->filterModel->getDocumentFilterById($idFilter);

        $parentRibbon = $app->ribbonModel->getRibbonByCode('documents');

        $data = array(
            'id_parent_ribbon' => $parentRibbon->getId(),
            'name' => $filter->getName(),
            'code' => 'documents.custom_filter.' . $filter->getId(),
            'is_visible' => '1',
            'is_system' => '0',
            'page_url' => '?page=UserModule:Documents:showDocumentsCustomFilter&id_filter=' . $filter->getId()
        );

        $app->ribbonModel->insertNewRibbon($data);
        $idRibbon = $app->ribbonModel->getLastInsertedRibbonId();

        $app->ribbonRightsModel->insertNewUserRibbonRight($idRibbon, $app->user->getId(), array($app->ribbonModel::VIEW => '1', $app->ribbonModel::EDIT => '1', $app->ribbonModel::DELETE => '1'));

        $rcm = CacheManager::getTemporaryObject(CacheCategories::RIBBONS);
        $rucm = CacheManager::getTemporaryObject(CacheCategories::RIBBON_USER_RIGHTS);
        $rgcm = CacheManager::getTemporaryObject(CacheCategories::RIBBON_GROUP_RIGHTS);

        $rcm->invalidateCache();
        $rucm->invalidateCache();
        $rgcm->invalidateCache();

        unset($rcm, $rucm, $rgcm);

        $app->flashMessage('Successfully pinned selected filter', 'success');
        $app->redirect('UserModule:DocumentFilter:showFilters');
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
            '$FILTER_GRID$' => $this->internalCreateFilterResultsGrid($filter),
            '$BULK_ACTION_CONTROLLER$' => ''
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
        global $app;

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/documents/document-filter-grid.html');

        $data = array(
            '$PAGE_TITLE$' => 'Document filters',
            '$LINKS$' => [],
            '$FILTER_GRID$' => $this->internalCreateStandardFilterGrid(),
            '$BULK_ACTION_CONTROLLER$' => ''
        );

        if($app->actionAuthorizator->checkActionRight(UserActionRights::CREATE_FILTER)) {
            $data['$LINKS$'][] = LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:showNewFilterForm'), 'New filter');
        }

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

        if(isset($_POST['has_ordering'])) {
            $data['has_ordering'] = '1';
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

        if(isset($_POST['has_ordering'])) {
            $data['has_ordering'] = '1';
        }

        $data['id_author'] = $app->user->getId();

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
        $fb = FormBuilder::getTemporaryObject();

        $fb ->setMethod('POST')->setAction('?page=UserModule:DocumentFilter:processNewFilterForm')
            
            ->addElement($fb->createLabel()->setText('Name')->setFor('name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->setMaxLength('256')->require())

            ->addElement($fb->createLabel()->setText('Description')->setFor('description'))
            ->addElement($fb->createTextArea()->setName('description'))
            
            ->addElement($fb->createLabel()->setText('SQL query ')->setFor('filter_sql'))
            ->addElement($fb->createTextArea()->setName('filter_sql'))

            ->addElement($fb->createLabel()->setText('SQL query has ordering?')->setFor('has_ordering'))
            ->addElement($fb->createInput()->setType('checkbox')->setName('has_ordering'))
            
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

        $seeSystemFilters = $app->actionAuthorizator->checkActionRight(UserActionRights::SEE_SYSTEM_FILTERS);
        $seeOtherUsersFilters = $app->actionAuthorizator->checkActionRight(UserActionRights::SEE_OTHER_USERS_FILTERS);

        $filters = [];
        if(!$seeSystemFilters && !$seeOtherUsersFilters) {
            $filters = $app->filterModel->getAllDocumentFiltersForIdUser($app->user->getId());
        } else {
            $filters = $app->filterModel->getAllDocumentFilters($seeSystemFilters, $seeOtherUsersFilters, $app->user->getId());
        }

        if(empty($filters)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($filters as $filter) {
                $showResultsLink = '-';
                $editLink = '-';
                $deleteLink = '-';
                $pinLink = '-';

                $ribbonFilterEntry = $app->ribbonModel->getRibbonForIdDocumentFilter($filter->getId());

                if(!is_null($filter->getIdAuthor())) {
                    if($filter->getIdAuthor() == $app->user->getId()) {
                        $showResultsLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:showFilterResults', 'id_filter' => $filter->getId()), 'Show results');
                        $editLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:showSingleFilter', 'id_filter' => $filter->getId()), 'Edit');
                        $deleteLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:deleteFilter', 'id_filter' => $filter->getId()), 'Delete');

                        if($ribbonFilterEntry === NULL) {
                            $pinLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:pinFilter', 'id_filter' => $filter->getId()), 'Pin');
                        } else {
                            $pinLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:unpinFilter', 'id_filter' => $filter->getId()), 'Unpin');
                        }
                    } else if($filter->getIdAuthor() != $app->user->getId()) {
                        if($app->actionAuthorizator->checkActionRight(UserActionRights::SEE_OTHER_USERS_FILTER_RESULTS)) {
                            $showResultsLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:showFilterResults', 'id_filter' => $filter->getId()), 'Show results');
                        }

                        if($app->actionAuthorizator->checkActionRight(UserActionRights::EDIT_OTHER_USERS_FILTER)) {
                            $editLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:showSingleFilter', 'id_filter' => $filter->getId()), 'Edit');
                        }

                        if($app->actionAuthorizator->checkActionRight(UserActionRights::DELETE_OTHER_USERS_FILTER)) {
                            $deleteLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:deleteFilter', 'id_filter' => $filter->getId()), 'Delete');
                        }
                    }
                } else {
                    if($app->actionAuthorizator->checkActionRight(UserActionRights::SEE_SYSTEM_FILTER_RESULTS)) {
                        $showResultsLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:showFilterResults', 'id_filter' => $filter->getId()), 'Show results');
                    }
                }

                $actionLinks = array(
                    $showResultsLink,
                    $editLink,
                    $deleteLink,
                    $pinLink
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

        if($filter->hasOrdering()) {
            $hasOrderingTrue = ' checked';
        } else {
            $hasOrderingTrue = '';
        }

        $fb ->setMethod('POST')->setAction('?page=UserModule:DocumentFilter:processEditFilterForm&id_filter=' . $filter->getId())
            
            ->addElement($fb->createLabel()->setText('Name')->setFor('name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->setMaxLength('256')->require()->setValue($filter->getName()))

            ->addElement($fb->createLabel()->setText('Description')->setFor('description'))
            ->addElement($fb->createTextArea()->setName('description')->setText($filter->getDescription() ?? ''))
        
            ->addElement($fb->createLabel()->setText('SQL query ')->setFor('filter_sql'))
            ->addElement($fb->createTextArea()->setName('filter_sql')->setText($filter->getSql()))

            ->addElement($fb->createLabel()->setText('SQL query has ordering?')->setFor('has_ordering'))
            ->addElement($fb->createInput()->setType('checkbox')->setName('has_ordering')->setSpecial($hasOrderingTrue))
            
            ->addElement($fb->createSubmit('Save'));

        return $fb->build();
    }

    private function internalCreateFilterResultsGrid(EntitiesDocumentFilter $filter) {
        return '
            <script type="text/javascript">
                loadDocumentsCustomFilter("' . $filter->getId() . '");
            </script>
            <table border="1"><img id="documents-loading" style="position: fixed; top: 50%; left: 49%;" src="img/loading.gif" width="32" height="32"></table>
        ';
        /*global $app;

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
    
        return $tb->build();*/
    }
}

?>