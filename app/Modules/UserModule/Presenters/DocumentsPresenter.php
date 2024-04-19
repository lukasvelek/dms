<?php

namespace DMS\Modules\UserModule;

use DMS\Components\DocumentReports\ADocumentReport;
use DMS\Constants\ArchiveType;
use DMS\Constants\DocumentAfterShredActions;
use DMS\Constants\DocumentShreddingStatus;
use DMS\Constants\DocumentStatus;
use DMS\Constants\FileStorageTypes;
use DMS\Constants\Metadata\DocumentMetadata;
use DMS\Constants\Metadata\DocumentReportMetadata;
use DMS\Constants\ProcessTypes;
use DMS\Constants\UserActionRights;
use DMS\Core\AppConfiguration;
use DMS\Core\CacheManager;
use DMS\Core\ScriptLoader;
use DMS\Helpers\ArrayHelper;
use DMS\Helpers\ArrayStringHelper;
use DMS\Helpers\DocumentFolderListHelper;
use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\LinkBuilder;
use Exception;

class DocumentsPresenter extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('Documents');

        $this->getActionNamesFromClass($this);
    }
    
    protected function duplicateDocument() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);

        $idDocument = $this->get('id');

        $idNewDocument = $app->documentRepository->duplicateDocument($idDocument, true);
        $app->documentRepository->addCommentToDocument($idDocument, 'Document has been duplicated as #' . $idNewDocument . ' by user #' . $app->user->getId(), $app->user->getId());
        
        $app->flashMessage('Document #' . $idDocument . ' duplicated as document #' . $idNewDocument);
        $app->logger->info('Document #' . $idDocument . ' duplicated by user #' . $app->user->getId() . ' as document #' . $idNewDocument);
        $app->redirect('showAll');
    }

    protected function lockDocumentForUser() {
        global $app;

        $idDocument = $this->get('id_document');
        $idUser = $this->get('id_user');
        $idFolder = $this->get('id_folder');

        $app->documentLockComponent->lockDocumentForUser($idDocument, $idUser);

        $url = [];
        if($idFolder !== NULL) {
            $url['id_folder'] = $idFolder;
        }

        $app->flashMessage('Document #' . $idDocument . ' has been locked', 'info');
        $app->redirect('showAll', $url);
    }

    protected function unlockDocumentForUser() {
        global $app;

        $idDocument = $this->get('id_document');
        $idUser = $this->get('id_user');
        $idFolder = $this->get('id_folder');

        $app->documentLockComponent->unlockDocument($idDocument, $idUser);

        $url = [];
        if($idFolder !== NULL) {
            $url['id_folder'] = $idFolder;
        }

        $app->flashMessage('Document #' . $idDocument . ' has been unlocked', 'info');
        $app->redirect('showAll', $url);
    }

    protected function processMoveToArchiveDocumentFormBulkAction() {
        global $app;

        $app->flashMessageIfNotIsset(['ids', 'archive_document'], true, ['page' => 'showAll']);

        if(!$app->actionAuthorizator->canMoveEntitiesFromToArchive()) {
            $app->flashMessage('You are not authorized to move entities to archive.', 'error');
            $app->redirect('showAll');
        }

        $ids = $this->get('ids', false);
        $archiveDocument = $this->post('archive_document');

        if(!is_array($ids)) {
            $ids = [$ids];
        }

        $app->documentModel->bulkMoveToArchiveDocument($ids, $archiveDocument);
        $app->documentMetadataHistoryModel->bulkInsertNewMetadataHistoryEntriesBasedOnDocumentMetadataArray([DocumentMetadata::ID_ARCHIVE_DOCUMENT => $archiveDocument], $ids, $app->user->getId());

        /*foreach($ids as $id) {
            $app->documentModel->moveToArchiveDocument($id, $archiveDocument);
            $app->documentMetadataHistoryModel->insertNewMetadataHistoryEntriesBasedOnDocumentMetadataArray(['id_archive_document' => $archiveDocument], $id);
        }*/

        $app->flashMessage('Documents moved to selected archive document', 'success');
        $app->redirect('showAll');
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
            $newEntityLink = LinkBuilder::createLink('showNewForm', 'New document');
        }

        $data['$LINKS$'][] = $newEntityLink;

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function generateReport() {
        global $app;

        $app->flashMessageIfNotIsset(['id_folder', 'filter', 'limit_range', 'order', 'total_count', 'file_format']);

        if(!$app->actionAuthorizator->canGenerateDocumentReports()) {
            $app->flashMessage('You are not authorized to generate document reports.', 'error');
            $app->redirect('showAll');
        }

        $idFolder = $this->get('id_folder');
        $totalCount = $this->get('total_count');
        $filter = $this->post('filter');
        $limit = $this->post('limit_range');
        $order = $this->post('order');
        $fileFormat = $this->post('file_format');

        $limit = ceil($limit);

        $qb = $app->documentModel->composeQueryStandardDocuments(false);

        if($idFolder > 0) {
            $qb->where(DocumentMetadata::ID_FOLDER . ' = ?', [$idFolder]);
        }

        if(!is_numeric($filter)) {
            switch($filter) {
                case 'shredded':
                    $qb->andWhere(DocumentMetadata::STATUS . ' = ?', [DocumentStatus::SHREDDED]);
                    break;
    
                case 'waitingForArchivation':
                    $qb->andWhere(DocumentMetadata::STATUS . ' = ?', [DocumentStatus::ARCHIVATION_APPROVED]);
                    break;
    
                case 'archived':
                    $qb->andWhere(DocumentMetadata::STATUS . ' = ?', [DocumentStatus::ARCHIVED]);
                    break;
    
                default:
                case 'all':
                    break;
            }

            if($limit < ($totalCount + 1)) {
                $qb->limit($limit);
            }
    
            if($order == 'desc') {
                $qb->orderBy(DocumentMetadata::ID, $order);
            }
        } else {
            $filterEntity = $app->filterModel->getDocumentFilterById($filter);

            $qb->setSQL($filterEntity->getSql());
    
            if(!$filterEntity->hasOrdering()) {
                if($limit < ($totalCount + 1)) {
                    $qb->limit($limit);
                }
                
                if($order == 'desc') {
                    $qb->orderBy(DocumentMetadata::ID, $order);
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

        if($rows->num_rows > ADocumentReport::SYNCHRONOUS_COUNT_CONST) {
            // use background export
            $data = [
                DocumentReportMetadata::ID_USER => $app->user->getId(),
                DocumentReportMetadata::SQL_STRING => $qb->getSQL(),
                DocumentReportMetadata::FILE_FORMAT => $fileFormat
            ];

            $app->documentModel->insertDocumentReportQueueEntry($data);

            $app->flashMessage('You requested to export more than 1000 entries. This operation will be done by background service. You will be able to find your export ' . LinkBuilder::createAdvLink(['page' => 'UserModule:DocumentReports:showAll'], 'here') . '.');
            $app->redirect('showAll');
        }

        $filename = $app->user->getId() . '_' . date('Y-m-d_H-i-s') . '_document_report.' . $fileFormat;

        $result = $app->documentReportGeneratorComponent->generateReport(null, $rows, $app->user->getId(), $fileFormat, $filename);

        if($result === FALSE) {
            die('ERROR! Documents presenter method '. __METHOD__);
        }

        $idFileStorageLocation = $result['id_file_storage_location'];
        $realFilename = $result['file_name'];

        $location = $app->fileStorageModel->getLocationById($idFileStorageLocation);
        $realServerPath = $location->getPath() . $realFilename;

        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary"); 
        header("Content-disposition: attachment; filename=\"" . basename($realServerPath) . "\"");

        readfile($realServerPath);

        unlink($filename);

        exit;
    }

    protected function showReportForm() {
        global $app;

        try {
            $app->actionAuthorizator->throwExceptionIfCannotGenerateDocumentReports();
        } catch (Exception $e) {
            $app->throwError($e->getMessage(), ['page' => 'showAll']);
        }

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/documents/new-document-form.html');

        /*if(!$app->actionAuthorizator->canGenerateDocumentReports()) {
            $app->flashMessage('You are not authorized to generate document reports.', 'error');
            $app->redirect('showAll');
        }*/

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
            $newEntityLink = LinkBuilder::createLink('showNewForm', 'New document');
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
            $newEntityLink = LinkBuilder::createLink('showNewForm', 'New document');
        }

        if(isset($_GET['id_folder'])) {
            $idFolder = $this->get('id_folder');
            if($idFolder > 0) {
                $folder = $app->folderModel->getFolderById($idFolder);
                $folderName = $folder->getName();
                $newEntityLink = LinkBuilder::createAdvLink(array('page' => 'showNewForm', 'id_folder' => $idFolder), 'New document');
            }
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
            $documentGrid = $this->internalCreateStandardDocumentGridAjax($idFolder, $filter, $page);
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
            '$SEARCH_FIELD$' => $searchField
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
            $newEntityLink = LinkBuilder::createLink('showNewForm', 'New document');
        }

        if(isset($_GET['id_folder'])) {
            $idFolder = $this->get('id_folder');

            if($idFolder > 0) {
                $folder = $app->folderModel->getFolderById($idFolder);
                $folderName = $folder->getName();
                $newEntityLink = LinkBuilder::createAdvLink(array('page' => 'showNewForm', 'id_folder' => $idFolder), 'New document');
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
            $documentGrid = $this->internalCreateStandardDocumentGridAjax($idFolder, '', $page);
        }, __METHOD__);

        $app->logger->logFunction(function() use (&$folderList, $idFolder) {
            $folderList = $this->internalCreateFolderList($idFolder, null);
        }, __METHOD__);

        $searchField = '
            <input type="text" id="q" placeholder="Search" oninput="loadDocumentsSearch(this.value, \'' . ($idFolder ?? 'null') . '\');">
        ';

        $data = array(
            '$PAGE_TITLE$' => 'Documents',
            '$DOCUMENT_GRID$' => $documentGrid,
            '$BULK_ACTION_CONTROLLER$' => '',
            '$FORM_ACTION$' => '?page=UserModule:Documents:performBulkAction' . (is_null($idFolder) ? ('&id_folder=' . $idFolder) : ''),
            '$LINKS$' => array($newEntityLink),
            '$CURRENT_FOLDER_TITLE$' => $folderName,
            '$FOLDER_LIST$' => $folderList,
            '$SEARCH_FIELD$' => $searchField
        );

        if($app->actionAuthorizator->checkActionRight(UserActionRights::GENERATE_DOCUMENT_REPORT)) {
            $data['$LINKS$'][] = '&nbsp;&nbsp;' . LinkBuilder::createAdvLink(array('page' => 'showReportForm', 'id_folder' => ($idFolder ?? 0)), 'Document report');
        }

        if($app->actionAuthorizator->checkActionRight(UserActionRights::SEE_OTHER_USERS_FILTERS) ||
           $app->actionAuthorizator->checkActionRight(UserActionRights::SEE_SYSTEM_FILTERS) ||
           $app->actionAuthorizator->checkActionRight(UserActionRights::CREATE_FILTER)) {
            $data['$LINKS$'][] = '&nbsp;&nbsp;' . LinkBuilder::createAdvLink(array('page' => 'UserModule:DocumentFilter:showFilters'), 'Filters');
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }
    
    private function internalCreateStandardDocumentGridAjax(?int $idFolder, ?string $filter, int $page = 1) {
        $code = '<script type="text/javascript">';

        if($filter != null) {
            $code .= 'loadDocumentsFilter("' . ($idFolder ?? 'null') . '", "' . $filter . '")';
        } else {
            $code .= 'loadDocuments("' . ($idFolder ?? 'null') . '", "' . $page . '");';
        }

        $code .= '</script>';
        $code .= '<div id="grid-loading"><img src="img/loading.gif" width="32" height="32"></div><table border="1"></table>';

        return $code;
    }

    protected function performBulkAction() {
        global $app;
        
        $action = $this->get('action');
        
        $cm = CacheManager::getTemporaryObject(md5($app->user->getId() . 'bulk_action' . $action));
        $ids = $cm->loadStringsFromCache();

        $cm->invalidateCache();
        unset($cm);
        
        $idFolder = -1;
        if(isset($_GET['id_folder'])) {
            $idFolder = $this->get('id_folder');
        }

        $filter = null;
        if(isset($_GET['filter'])) {
            $filter = $this->get('filter');
        }

        if($action == '-') {
            $app->redirect('showAll', ['id_folder' => $idFolder]);
        }

        if(method_exists($this, '_' . $action)) {
            return $this->{'_' . $action}($ids, $idFolder, $filter);
        } else {
            die('Method does not exist!');
        }
    }

    protected function showNewForm() {
        global $app;

        if(!$app->actionAuthorizator->canCreateDocument()) {
            $app->flashMessage('You are not authorized to create a new document.', 'error');
            $app->redirect('showAll');
        }

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

        $folders = DocumentFolderListHelper::getSelectFolderList($app->folderModel, $idFolder);

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

        $dbFSL = $app->fileStorageModel->getAllActiveFileStorageLocations(true);
        
        $fileStorageLocations = [];
        foreach($dbFSL as $loc) {
            $fsl = [
                'value' => $loc->getPath(),
                'text' => $loc->getName()
            ];

            if($loc->isDefault() && $loc->getType() == FileStorageTypes::FILES) {
                $fsl['selected'] = 'selected';
            }

            $fileStorageLocations[] = $fsl;
        }

        $fb = FormBuilder::getTemporaryObject();
        
        $name = $fb->createInput()  ->setType('text')
                                    ->setName('name')
                                    ->require();

        $fb ->setMethod('POST')->setAction('?page=UserModule:Documents:createNewDocument')->setEncType()

            ->addElement($fb->createLabel()->setText('Document name')
                                           ->setFor('name')
                                           ->setRequired())
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

            ->addElement($fb->createLabel()->setFor('file_storage_directory')
                                           ->setText('File storage'))
            ->addElement($fb->createSelect()->setName('file_storage_directory')
                                            ->addOptionsBasedOnArray($fileStorageLocations))

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
        $fileStorageDirectory = $this->post('file_storage_directory');
        
        $data[DocumentMetadata::NAME] = $this->post('name');
        $data[DocumentMetadata::ID_MANAGER] = $this->post('manager');
        $data[DocumentMetadata::STATUS] = $this->post('status');
        $data[DocumentMetadata::ID_GROUP] = $idGroup;
        $data[DocumentMetadata::ID_AUTHOR] = $app->user->getId();
        $data[DocumentMetadata::SHRED_YEAR] = $this->post('shred_year');
        $data[DocumentMetadata::AFTER_SHRED_ACTION] = $this->post('after_shred_action');
        $data[DocumentMetadata::SHREDDING_STATUS] = DocumentShreddingStatus::NO_STATUS;

        if($idFolder != '-1') {
            $data[DocumentMetadata::ID_FOLDER] = $idFolder;
        }

        if(isset($_FILES['file'])) {
            $data[DocumentMetadata::FILE] = $_FILES['file']['name'];
        }

        ArrayHelper::deleteKeysFromArray($_POST, [
            'name',
            'manager',
            'status',
            'group',
            'folder',
            'shred_year',
            'after_shred_action',
            'file_storage_directory'
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

        $fileUpload = true;
        if(isset($data[DocumentMetadata::FILE]) && !empty($data[DocumentMetadata::FILE])) {
            $fileUpload = $app->fsManager->uploadFile($_FILES['file'], $data[DocumentMetadata::FILE], $fileStorageDirectory); // filepath is converted here
        }

        if($fileUpload !== TRUE) {
            $app->flashMessage('The file you selected has unsupported extension!', 'error');
            $app->redirect('showAll');
        }
        
        // CUSTOM OPERATION DEFINITION
        // END OF CUSTOM OPERATION DEFINITION
        
        $app->documentModel->insertNewDocument($data);
        
        $idDocument = $app->documentModel->getLastInsertedDocumentForIdUser($app->user->getId())->getId();
        
        $app->documentMetadataHistoryModel->insertNewMetadataHistoryEntriesBasedOnDocumentMetadataArray($data, $idDocument, $app->user->getId());
        
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

        $app->documentMetadataHistoryModel->insertNewMetadataHistoryEntriesBasedOnDocumentMetadataArray([DocumentMetadata::ID_OFFICER => $documentIdManager], $idDocument, $app->user->getId());
        $app->documentModel->updateOfficer($idDocument, $documentIdManager);

        $app->flashMessage('Created new document', 'success');
        
        $url = 'showAll';

        if(isset($data[DocumentMetadata::ID_FOLDER])) {
            $app->redirect($url, array('id_folder' => $data[DocumentMetadata::ID_FOLDER]));
        } else {
            $app->redirect($url);
        }
    }

    private function _move_to_archive_document(array $ids, int $idFolder, ?string $filter) {
        global $app;

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/documents/new-document-form.html');

        $data = [
            '$PAGE_TITLE$' => 'Move document to archive document',
            '$NEW_DOCUMENT_FORM$' => $this->internalCreateMoveToArchiveDocumentForm($ids, $idFolder)
        ];

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function _move_from_archive_document(array $ids, int $idFolder, ?string $filter) {
        global $app;

        $app->documentModel->bulkMoveFromArchiveDocument($ids);
        $app->documentMetadataHistoryModel->bulkInsertNewMetadataHistoryEntriesBasedOnDocumentMetadataArray([DocumentMetadata::ID_ARCHIVE_DOCUMENT => 'NULL'], $ids, $app->user->getId());

        $app->flashMessage('Documents moved from the archive document', 'success');

        if($filter !== NULL) {
            $params = ['filter' => $filter];
            if($idFolder > -1) {
                $params['id_folder'] = $idFolder;
            }
            $app->redirect('showFiltered', $params);
        } else {
            $app->redirect('showAll', ($idFolder > -1) ? ['id_folder' => $idFolder] : []);
        }
    }

    private function _suggest_for_shredding(array $ids, int $idFolder, ?string $filter) {
        global $app;

        $app->documentModel->updateDocumentsBulk([DocumentMetadata::SHREDDING_STATUS => DocumentShreddingStatus::IN_APPROVAL], $ids);
        $app->documentMetadataHistoryModel->bulkInsertNewMetadataHistoryEntriesBasedOnDocumentMetadataArray([DocumentMetadata::SHREDDING_STATUS => DocumentShreddingStatus::IN_APPROVAL], $ids, $app->user->getId());

        foreach($ids as $id) {
            $app->processComponent->startProcess(ProcessTypes::SHREDDING, $id, $app->user->getId());
        }

        $app->flashMessage('Process has started', 'success');
        
        if($filter !== NULL) {
            $params = ['filter' => $filter];
            if($idFolder > -1) {
                $params['id_folder'] = $idFolder;
            }
            $app->redirect('showFiltered', $params);
        } else {
            $app->redirect('showAll', ($idFolder > -1) ? ['id_folder' => $idFolder] : []);
        }
    }

    private function _delete_documents(array $ids, int $idFolder, ?string $filter) {
        global $app;

        $result = true;
        foreach($ids as $id) {
            if($result === TRUE) {
                $result = $app->processComponent->startProcess(ProcessTypes::DELETE, $id, $app->user->getId());
            }
        }

        if(count($ids) > 1) {
            // multiple
            if($result === TRUE) {
                $app->flashMessage('Processes have started', 'success');
            } else {
                $app->flashMessage('Some processes have not started', 'warn');
            }
        } else {
            if($result === TRUE) {
                $app->flashMessage('Process has started', 'success');
            } else {
                $app->flashMessage('Process could not be started', 'error');
            }
        }

        if($filter !== NULL) {
            $params = ['filter' => $filter];
            if($idFolder > -1) {
                $params['id_folder'] = $idFolder;
            }
            $app->redirect('showFiltered', $params);
        } else {
            $app->redirect('showAll', ($idFolder > -1) ? ['id_folder' => $idFolder] : []);
        }
    }

    private function _decline_archivation(array $ids, int $idFolder, ?string $filter) {
        global $app;

        $app->documentModel->updateDocumentsBulk([DocumentMetadata::STATUS => DocumentStatus::ARCHIVATION_DECLINED], $ids);
        $app->documentMetadataHistoryModel->bulkInsertNewMetadataHistoryEntriesBasedOnDocumentMetadataArray([DocumentMetadata::STATUS => DocumentStatus::ARCHIVATION_DECLINED], $ids, $app->user->getId());

        if(count($ids) == 1) {
            $app->flashMessage('Declined archivation for selected document', 'success');
        } else {
            $app->flashMessage('Declined archivation for selected documents', 'success');
        }

        if($filter !== NULL) {
            $params = ['filter' => $filter];
            if($idFolder > -1) {
                $params['id_folder'] = $idFolder;
            }
            $app->redirect('showFiltered', $params);
        } else {
            $app->redirect('showAll', ($idFolder > -1) ? ['id_folder' => $idFolder] : []);
        }
    }

    private function _approve_archivation(array $ids, int $idFolder, ?string $filter) {
        global $app;

        $app->documentModel->updateDocumentsBulk([DocumentMetadata::STATUS => DocumentStatus::ARCHIVATION_APPROVED], $ids);
        $app->documentMetadataHistoryModel->bulkInsertNewMetadataHistoryEntriesBasedOnDocumentMetadataArray([DocumentMetadata::STATUS => DocumentStatus::ARCHIVATION_APPROVED], $ids, $app->user->getId());

        if(count($ids) == 1) {
            $app->flashMessage('Approved archivation for selected document', 'success');
        } else {
            $app->flashMessage('Approved archivation for selected documents', 'success');
        }

        if($filter !== NULL) {
            $params = ['filter' => $filter];
            if($idFolder > -1) {
                $params['id_folder'] = $idFolder;
            }
            $app->redirect('showFiltered', $params);
        } else {
            $app->redirect('showAll', ($idFolder > -1) ? ['id_folder' => $idFolder] : []);
        }
    }

    private function _archive(array $ids, int $idFolder, ?string $filter) {
        global $app;

        $app->documentModel->updateDocumentsBulk([DocumentMetadata::STATUS => DocumentStatus::ARCHIVED], $ids);
        $app->documentMetadataHistoryModel->bulkInsertNewMetadataHistoryEntriesBasedOnDocumentMetadataArray([DocumentMetadata::STATUS => DocumentStatus::ARCHIVED], $ids, $app->user->getId());

        if(count($ids) == 1) {
            $app->flashMessage('Archived selected document', 'success');
        } else {
            $app->flashMessage('Archived selected documents', 'success');
        }

        if($filter !== NULL) {
            $params = ['filter' => $filter];
            if($idFolder > -1) {
                $params['id_folder'] = $idFolder;
            }
            $app->redirect('showFiltered', $params);
        } else {
            $app->redirect('showAll', ($idFolder > -1) ? ['id_folder' => $idFolder] : []);
        }
    }

    private function internalCreateFolderList(?int $idFolder, ?string $filter) {
        global $app;

        $createLink = function(string $action, string $text, ?int $idFolder, ?string $filter) {
            $url = array(
                'page' => $action
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
        
        $dflh = new DocumentFolderListHelper($app->folderModel);
        
        $list = array(
            'null1' => '&nbsp;&nbsp;' . $dflh->createFolderLink($link, 'Main folder' . (AppConfiguration::getGridMainFolderHasAllDocuments() ? ' (all documents)' : ''), null, $filter) . '<br>',
            'null2' => '<hr>'
        );
        
        $folders = $app->folderModel->getAllFolders();

        foreach($folders as $folder) {
            $dflh->createFolderList($folder, $list, 0, $filter, $link, $folders);
        }

        if(count($folders) > 0) {
            $list['null3'] = '<hr>';
        }

        $list['null4'] = '&nbsp;&nbsp;' . LinkBuilder::createLink('showSharedWithMe', 'Documents shared with me');

        return ArrayStringHelper::createUnindexedStringFromUnindexedArray($list);
    }

    private function internalCreateSharedWithMeDocumentGrid(int $page) {
        $code = '<script type="text/javascript">';
        $code .= 'loadDocumentsSharedWithMe("' . $page . '");';
        $code .= '</script>';
        $code .= '<div id="grid-loading"><img src="img/loading.gif" width="32" height="32"></div><table border="1"></table>';

        return $code;
    }

    private function internalCreateCustomFilterDocumentsGrid(int $idFilter) {
        $code = '<script type="text/javascript">';
        $code .= 'loadDocumentsCustomFilter("' . $idFilter . '");';
        $code .= '</script>';
        $code .= '<div id="grid-loading"><img src="img/loading.gif" width="32" height="32"></div><table border="1"></table>';

        return $code;
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

        $documentPageControl = 'Total documents: ' . $documentCount . ' | ';

        if($documentCount > AppConfiguration::getGridSize()) {
            if($pageCheck * AppConfiguration::getGridSize() >= $documentCount) {
                $documentPageControl .= (1 + ($page * AppConfiguration::getGridSize()));
            } else {
                $from = 1 + ($pageCheck * AppConfiguration::getGridSize());
                $to = AppConfiguration::getGridSize() + ($pageCheck * AppConfiguration::getGridSize());

                if($to > $documentCount) {
                    $to = $documentCount;
                }

                $documentPageControl .= $from . '-' . $to;
            }
        } else {
            $documentPageControl = 'Total documents: ' .  $documentCount;
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

        $extensions = ADocumentReport::SUPPORTED_EXTENSIONS;
        $extensionArray = [];
        foreach($extensions as $extCode => $extName) {
            $extensionArray[] = [
                'value' => $extCode,
                'text' => $extName
            ];
        }

        $fb
            ->setMethod('POST')->setAction('?page=UserModule:Documents:generateReport&id_folder=' . $idFolder . '&total_count=' . $count)

            ->addElement($fb->createLabel()->setText('Filter')->setFor('filter'))
            ->addElement($fb->createSelect()->setName('filter')->addOptionsBasedOnArray($filterArray))

            ->addElement($fb->createLabel()->setText('Limit')->setFor('limit_range'))
            ->addElement($fb->createLabel()->setText('')->setId('limit_text'))
            ->addElement($fb->createInput()->setType('range')->setMin('1')->setMax(($count + 1))->setName('limit_range')->setStep($step)->setValue((ceil($count / 2))))

            ->addElement($fb->createLabel()->setText('Order')->setFor('order'))
            ->addElement($fb->createSelect()->setName('order')->addOptionsBasedOnArray($orderArray))

            ->addElement($fb->createLabel()->setText('File format')->setFor('file_format'))
            ->addElement($fb->createSelect()->setName('file_format')->addOptionsBasedOnArray($extensionArray))

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

        $fb ->setMethod('POST')->setAction('?page=UserModule:Documents:processMoveToArchiveDocumentFormBulkAction' . $urlIds . (($idFolder > -1) ? ('id_folder=' . $idFolder) : ''))
            
            ->addElement($fb->createLabel()->setText('Archive document')->setFor('archive_document'))
            ->addElement($fb->createSelect()->setName('archive_document')->addOptionsBasedOnArray($archiveDocumentsArr))

            ->addElement($fb->createSubmit('Move'))
        ;

        return $fb->build();
    }
}

?>