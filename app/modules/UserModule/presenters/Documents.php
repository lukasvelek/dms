<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\ArchiveType;
use DMS\Constants\CacheCategories;
use DMS\Constants\DocumentAfterShredActions;
use DMS\Constants\DocumentShreddingStatus;
use DMS\Constants\DocumentStatus;
use DMS\Constants\ProcessTypes;
use DMS\Constants\UserActionRights;
use DMS\Core\AppConfiguration;
use DMS\Core\CacheManager;
use DMS\Core\CypherManager;
use DMS\Core\ScriptLoader;
use DMS\Entities\Document;
use DMS\Entities\Folder;
use DMS\Helpers\ArrayHelper;
use DMS\Helpers\ArrayStringHelper;
use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;

class Documents extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('Documents');

        $this->getActionNamesFromClass($this);
    }
    
    protected function processMoveToArchiveDocumentFormBulkAction() {
        global $app;

        $app->flashMessageIfNotIsset(['ids', 'archive_document'], true, ['page' => 'UserModule:Documents:showAll']);

        $ids = $this->get('ids', false);
        $archiveDocument = $this->post('archive_document');

        if(!is_array($ids)) {
            $ids = [$ids];
        }

        foreach($ids as $id) {
            echo $app->documentModel->moveToArchiveDocument($id, $archiveDocument);
        }

        $app->flashMessage('Documents moved to selected archive document', 'success');
        //$app->redirect('UserModule:SingleArchive:showContent', ['id' => $archiveDocument]);
        $app->redirect('UserModule:Documents:showAll');
    }

    protected function showDocumentsCustomFilter() {
        global $app;
        
        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/documents/document-filter-grid.html');
        
        $app->flashMessageIfNotIsset(array('id_filter'));

        $idFilter = $this->get('id_filter');
        
        $data = array(
            '$PAGE_TITLE$' => 'Documents',
            '$LINKS$' => [],
            '$FILTER_GRID$' => $this->internalCreateCustomFilterDocumentsGrid($idFilter),
            '$BULK_ACTION_CONTROLLER$' => ''
        );

        if($app->actionAuthorizator->checkActionRight(UserActionRights::CREATE_DOCUMENT)) {
            $newEntityLink = LinkBuilder::createLink('UserModule:Documents:showNewForm', 'New document');
        }

        $data['$LINKS$'][] = $newEntityLink;

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function downloadReport() {
        global $app;

        $app->flashMessageIfNotIsset(['hash']);

        $filename = 'cache/temp_' . $this->get('hash') . '.csv';
        $downloadFilename = 'cache/report_' . date('Y-m-d_H-i-s') . '.csv';

        copy($filename, $downloadFilename);

        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary"); 
        header("Content-disposition: attachment; filename=\"" . basename($downloadFilename) . "\"");

        readfile($downloadFilename);

        unlink($filename);
        unlink($downloadFilename);

        $app->flashMessage('Report has been generated and downloaded', 'success');

        ScriptLoader::loadJSScript('DocumentReportGenerator.js');
    }

    protected function generateReport() {
        global $app;

        $app->flashMessageIfNotIsset(['id_folder', 'filter', 'limit_range', 'order', 'total_count']);

        $idFolder = $this->get('id_folder');
        $totalCount = $this->get('total_count');
        $filter = $this->post('filter');
        $limit = $this->post('limit_range');
        $order = $this->post('order');

        $qb = $app->documentModel->composeQueryStandardDocuments(false);

        if($idFolder > 0) {
            $qb->where('id_folder = ?', [$idFolder]);
        }

        if(!is_numeric($filter)) {
            switch($filter) {
                case 'shredded':
                    $qb->andWhere('status = ?', [DocumentStatus::SHREDDED]);
                    break;
    
                case 'waitingForArchivation':
                    $qb->andWhere('status = ?', [DocumentStatus::ARCHIVATION_APPROVED]);
                    break;
    
                case 'archived':
                    $qb->andWhere('status = ?', [DocumentStatus::ARCHIVED]);
                    break;
    
                default:
                case 'all':
                    break;
            }

            if($limit < ($totalCount + 1)) {
                $qb->limit($limit);
            }
    
            if($order == 'desc') {
                $qb->orderBy('id', $order);
            }
        } else {
            $filterEntity = $app->filterModel->getDocumentFilterById($filter);

            $qb->setSQL($filterEntity->getSql());
    
            if(!$filterEntity->hasOrdering()) {
                if($limit < ($totalCount + 1)) {
                    $qb->limit($limit);
                }
                
                if($order == 'desc') {
                    $qb->orderBy('id', $order);
                }
            }
        }

        $rows = null;
        $app->logger->logFunction(function() use (&$rows, $qb) {
            $rows = $qb->execute()->fetchAll();
        }, __METHOD__);

        if($rows === FALSE || $rows === NULL) {
            die('Error!');
        }

        if($rows->num_rows > 1000) {
            // use background export
            $data = [
                'id_user' => $app->user->getId(),
                'sql_string' => $qb->getSQL()
            ];

            $app->documentModel->insertDocumentReportQueueEntry($data);

            $app->flashMessage('You requested to export more than 1000 entries. This operation will be done by background service. You will be able to find your export ' . LinkBuilder::createAdvLink(['page' => 'UserModule:DocumentReports:showAll'], 'here') . '.');
            $app->redirect('UserModule:Documents:showAll');
        }

        /*$fileRow = array(
            'id;id_folder;name;date_created' . "\r\n"
        );

        $app->logger->logFunction(function() use ($rows, &$fileRow) {
            foreach($rows as $row) {
                $fileRow[] = $row['id'] . ';' . ($row['id_folder'] ?? '-') . ';' . $row['name'] . ';' . $row['date_created'] . "\r\n";
            }
        }, __METHOD__);*/

        $hash = CypherManager::createCypher(32);
        $filename = 'temp_' . $hash . '.csv';

        //$app->fileManager->write('cache/' . $filename, $fileRow, false);

        $result = $app->documentReportGeneratorComponent->generateReport($rows, $app->user->getId(), $filename);

        if($result === FALSE) {
            die('ERROR! Documents presenter: line 205');
        }

        //$filename = 'cache/temp_' . $hash . '.csv';
        $filename = $result;
        $downloadFilename = 'cache/report_' . date('Y-m-d_H-i-s') . '.csv';

        copy($filename, $downloadFilename);

        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary"); 
        header("Content-disposition: attachment; filename=\"" . basename($downloadFilename) . "\"");

        readfile($downloadFilename);

        unlink($filename);
        //unlink($downloadFilename);
    }

    protected function showReportForm() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/documents/new-document-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'Document report generator',
            '$NEW_DOCUMENT_FORM$' => $this->internalCreateDocumentReportForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showSharedWithMe() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/documents/document-grid.html');

        $idFolder = null;
        $folderName = 'Main folder';
        $newEntityLink = '';
        $page = 1;

        if($app->actionAuthorizator->checkActionRight(UserActionRights::CREATE_DOCUMENT)) {
            $newEntityLink = LinkBuilder::createLink('UserModule:Documents:showNewForm', 'New document');
        }
        
        if(isset($_GET['grid_page'])) {
            $page = (int)($this->get('grid_page'));
        }

        $documentGrid = '';
        $folderList = '';
        
        $app->logger->logFunction(function() use (&$documentGrid, $page) {
            $documentGrid = $this->internalCreateSharedWithMeDocumentGrid($page);
        }, __METHOD__);

        $app->logger->logFunction(function() use (&$folderList, $idFolder) {
            $folderList = $this->internalCreateFolderList($idFolder, null);
        }, __METHOD__);

        $searchField = '
            <input type="text" id="q" placeholder="Search" oninput="ajaxSearch(this.value, \'' . ($idFolder ?? 'null') . '\');">
        ';

        $data = array(
            '$PAGE_TITLE$' => 'Documents',
            '$DOCUMENT_GRID$' => $documentGrid,
            '$BULK_ACTION_CONTROLLER$' => '',
            '$FORM_ACTION$' => '?page=UserModule:Documents:performBulkAction' . (is_null($idFolder) ? ('&id_folder=' . $idFolder) : ''),
            '$LINKS$' => array($newEntityLink),
            '$CURRENT_FOLDER_TITLE$' => $folderName,
            '$FOLDER_LIST$' => $folderList,
            '$SEARCH_FIELD$' => $searchField,
            '$DOCUMENT_PAGE_CONTROL$' => $this->internalCreateGridPageControl($page, $idFolder, 'showSharedWithMe')
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showFiltered() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/documents/document-grid.html');

        $idFolder = null;
        $folderName = 'Main folder';
        $newEntityLink = '';
        $page = 1;

        if($app->actionAuthorizator->checkActionRight(UserActionRights::CREATE_DOCUMENT)) {
            $newEntityLink = LinkBuilder::createLink('UserModule:Documents:showNewForm', 'New document');
        }

        if(isset($_GET['id_folder'])) {
            $idFolder = $this->get('id_folder');
            $folder = $app->folderModel->getFolderById($idFolder);
            $folderName = $folder->getName();
            $newEntityLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Documents:showNewForm', 'id_folder' => $idFolder), 'New document');
        }

        if(isset($_GET['grid_page'])) {
            $page = (int)($this->get('grid_page'));
        }

        $documentGrid = '';
        $folderList = '';

        $filter = null;

        if(isset($_GET['filter'])) {
            $filter = $this->get('filter');
        }

        $app->logger->logFunction(function() use (&$documentGrid, $idFolder, $filter, $page, $app) {
            if(AppConfiguration::getGridUseAjax()) {
                $documentGrid = $this->internalCreateStandardDocumentGridAjax($idFolder, $filter, $page);
            } else{
                $documentGrid = $this->internalCreateStandardDocumentGrid($idFolder, $filter, $page);
            }
        }, __METHOD__);

        $app->logger->logFunction(function() use (&$folderList, $idFolder, $filter) {
            $folderList = $this->internalCreateFolderList($idFolder, $filter);
        }, __METHOD__);

        $searchField = '
            <input type="text" id="q" placeholder="Search" oninput="loadDocumentsSearchFilter(this.value, \'' . ($idFolder ?? 'null') . '\', \'' . $filter . '\');">
        ';

        $data = array(
            '$PAGE_TITLE$' => 'Documents',
            '$DOCUMENT_GRID$' => $documentGrid,
            '$BULK_ACTION_CONTROLLER$' => '',
            '$FORM_ACTION$' => '?page=UserModule:Documents:performBulkAction' . (is_null($idFolder) ? ('&id_folder=' . $idFolder) : ''),
            '$LINKS$' => array($newEntityLink),
            '$CURRENT_FOLDER_TITLE$' => $folderName,
            '$FOLDER_LIST$' => $folderList,
            '$SEARCH_FIELD$' => $searchField,
            '$DOCUMENT_PAGE_CONTROL$' => $this->internalCreateGridPageControl($page, $idFolder)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showAll() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/documents/document-grid.html');

        $idFolder = null;
        $folderName = 'Main folder';
        $newEntityLink = '';
        $page = 1;

        if($app->actionAuthorizator->checkActionRight(UserActionRights::CREATE_DOCUMENT)) {
            $newEntityLink = LinkBuilder::createLink('UserModule:Documents:showNewForm', 'New document');
        }

        if(isset($_GET['id_folder'])) {
            $idFolder = $this->get('id_folder');

            if($idFolder > -1) {
                $folder = $app->folderModel->getFolderById($idFolder);
                $folderName = $folder->getName();
                $newEntityLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Documents:showNewForm', 'id_folder' => $idFolder), 'New document');
            } else {
                $idFolder = null;
            }
        }

        if(isset($_GET['grid_page'])) {
            $page = (int)($this->get('grid_page'));
        }

        $documentGrid = '';
        $folderList = '';
        
        $app->logger->logFunction(function() use (&$documentGrid, $idFolder, $page, $app) {
            if(AppConfiguration::getGridUseAjax()) {
                $documentGrid = $this->internalCreateStandardDocumentGridAjax($idFolder, '', $page);
            } else{
                $documentGrid = $this->internalCreateStandardDocumentGrid($idFolder, $page);
            }
        }, __METHOD__);

        $app->logger->logFunction(function() use (&$folderList, $idFolder) {
            $folderList = $this->internalCreateFolderList($idFolder, null);
        }, __METHOD__);

        $searchField = '
            <input type="text" id="q" placeholder="Search" oninput="loadDocumentsSearch(this.value, \'' . ($idFolder ?? 'null') . '\');">
            <!--<script type="text/javascript" src="js/DocumentAjaxSearch.js"></script>
            <script type="text/javascript" src="js/DocumentAjaxBulkActions.js"></script>-->
        ';

        $data = array(
            '$PAGE_TITLE$' => 'Documents',
            '$DOCUMENT_GRID$' => $documentGrid,
            '$BULK_ACTION_CONTROLLER$' => '',
            '$FORM_ACTION$' => '?page=UserModule:Documents:performBulkAction' . (is_null($idFolder) ? ('&id_folder=' . $idFolder) : ''),
            '$LINKS$' => array($newEntityLink),
            '$CURRENT_FOLDER_TITLE$' => $folderName,
            '$FOLDER_LIST$' => $folderList,
            '$SEARCH_FIELD$' => $searchField,
            '$DOCUMENT_PAGE_CONTROL$' => $this->internalCreateGridPageControl($page, $idFolder)
        );

        if($app->actionAuthorizator->checkActionRight(UserActionRights::GENERATE_DOCUMENT_REPORT)) {
            $data['$LINKS$'][] = '&nbsp;&nbsp;' . LinkBuilder::createAdvLink(array('page' => 'UserModule:Documents:showReportForm', 'id_folder' => ($idFolder ?? 0)), 'Document report');
        }

        if($app->actionAuthorizator->checkActionRight(UserActionRights::SEE_OTHER_USERS_FILTERS) ||
           $app->actionAuthorizator->checkActionRight(UserActionRights::SEE_SYSTEM_FILTERS) ||
           $app->actionAuthorizator->checkActionRight(UserActionRights::CREATE_FILTER)) {
            $data['$LINKS$'][] = '&nbsp;&nbsp;' . LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:showFilters'), 'Filters');
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateStandardDocumentGrid(?int $idFolder, ?string $filter, int $page = 1) {
        global $app;

        $documentModel = $app->documentModel;
        $userModel = $app->userModel;
        $folderModel = $app->folderModel;

        $ucm = CacheManager::getTemporaryObject(CacheCategories::USERS);
        $fcm = CacheManager::getTemporaryObject(CacheCategories::FOLDERS);

        if($idFolder == 'null') {
            $idFolder = null;
        }

        $dataSourceCallback = function() use ($documentModel, $idFolder, $filter, $page) {
            if(AppConfiguration::getGridUseFastLoad()) {
                $page -= 1;

                $firstIdDocumentOnPage = $documentModel->getFirstIdDocumentOnAGridPage(($page * AppConfiguration::getGridSize()));

                return $documentModel->getStandardDocumentsFromId($firstIdDocumentOnPage, $idFolder, $filter, AppConfiguration::getGridSize());
            } else {
                return $documentModel->getStandardDocuments($idFolder, $filter, ($page * AppConfiguration::getGridSize()));
            }
        };

        $gb = new GridBuilder();

        $gb->addColumns(['name' => 'Name', 'idAuthor' => 'Author', 'status' => 'Status', 'idFolder' => 'Folder']);
        $gb->addOnColumnRender('idAuthor', function(Document $document) use ($ucm, $userModel) {
            $user = $ucm->loadUserByIdFromCache($document->getIdAuthor());

            if(is_null($user)) {
                $user = $userModel->getUserById($document->getIdAuthor());

                $ucm->saveUserToCache($user);
            }

            return $user->getFullname();
        });
        $gb->addOnColumnRender('idFolder', function(Document $document) use ($fcm, $folderModel) {
            if(is_null($document->getIdFolder())) {
                return '-';
            } else {
                $folder = $fcm->loadFolderByIdFromCache($document->getIdFolder());

                if(is_null($folder)) {
                    $folder = $folderModel->getFolderById($document->getIdFolder());

                    $fcm->saveFolderToCache($folder);
                }

                return $folder->getName();
            }
        });
        $gb->addOnColumnRender('status', function(Document $document) {
            return DocumentStatus::$texts[$document->getStatus()];
        });
        $gb->addHeaderCheckbox('select-all', 'selectAllDocumentEntries()');
        $gb->addRowCheckbox(function(Document $document) {
            return '<input type="checkbox" id="select" name="select[]" value="' . $document->getId() . '" onupdate="drawDocumentBulkActions()" onchange="drawDocumentBulkActions()">';
        });
        $gb->addDataSourceCallback($dataSourceCallback);

        return $gb->build();
    }

    private function internalCreateStandardDocumentGridAjax(?int $idFolder, ?string $filter, int $page = 1) {
        $code = '<script type="text/javascript">';

        if($filter != null) {
            $code .= 'loadDocumentsFilter("' . ($idFolder ?? 'null') . '", "' . $filter . '")';
        } else {
            $code .= 'loadDocuments("' . ($idFolder ?? 'null') . '", "' . $page . '");';
        }

        $code .= '</script>';
        $code .= '<table border="1"><img id="documents-loading" style="position: fixed; top: 50%; left: 49%;" src="img/loading.gif" width="32" height="32"></table>';

        return $code;
    }

    protected function performBulkAction() {
        global $app;

        $app->flashMessageIfNotIsset(['select']);

        $ids = $this->get('select', false);
        $action = $this->get('action');
        
        $idFolder = -1;
        if(isset($_GET['id_folder'])) {
            $idFolder = $this->get('id_folder');
        }

        if($action == '-') {
            $app->redirect('UserModule:Documents:showAll', ['id_folder' => $idFolder]);
        }

        if(method_exists($this, '_' . $action)) {
            return $this->{'_' . $action}($ids, $idFolder);
        } else {
            die('Method does not exist!');
        }
    }

    protected function showNewForm() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/documents/new-document-form.html');

        $idFolder = null;

        if(isset($_GET['id_folder'])) {
            $idFolder = $this->get('id_folder');
        }

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

        if(count($users) > 0) {
            foreach($users as $user) {
                $managers[] = array(
                    'value' => $user->getId(),
                    'text' => $user->getFullname()
                );
            }
        }

        $statusMetadata = $app->metadataModel->getMetadataByName('status', 'documents');
        $dbStatuses = $app->metadataModel->getAllValuesForIdMetadata($statusMetadata->getId());
        $statuses = [];

        if(count($dbStatuses) > 0) {
            foreach($dbStatuses as $dbs) {
                $statuses[] = array(
                    'value' => $dbs->getValue(),
                    'text' => $dbs->getName()
                );
            }
        } else {
            ScriptLoader::alert('No statuses found!', array('UserModule:Documents:showAll'));
        }

        $dbGroups = $app->groupModel->getAllGroups();
        $groups = [];

        if(count($dbGroups) > 0) {
            foreach($dbGroups as $dbg) {
                $groups[] = array(
                    'value' => $dbg->getId(),
                    'text' => $dbg->getName()
                );
            }
        } else {
            ScriptLoader::alert('No groups found!', array('UserModule:Documents:showAll'));
        }

        $rankMetadata = $app->metadataModel->getMetadataByName('rank', 'documents');
        $dbRanks = $app->metadataModel->getAllValuesForIdMetadata($rankMetadata->getId());
        $ranks = [];

        if(count($dbRanks) > 0) {
            foreach($dbRanks as $dbr) {
                $ranks[] = array(
                    'value' => $dbr->getValue(),
                    'text' => $dbr->getName()
                );
            }
        } else {
            ScriptLoader::alert('No ranks found!', array('UserModule:Documents:showAll'));
        }

        $dbFolders = $app->folderModel->getAllFolders();
        $folders = array(
            array(
                'value' => '-1',
                'text' => '-'
            )
        );

        if(count($dbFolders) > 0) {
            foreach($dbFolders as $dbf) {
                $text = $dbf->getName();
    
                for($i = 0; $i < $dbf->getNestLevel(); $i++) {
                    $text = '&nbsp;&nbsp;' . $text;
                }
    
                $folder = array(
                    'value' => $dbf->getId(),
                    'text' => $text
                );
    
                if($idFolder != null && $idFolder == $dbf->getId()) {
                    $folder['selected'] = 'selected';
                }
    
                $folders[] = $folder;
            }
        }

        $shredYears = [];
        for($i = 1950; $i < 2200; $i++) {
            if(date('Y') == $i) {
                $shredYears[] = array(
                    'value' => $i,
                    'text' => $i,
                    'selected' => 'selected'
                );
            } else {
                $shredYears[] = array(
                    'value' => $i,
                    'text' => $i
                );
            }
        }

        $afterShredActions = [];
        foreach(DocumentAfterShredActions::$texts as $value => $text) {
            $afterShredActions[] = array(
                'value' => $value,
                'text' => $text
            );
        }

        $customMetadata = $app->metadataModel->getAllMetadataForTableName('documents');
        // name = array('text' => 'text', 'options' => 'options from metadata_values')
        $metadata = [];

        if(count($customMetadata) > 0) {
            foreach($customMetadata as $cm) {
                if($cm->getIsSystem()) {
                    continue;
                }

                if($cm->getInputType() == 'select_external') {
                    $name = $cm->getName();
                    $text = $cm->getText();
                    $values = $app->externalEnumComponent->getEnumByName($cm->getSelectExternalEnumName())->getValues();

                    $options = [];
                    foreach($values as $value => $vtext) {
                        $options[] = array(
                            'value' => $value,
                            'text' => $vtext
                        );
                    }

                    $metadata[$name] = array('text' => $text, 'options' => $options, 'type' => 'select', 'length' => $cm->getInputLength(), 'readonly' => $cm->getIsReadonly());
                } else {
                    $name = $cm->getName();
                    $text = $cm->getText();
                    $values = $app->metadataModel->getAllValuesForIdMetadata($cm->getId());
    
                    $options = [];

                    $options[] = [
                        'value' => 'null',
                        'text' => '-'
                    ];

                    $hasDefault = false;
                    foreach($values as $v) {
                        $option = array(
                            'value' => $v->getValue(),
                            'text' => $v->getName()
                        );

                        if($v->getIsDefault()) {
                            $option['selected'] = 'selected';
                            $hasDefault = true;
                        }
                        
                        $options[] = $option;
                    }

                    if($hasDefault === FALSE) {
                        $options[0]['selected'] = 'selected';
                    }
    
                    $metadata[$name] = array('text' => $text, 'options' => $options, 'type' => $cm->getInputType(), 'length' => $cm->getInputLength(), 'readonly' => $cm->getIsReadonly());
                }
            }
        }

        $fb = FormBuilder::getTemporaryObject();
        
        $name = $fb->createInput()  ->setType('text')
                                    ->setName('name')
                                    ->require();

        $fb ->setMethod('POST')->setAction('?page=UserModule:Documents:createNewDocument')->setEncType()

            ->addElement($fb->createLabel()->setText('Document name')
                                           ->setFor('name'))
            ->addElement($name)

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

            ->addElement($fb->createLabel()->setFor('rank')
                                           ->setText('Rank'))
            ->addElement($fb->createSelect()->setName('rank')
                                            ->addOptionsBasedOnArray($ranks))

            ->addElement($fb->createLabel()->setFor('folder')
                                           ->setText('Folder'))
            ->addElement($fb->createSelect()->setName('folder')
                                            ->addOptionsBasedOnArray($folders))

            ->addElement($fb->createLabel()->setFor('file')
                                           ->setText('File'))
            ->addElement($fb->createInput()->setType('file')->setName('file'))

            ->addElement($fb->createLabel()->setFor('shred_year')
                                           ->setText('Shred year'))
            ->addElement($fb->createSelect()->setName('shred_year')
                                            ->addOptionsBasedOnArray($shredYears))

            ->addElement($fb->createLabel()->setFor('after_shred_action')
                                           ->setText('Action after shredding'))
            ->addElement($fb->createSelect()->setName('after_shred_action')
                                            ->addOptionsBasedOnArray($afterShredActions))
           ;

        foreach($metadata as $name => $d) {
            $text = $d['text'];
            $options = $d['options'];
            $inputType = $d['type'];
            $inputLength = $d['length'];
            $readonly = $d['readonly'];

            $fb->addElement($fb->createLabel()->setText($text)->setFor($name));
            $elem = null;

            switch($inputType) {
                case 'select':
                    $elem = $fb->createSelect()->setName($name)->addOptionsBasedOnArray($options);
                    
                    break;

                case 'text':
                    if($inputLength > 256) {
                        $elem = $fb->createTextArea()->setName($name);
                    } else {
                        $elem = $fb->createInput()->setType($inputType)->setMaxLength($inputLength)->setName($name);
                    }

                    break;

                case 'number':
                    $elem = $fb->createInput()->setType($inputType)->setMaxLength($inputLength)->setName($name);

                    break;

                case 'boolean':
                    $elem = $fb->createInput()->setType('checkbox')->setName($name);

                    break;

                case 'date':
                    $elem = $fb->createInput()->setType('date')->setName($name);

                    break;

                case 'datetime':
                    $elem = $fb->createInput()->setType('datetime')->setName($name);

                    break;
            }

            if(!is_null($elem)) {
                if($readonly) {
                    $elem->readonly();
                }
        
                $fb->addElement($elem);
            }
        }

        $fb->addElement($fb->createSubmit('Create'));

        $form = $fb->build();

        $data['$NEW_DOCUMENT_FORM$'] = $form;

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function createNewDocument() {
        global $app;

        $data = [];

        $idGroup = $this->post('group');
        $idFolder = $this->post('folder');
        
        $data['name'] = $this->post('name');
        $data['id_manager'] = $this->post('manager');
        $data['status'] = $this->post('status');
        $data['id_group'] = $idGroup;
        $data['id_author'] = $app->user->getId();
        $data['shred_year'] = $this->post('shred_year');
        $data['after_shred_action'] = $this->post('after_shred_action');
        $data['shredding_status'] = DocumentShreddingStatus::NO_STATUS;

        if($idFolder != '-1') {
            $data['id_folder'] = $idFolder;
        }

        if(isset($_FILES['file'])) {
            $data['file'] = $_FILES['file']['name'];
        }

        ArrayHelper::deleteKeysFromArray($_POST, [
            'name',
            'manager',
            'status',
            'group',
            'folder',
            'shred_year',
            'after_shred_action'
        ]);

        $customMetadata = ArrayHelper::formatArrayData($_POST);

        $remove = [];
        foreach($customMetadata as $key => $value) {
            if($value == 'null') {
                $remove[] = $key;
            }
        }

        ArrayHelper::deleteKeysFromArray($customMetadata, $remove);
        $data = array_merge($data, $customMetadata);

        if(isset($data['file']) && !empty($data['file'])) {
            $app->fsManager->uploadFile($_FILES['file'], $data['file']);
        }
        
        // CUSTOM OPERATION DEFINITION
        // END OF CUSTOM OPERATION DEFINITION
        
        $app->documentModel->insertNewDocument($data);

        $idDocument = $app->documentModel->getLastInsertedDocumentForIdUser($app->user->getId())->getId();

        $app->logger->info('Created document #' . $idDocument, __METHOD__);

        $documentGroupUsers = $app->groupUserModel->getGroupUsersByGroupId($idGroup);
        $documentIdManager = null;

        foreach($documentGroupUsers as $dgu) {
            if($dgu->getIsManager() == true) {
                $documentIdManager = $dgu->getIdUser();
            }
        }

        if(is_null($documentIdManager)) {
            $app->flashMessage('Group #' . $idGroup . ' has no manager!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $app->documentModel->updateOfficer($idDocument, $documentIdManager);

        $app->flashMessage('Created new document', 'success');
        
        $url = 'UserModule:Documents:showAll';

        if(isset($data['id_folder'])) {
            $app->redirect($url, array('id_folder' => $data['id_folder']));
        } else {
            $app->redirect($url);
        }
    }

    private function _move_to_archive_document(array $ids, int $idFolder) {
        global $app;

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/documents/new-document-form.html');

        $data = [
            '$PAGE_TITLE$' => 'Move document to archive document',
            '$NEW_DOCUMENT_FORM$' => $this->internalCreateMoveToArchiveDocumentForm($ids, $idFolder)
        ];

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function _move_from_archive_document(array $ids, int $idFolder) {
        global $app;

        foreach($ids as $id) {
            $app->documentModel->moveFromArchiveDocument($id);
        }

        $app->flashMessage('Documents moved from the archive document', 'success');
        $app->redirect('UserModule:Documents:showAll', ($idFolder > -1) ? ['id_folder' => $idFolder] : []);
    }

    private function _suggest_for_shredding(array $ids, int $idFolder) {
        global $app;

        foreach($ids as $id) {
            $app->documentModel->updateDocument($id, array(
                'shredding_status' => DocumentShreddingStatus::IN_APPROVAL
            ));
            $app->processComponent->startProcess(ProcessTypes::SHREDDING, $id, $app->user->getId());
        }

        $app->flashMessage('Process has started', 'success');
        $app->redirect('UserModule:Documents:showAll', ($idFolder > -1) ? ['id_folder' => $idFolder] : []);
    }

    private function _delete_documents(array $ids, int $idFolder) {
        global $app;

        foreach($ids as $id) {
            $app->processComponent->startProcess(ProcessTypes::DELETE, $id, $app->user->getId());
        }

        $app->flashMessage('Process has started', 'success');
        $app->redirect('UserModule:Documents:showAll', ($idFolder > -1) ? ['id_folder' => $idFolder] : []);
    }

    private function _decline_archivation(array $ids, int $idFolder) {
        global $app;
        
        foreach($ids as $id) {
            $document = null;

            $app->logger->logFunction(function() use (&$document, $id, $app) {
                $document = $app->documentModel->getDocumentById($id);
            }, __METHOD__);

            if($document == null) {
                die();
            }

            if($app->documentAuthorizator->canDeclineArchivation($document)) {
                $app->documentModel->updateStatus($document->getId(), DocumentStatus::ARCHIVATION_DECLINED);
            }
        }

        if(count($ids) == 1) {
            $app->flashMessage('Declined archivation for selected document', 'success');
        } else {
            $app->flashMessage('Declined archivation for selected documents', 'success');
        }

        $app->redirect('UserModule:Documents:showAll', ($idFolder > -1) ? ['id_folder' => $idFolder] : []);
    }

    private function _approve_archivation(array $ids, int $idFolder) {
        global $app;

        foreach($ids as $id) {
            $document = null;

            $app->logger->logFunction(function() use (&$document, $id, $app) {
                $document = $app->documentModel->getDocumentById($id);
            }, __METHOD__);

            if($document == null) {
                die();
            }

            if($app->documentAuthorizator->canApproveArchivation($document)) {
                $app->documentModel->updateStatus($document->getId(), DocumentStatus::ARCHIVATION_APPROVED);
            }
        }

        if(count($ids) == 1) {
            $app->flashMessage('Approved archivation for selected document', 'success');
        } else {
            $app->flashMessage('Approved archivation for selected documents', 'success');
        }

        $app->redirect('UserModule:Documents:showAll', ($idFolder > -1) ? ['id_folder' => $idFolder] : []);
    }

    private function _archive(array $ids, int $idFolder) {
        global $app;

        foreach($ids as $id) {
            $document = null;

            $app->logger->logFunction(function() use (&$document, $id, $app) {
                $document = $app->documentModel->getDocumentById($id);
            }, __METHOD__);

            if($document == null) {
                die();
            }

            if($app->documentAuthorizator->canArchive($document)) {
                $app->documentModel->updateStatus($document->getId(), DocumentStatus::ARCHIVED);
            }
        }

        if(count($ids) == 1) {
            $app->flashMessage('Archived selected document', 'success');
        } else {
            $app->flashMessage('Archived selected documents', 'success');
        }

        $app->redirect('UserModule:Documents:showAll', ($idFolder > -1) ? ['id_folder' => $idFolder] : []);
    }

    private function internalCreateFolderList(?int $idFolder, ?string $filter) {
        global $app;

        $createLink = function(string $action, string $text, ?int $idFolder, ?string $filter) {
            $url = array(
                'page' => 'UserModule:Documents:' . $action
            );

            if($idFolder != null) {
                $url['id_folder'] = $idFolder;
            }

            if($filter != null) {
                $url['filter'] = $filter;
            }

            return LinkBuilder::createAdvLink($url, $text);
        };

        $link = 'showAll';
        if($filter != null) {
            $link = 'showFiltered';
        }
        
        $list = array(
            'null1' => '&nbsp;&nbsp;' . $createLink($link, 'Main folder' . (AppConfiguration::getGridMainFolderHasAllComments() ? ' (all documents)' : ''), null, $filter) . '<br>',
            'null2' => '<hr>'
        );
        
        $folders = $app->folderModel->getAllFolders();

        foreach($folders as $folder) {
            $this->_createFolderList($folder, $list, 0, $filter, $createLink);
        }

        if(count($folders) > 0) {
            $list['null3'] = '<hr>';
        }

        $list['null4'] = '&nbsp;&nbsp;' . LinkBuilder::createLink('UserModule:Documents:showSharedWithMe', 'Documents shared with me');

        return ArrayStringHelper::createUnindexedStringFromUnindexedArray($list);
    }

    private function _createFolderList(Folder $folder, array &$list, int $level, ?string $filter, callable $linkCreationMethod) {
        global $app;

        $link = 'showAll';
        if($filter != null) {
            $link = 'showFiltered';
        }

        $childFolders = $app->folderModel->getFoldersForIdParentFolder($folder->getId());

        $folderLink = $linkCreationMethod($link, $folder->getName(), $folder->getId(), $filter);
        
        $spaces = '&nbsp;&nbsp;';

        if($level > 0) {
            for($i = 0; $i < $level; $i++) {
                $spaces .= '&nbsp;&nbsp;';
            }
        }

        if(!array_key_exists($folder->getId(), $list)) {
            $list[$folder->getId()] = $spaces . $folderLink . '<br>';
        }

        if(count($childFolders) > 0) {
            foreach($childFolders as $cf) {
                $this->_createFolderList($cf, $list, $level + 1, $filter, $linkCreationMethod);
            }
        }
    }

    private function internalCreateSharedWithMeDocumentGrid(int $page) {
        return '
            <script type="text/javascript">
            loadDocumentsSharedWithMe("' . $page . '");
            </script> 
            <table border="1"><img id="documents-loading" style="position: fixed; top: 50%; left: 49%;" src="img/loading.gif" width="32" height="32"></table>
        ';
    }

    private function internalCreateCustomFilterDocumentsGrid(int $idFilter) {
        return '
            <script type="text/javascript">
            loadDocumentsCustomFilter("' . $idFilter . '");
            </script> 
            <table border="1"><img id="documents-loading" style="position: fixed; top: 50%; left: 49%;" src="img/loading.gif" width="32" height="32"></table>
        ';
    }

    private function internalCreateGridPageControl(int $page, ?string $idFolder, string $action = 'showAll') {
        global $app;

        $documentCount = 0;

        switch($action) {
            case 'showSharedWithMe':
                $documentCount = $app->documentModel->getCountDocumentsSharedWithUser($app->user->getId());
                break;

            case 'showFiltered':
                if(isset($_GET['filter'])) {
                    $f = $this->get('filter');
                    $documentCount = $app->documentModel->getDocumentCountForStatus($idFolder, $f);
                }
                break;

            default:
            case 'showAll':
                $documentCount = $app->documentModel->getTotalDocumentCount($idFolder);
                break;
        }

        $documentPageControl = '';
        $firstPageLink = '<a class="general-link" title="First page" href="?page=UserModule:Documents:' . $action;
        $previousPageLink = '<a class="general-link" title="Previous page" href="?page=UserModule:Documents:' . $action;
        $nextPageLink = '<a class="general-link" title="Next page" href="?page=UserModule:Documents:' . $action;
        $lastPageLink = '<a class="general-link" title="Last page" href="?page=UserModule:Documents:' . $action;

        if(!is_null($idFolder)) {
            $firstPageLink .= '&id_folder=' . $idFolder;
            $previousPageLink .= '&id_folder=' . $idFolder;
            $nextPageLink .= '&id_folder=' . $idFolder;
            $lastPageLink .= '&id_folder=' . $idFolder;
        }

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

        if($documentCount <= ($page * AppConfiguration::getGridSize())) {
            $nextPageLink .= ' hidden';
        }

        $nextPageLink .= '>&gt;</a>';

        $lastPageLink .= '&grid_page=' . (ceil($documentCount / AppConfiguration::getGridSize()));
        $lastPageLink .= '"';

        if($documentCount <= ($page * AppConfiguration::getGridSize())) {
            $lastPageLink .= ' hidden';
        }

        $lastPageLink .= '>&gt;&gt;</a>';

        if($documentCount > AppConfiguration::getGridSize()) {
            if($pageCheck * AppConfiguration::getGridSize() >= $documentCount) {
                $documentPageControl = (1 + ($page * AppConfiguration::getGridSize()));
            } else {
                $documentPageControl = (1 + ($pageCheck * AppConfiguration::getGridSize())) . '-' . (AppConfiguration::getGridSize() + ($pageCheck * AppConfiguration::getGridSize()));
            }
        } else {
            $documentPageControl = $documentCount;
        }

        $documentPageControl .= ' | ' . $firstPageLink . ' ' . $previousPageLink . ' ' . $nextPageLink . ' ' . $lastPageLink;

        return $documentPageControl;
    }

    private function internalCreateDocumentReportForm() {
        global $app;

        $idFolder = 0;

        if(isset($_GET['id_folder'])) {
            $idFolder = $this->get('id_folder');
        }

        $fb = FormBuilder::getTemporaryObject();

        $filterArray = [];

        $addFilter = function(string $key, string $value) use (&$filterArray) {
            $filterArray[] = array('value' => $key, 'text' => $value);
        };

        $addFilter('all', 'All');
        $addFilter('waitingForArchivation', 'Waiting for archivation');
        $addFilter('shredded', 'Shredded');
        $addFilter('archived', 'Archived');

        // ===== CUSTOM FILTERS =====

        $seeSystemFilters = $app->actionAuthorizator->checkActionRight(UserActionRights::SEE_SYSTEM_FILTERS);
        $seeOtherUsersFilters = $app->actionAuthorizator->checkActionRight(UserActionRights::SEE_OTHER_USERS_FILTERS);

        $filters = [];
        if(!$seeSystemFilters && !$seeOtherUsersFilters) {
            $filters = $app->filterModel->getAllDocumentFiltersForIdUser($app->user->getId());
        } else {
            $filters = $app->filterModel->getAllDocumentFilters($seeSystemFilters, $seeOtherUsersFilters, $app->user->getId());
        }

        foreach($filters as $filter) {
            $addFilter($filter->getId(), $filter->getName());
        }

        // ===== END OF CUSTOM FILTERS =====

        $orderArray = array(
            array('value' => 'asc', 'text' => 'Ascending'),
            array('value' => 'desc', 'text' => 'Descending')
        );

        $count = 0;

        $app->logger->logFunction(function() use (&$count, $app) {
            $count = $app->documentModel->getDocumentCountByStatus();
        }, __METHOD__);

        $step = 1;

        if($count >= 20) {
            $step = $count / 20;
        }

        $fb
            ->setMethod('POST')->setAction('?page=UserModule:Documents:generateReport&id_folder=' . $idFolder . '&total_count=' . $count)

            ->addElement($fb->createLabel()->setText('Filter')->setFor('filter'))
            ->addElement($fb->createSelect()->setName('filter')->addOptionsBasedOnArray($filterArray))

            ->addElement($fb->createLabel()->setText('Limit')->setFor('limit_range'))
            ->addElement($fb->createLabel()->setText('')->setId('limit_text'))
            ->addElement($fb->createInput()->setType('range')->setMin('1')->setMax(($count + 1))->setName('limit_range')->setStep($step)->setValue(($count / 2)))

            ->addElement($fb->createLabel()->setText('Order')->setFor('order'))
            ->addElement($fb->createSelect()->setName('order')->addOptionsBasedOnArray($orderArray))

            ->addJSScript(ScriptLoader::loadJSScript('js/ReportForm.js'))

            ->addElement($fb->createSubmit('Generate'))
        ;

        return $fb->build();
    }

    private function internalCreateMoveToArchiveDocumentForm(array $ids, int $idFolder) {
        global $app;

        $archiveDocuments = $app->archiveModel->getAllAvailableArchiveEntitiesByType(ArchiveType::DOCUMENT);
        $archiveDocumentsArr = [];
        foreach($archiveDocuments as $ad) {
            $archiveDocumentsArr[] = [
                'value' => $ad->getId(),
                'text' => $ad->getName()
            ];
        }

        $urlIds = '&';
        $i = 0;
        foreach($ids as $id) {
            if(($i + 1) == count($ids)) {
                $urlIds .= 'ids=' . $id;
            } else {
                $urlIds .= 'ids=' . $id . '&';
            }
            $i++;
        }

        $fb = new FormBuilder();

        $fb ->setMethod('POST')->setAction('?page=UserModule:Documents:processMoveToArchiveDocumentFormBulkAction' . $urlIds . ($idFolder > -1) ? ('id_folder=' . $idFolder) : '')
            
            ->addElement($fb->createLabel()->setText('Archive document')->setFor('archive_document'))
            ->addElement($fb->createSelect()->setName('archive_document')->addOptionsBasedOnArray($archiveDocumentsArr))

            ->addElement($fb->createSubmit('Move'))
        ;

        return $fb->build();
    }
}

?>