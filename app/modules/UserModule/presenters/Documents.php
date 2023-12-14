<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\DocumentAfterShredActions;
use DMS\Constants\DocumentShreddingStatus;
use DMS\Constants\DocumentStatus;
use DMS\Constants\FlashMessageTypes;
use DMS\Constants\ProcessTypes;
use DMS\Core\ScriptLoader;
use DMS\Core\TemplateManager;
use DMS\Entities\Folder;
use DMS\Helpers\ArrayStringHelper;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;
use DMS\Panels\Panels;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\LinkBuilder;

class Documents extends APresenter {
    private string $name;
    private TemplateManager $templateManager;
    private IModule $module;

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

    protected function showSharedWithMe() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/documents/document-grid.html');

        $idFolder = null;
        $folderName = 'Main folder';
        $newEntityLink = LinkBuilder::createLink('UserModule:Documents:showNewForm', 'New document');

        $documentGrid = '';
        $folderList = '';
        
        $app->logger->logFunction(function() use (&$documentGrid) {
            $documentGrid = $this->internalCreateSharedWithMeDocumentGrid();
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
            '$FORM_ACTION$' => '?page=UserModule:Documents:performBulkAction',
            '$NEW_DOCUMENT_LINK$' => $newEntityLink,
            '$CURRENT_FOLDER_TITLE$' => $folderName,
            '$FOLDER_LIST$' => $folderList,
            '$SEARCH_FIELD$' => $searchField,
            '$DOCUMENT_PANEL$' => ''
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showFiltered() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/documents/document-grid.html');

        $idFolder = null;
        $folderName = 'Main folder';
        $newEntityLink = LinkBuilder::createLink('UserModule:Documents:showNewForm', 'New document');

        if(isset($_GET['id_folder'])) {
            $idFolder = htmlspecialchars($_GET['id_folder']);
            $folder = $app->folderModel->getFolderById($idFolder);
            $folderName = $folder->getName();
            $newEntityLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Documents:showNewForm', 'id_folder' => $idFolder), 'New document');
        }

        $documentGrid = '';
        $folderList = '';

        $filter = null;

        if(isset($_GET['filter'])) {
            $filter = htmlspecialchars($_GET['filter']);
        }
        
        $app->logger->logFunction(function() use (&$documentGrid, $idFolder, $filter) {
            $documentGrid = $this->internalCreateStandardDocumentGrid($idFolder, $filter);
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
            '$FORM_ACTION$' => '?page=UserModule:Documents:performBulkAction',
            '$NEW_DOCUMENT_LINK$' => $newEntityLink,
            '$CURRENT_FOLDER_TITLE$' => $folderName,
            '$FOLDER_LIST$' => $folderList,
            '$SEARCH_FIELD$' => $searchField,
            '$DOCUMENT_PANEL$' => Panels::createDocumentsPanel()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showAll() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/documents/document-grid.html');

        $idFolder = null;
        $folderName = 'Main folder';
        $newEntityLink = LinkBuilder::createLink('UserModule:Documents:showNewForm', 'New document');

        if(isset($_GET['id_folder'])) {
            $idFolder = htmlspecialchars($_GET['id_folder']);
            $folder = $app->folderModel->getFolderById($idFolder);
            $folderName = $folder->getName();
            $newEntityLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Documents:showNewForm', 'id_folder' => $idFolder), 'New document');
        }

        $documentGrid = '';
        $folderList = '';
        
        $app->logger->logFunction(function() use (&$documentGrid, $idFolder) {
            $documentGrid = $this->internalCreateStandardDocumentGrid($idFolder, null);
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
            '$FORM_ACTION$' => '?page=UserModule:Documents:performBulkAction',
            '$NEW_DOCUMENT_LINK$' => $newEntityLink,
            '$CURRENT_FOLDER_TITLE$' => $folderName,
            '$FOLDER_LIST$' => $folderList,
            '$SEARCH_FIELD$' => $searchField,
            '$DOCUMENT_PANEL$' => Panels::createDocumentsPanel()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateStandardDocumentGrid(?int $idFolder, ?string $filter) {
        $code = '<script type="text/javascript">';

        if($filter != null) {
            $code .= 'loadDocumentsFilter("' . ($idFolder ?? 'null') . '", "' . $filter . '")';
        } else {
            $code .= 'loadDocuments("' . ($idFolder ?? 'null') . '");';
        }

        $code .= '</script>';
        $code .= '<table border="1"><img id="documents-loading" style="position: fixed; top: 50%; left: 49%;" src="img/loading.gif" width="32" height="32"></table>';

        return $code;
    }

    protected function performBulkAction() {
        global $app;

        if(!isset($_GET['select'])) {
            $app->redirect('UserModule:Documents:showAll');
        }

        $ids = $_GET['select'];
        $action = htmlspecialchars($_GET['action']);

        if($action == '-') {
            $app->redirect('UserModule:Documents:showAll');
        }

        if(method_exists($this, '_' . $action)) {
            $this->{'_' . $action}($ids);
        } else {
            die('Method does not exist!');
        }
    }

    protected function showNewForm() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/documents/new-document-form.html');

        $idFolder = null;

        if(isset($_GET['id_folder'])) {
            $idFolder = htmlspecialchars($_GET['id_folder']);
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
    
                $name = $cm->getName();
                $text = $cm->getText();
                $values = $app->metadataModel->getAllValuesForIdMetadata($cm->getId());
    
                $options = [];
                foreach($values as $v) {
                    $options[] = array(
                        'value' => $v->getValue(),
                        'text' => $v->getName()
                    );
                }
    
                $metadata[$name] = array('text' => $text, 'options' => $options, 'type' => $cm->getInputType(), 'length' => $cm->getInputLength());
            }
        }

        $fb = FormBuilder::getTemporaryObject();

        $fb ->setMethod('POST')->setAction('?page=UserModule:Documents:createNewDocument')->setEncType()

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

        $idGroup = htmlspecialchars($_POST['group']);
        $idFolder = htmlspecialchars($_POST['folder']);
        
        $data['name'] = htmlspecialchars($_POST['name']);
        $data['id_manager'] = htmlspecialchars($_POST['manager']);
        $data['status'] = htmlspecialchars($_POST['status']);
        $data['id_group'] = htmlspecialchars($idGroup);
        $data['id_author'] = $app->user->getId();
        $data['shred_year'] = htmlspecialchars($_POST['shred_year']);
        $data['after_shred_action'] = htmlspecialchars($_POST['after_shred_action']);
        $data['shredding_status'] = DocumentShreddingStatus::NO_STATUS;

        if($idFolder != '-1') {
            $data['id_folder'] = $idFolder;
        }

        if(isset($_FILES['file'])) {
            $data['file'] = $_FILES['file']['name'];
        }

        unset($_POST['name']);
        unset($_POST['manager']);
        unset($_POST['status']);
        unset($_POST['group']);
        unset($_POST['folder']);
        unset($_POST['shred_year']);
        unset($_POST['after_shred_action']);

        $customMetadata = $_POST;

        $data = array_merge($data, $customMetadata);

        if(isset($data['file']) && !empty($data['file'])) {
            $app->fsManager->uploadFile($_FILES['file'], $data['file']);
        }
        
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
            die('Document group has no manager!');
        }

        $app->documentModel->updateOfficer($idDocument, $documentIdManager);

        $app->redirect('UserModule:Documents:showAll');
    }

    private function _suggest_for_shredding(array $ids) {
        global $app;

        foreach($ids as $id) {
            $app->documentModel->updateDocument($id, array(
                'shredding_status' => DocumentShreddingStatus::IN_APPROVAL
            ));
            $app->processComponent->startProcess(ProcessTypes::SHREDDING, $id, $app->user->getId());
        }

        echo('<script type="text/javascript">alert("Process has started"); location.href = "?page=UserModule:Documents:showAll";</script>');
    }

    private function _delete_documents(array $ids) {
        global $app;

        foreach($ids as $id) {
            $app->processComponent->startProcess(ProcessTypes::DELETE, $id, $app->user->getId());
        }

        echo('<script type="text/javascript">alert("Process has started"); location.href = "?page=UserModule:Documents:showAll";</script>');
    }

    private function _decline_archivation(array $ids) {
        global $app;
        
        foreach($ids as $id) {
            if($app->documentAuthorizator->canDeclineArchivation($id)) {
                $app->documentModel->updateStatus($id, DocumentStatus::ARCHIVATION_DECLINED);
            }
        }

        $app->redirect('UserModule:Documents:showAll');
    }

    private function _approve_archivation(array $ids) {
        global $app;

        foreach($ids as $id) {
            if($app->documentAuthorizator->canApproveArchivation($id)) {
                $app->documentModel->updateStatus($id, DocumentStatus::ARCHIVATION_APPROVED);
            }
        }

        $app->redirect('UserModule:Documents:showAll');
    }

    private function _archive(array $ids) {
        global $app;

        foreach($ids as $id) {
            if($app->documentAuthorizator->canArchive($id)) {
                $app->documentModel->updateStatus($id, DocumentStatus::ARCHIVED);
            }
        }

        $app->redirect('UserModule:Documents:showAll');
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
            'null1' => '&nbsp;&nbsp;' . $createLink($link, 'Main folder (all files)', null, $filter) . '<br>',
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
        $folderLink = $linkCreationMethod($link, $folder->getName(), $folder->getId(), $filter) /*LinkBuilder::createAdvLink(array('page' => 'UserModule:Documents:showAll', 'id_folder' => $folder->getId()), $folder->getName())*/;
        
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

    private function internalCreateSharedWithMeDocumentGrid() {
        return '
            <script type="text/javascript">
            loadDocumentsSharedWithMe();
            </script> 
            <table border="1"><img id="documents-loading" style="position: fixed; top: 50%; left: 49%;" src="img/loading.gif" width="32" height="32"></table>
        ';
    }
}

?>