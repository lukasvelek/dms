<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\CacheCategories;
use DMS\Constants\DocumentAfterShredActions;
use DMS\Constants\DocumentLockStatus;
use DMS\Constants\DocumentLockType;
use DMS\Constants\DocumentRank;
use DMS\Constants\DocumentShreddingStatus;
use DMS\Constants\Metadata\DocumentMetadata;
use DMS\Constants\ProcessTypes;
use DMS\Constants\UserActionRights;
use DMS\Core\AppConfiguration;
use DMS\Core\CacheManager;
use DMS\Core\CypherManager;
use DMS\Core\ScriptLoader;
use DMS\Entities\Document;
use DMS\Entities\DocumentLockEntity;
use DMS\Entities\DocumentMetadataHistoryEntity;
use DMS\Helpers\ArrayHelper;
use DMS\Helpers\DatetimeFormatHelper;
use DMS\Helpers\TextHelper;
use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

class SingleDocumentPresenter extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('SingleDocument', 'Document');

        $this->getActionNamesFromClass($this);
    }

    protected function showLockHistory() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);

        $idDocument = $this->get('id');

        $template = $this->loadTemplate(__DIR__ . '/templates/documents/document-metadata-history-grid.html');

        $data = [
            '$PAGE_TITLE$' => 'Locking history for document #' . $idDocument,
            '$LINKS$' => [],
            '$METADATA_GRID$' => $this->internalCreateLockHistoryGrid($idDocument)
        ];

        $data['$LINKS$'][] = LinkBuilder::createAdvLink(['page' => 'showInfo', 'id' => $idDocument], '&larr;');

        $this->fill($data, $template);
        
        return $template;
    }

    protected function showMetadataHistory() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);

        $idDocument = $this->get('id');

        $template = $this->loadTemplate(__DIR__ . '/templates/documents/document-metadata-history-grid.html');

        $data = [
            '$PAGE_TITLE$' => 'Metadata history for document #' . $idDocument,
            '$LINKS$' => [],
            '$METADATA_GRID$' => $this->internalCreateMetadataHistoryGrid($idDocument)
        ];

        $data['$LINKS$'][] = LinkBuilder::createAdvLink(['page' => 'showInfo', 'id' => $idDocument], '&larr;');

        $this->fill($data, $template);

        return $template;
    }

    protected function shareDocument() {
        global $app;

        $app->flashMessageIfNotIsset(['id_document', 'user', 'date_from', 'date_to']);

        $idDocument = $this->get('id_document');
        $idUser = $this->post('user');
        $dateFrom = $this->post('date_from');
        $dateTo = $this->post('date_to');
        $idAuthor = $app->user->getId();
        $hash = CypherManager::createCypher(64);

        if(strtotime($dateFrom) > strtotime($dateTo)) {
            ScriptLoader::alert('Start date cannot be later than end date!', array('page' => 'UserModule:SingleDocument:showShare', 'id' => $idDocument));
        }

        $data = array(
            'id_document' => $idDocument,
            'id_author' => $idAuthor,
            'id_user' => $idUser,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'hash' => $hash
        );

        $app->documentModel->insertDocumentSharing($data);

        $app->redirect('Documents:showAll');
    }

    protected function showShare() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);

        $idDocument = $this->get('id');
        $document = $app->documentModel->getDocumentById($idDocument);

        if(is_null($document)) {
            $app->flashMessage('Document #' . $idDocument . ' does not exist!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/documents/document-sharing-grid.html');

        $data = array(
            '$PAGE_TITLE$' => 'Share document <i>' . $document->getName() . '</i>',
            '$SHARING_GRID$' => $this->internalCreateDocumentSharingForm($document)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function deleteComment() {
        global $app;

        $app->flashMessageIfNotIsset(['id_comment', 'id_document']);

        $idComment = $this->get('id_comment');
        $idDocument = $this->get('id_document');

        $app->documentCommentModel->deleteComment($idComment);

        $app->logger->info('Deleted comment #' . $idComment, __METHOD__);

        $app->redirect('showInfo', array('id' => $idDocument));
    }

    protected function askToDeleteComment() {
        global $app;

        $app->flashMessageIfNotIsset(['id_comment', 'id_document']);

        $idComment = $this->get('id_comment');
        $idDocument = $this->get('id_document');

        $urlConfirm = array(
            'page' => 'UserModule:SingleDocument:deleteComment',
            'id_comment' => $idComment,
            'id_document' => $idDocument
        );

        $urlClose = array(
            'page' => 'UserModule:SingleDocument:showInfo',
            'id' => $idDocument
        );

        $code = ScriptLoader::confirmUser('Do you want to delete the comment?', $urlConfirm, $urlClose);

        return $code;
    }

    protected function saveComment() {
        global $app;

        $app->flashMessageIfNotIsset(['id_comment', 'text']);

        $idDocument = $this->get('id_document');
        $idAuthor = $app->user->getId();
        $text = $this->post('text');

        $data = array(
            'id_document' => $idDocument,
            'id_author' => $idAuthor,
            'text' => $text
        );

        $app->documentCommentModel->insertComment($data);

        $app->redirect('showInfo', array('id' => $idDocument));
    }

    protected function showInfo() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);

        $id = $this->get('id');
        $document = $app->documentModel->getDocumentById($id);

        if(is_null($document)) {
            $app->flashMessage('Document #' . $id . ' does not exist!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/documents/single-document-grid.html');

        $documentGrid = '';
        $documentComments = '';

        $app->logger->logFunction(function() use (&$documentGrid, $document) {
            $documentGrid = $this->internalCreateDocumentInfoGrid($document);
        });

        $app->logger->logFunction(function() use (&$documentComments, $document) {
            $documentComments = $this->internalCreateDocumentComments($document);
        });

        $data = array(
            '$PAGE_TITLE$' => 'Document <i>' . $document->getName() . '</i>',
            '$DOCUMENT_GRID$' => $documentGrid,
            '$NEW_COMMENT_FORM$' => $this->internalCreateNewDocumentCommentForm($document),
            '$DOCUMENT_COMMENTS$' => $documentComments,
            '$LINKS$' => []
        );

        $backUrl = ['page' => 'Documents:showAll'];

        if($document->getIdFolder() !== NULL) {
            $backUrl['id_folder'] = $document->getIdFolder();
        }

        $data['$LINKS$'][] = LinkBuilder::createAdvLink($backUrl, '&larr;') . '&nbsp;&nbsp;';
        $data['$LINKS$'][] = LinkBuilder::createAdvLink(['page' => 'showMetadataHistory', 'id' => $id], 'Metadata history') . '&nbsp;&nbsp;';
        $data['$LINKS$'][] = LinkBuilder::createAdvLink(['page' => 'showLockHistory', 'id' => $id], 'Locking history');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showEdit() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);

        $id = $this->get('id');
        $document  = $app->documentModel->getDocumentById($id);

        if(is_null($document)) {
            $app->flashMessage('Document #' . $id . ' does not exist!', 'error');
            $app->redirect($app::URL_HOME_PAGE);
        }

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/documents/new-document-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'Edit document <i>' . $document->getName() . '</i>',
            '$NEW_DOCUMENT_FORM$' => $this->internalCreateDocumentEditForm($document)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function updateDocument() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);

        $id = $this->get('id');

        $data = [];

        $idGroup = $this->post('group');
        $idFolder = $this->post('folder');
        
        $data[DocumentMetadata::NAME] = $this->post('name');
        $data[DocumentMetadata::ID_MANAGER] = $this->post('manager');
        $data[DocumentMetadata::STATUS] = $this->post('status');
        $data[DocumentMetadata::ID_GROUP] = $idGroup;

        if($idFolder != '-1') {
            $data[DocumentMetadata::ID_FOLDER] = $idFolder;
        }

        unset($_POST['name']);
        unset($_POST['manager']);
        unset($_POST['status']);
        unset($_POST['group']);
        unset($_POST['folder']);

        ArrayHelper::deleteKeysFromArray($_POST, [
            'name',
            'manager',
            'status',
            'group',
            'folder'
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

        $app->documentModel->updateDocument($id, $data);
        $app->documentMetadataHistoryModel->insertNewMetadataHistoryEntriesBasedOnDocumentMetadataArray($data, $id, $app->user->getId());

        $app->logger->info('Updated document #' . $id, __METHOD__);

        $app->redirect('showInfo', array('id' => $id));
    }

    private function internalCreateDocumentEditForm(Document $document) {
        global $app;

        $managers = array(
            array(
                'value' => $app->user->getId(),
                'text' => '&lt;&lt;Me&gt;&gt;'
            )
        );

        $users = $app->userModel->getAllUsers();

        foreach($users as $user) {
            $manager = array(
                'value' => $user->getId(),
                'text' => $user->getFullname()
            );

            if($document->getIdManager() == $user->getId()) {
                $manager['selected'] = 'selected';
            }

            $managers[] = $manager;
        }

        $statusMetadata = $app->metadataModel->getMetadataByName('status', 'documents');
        $dbStatuses = $app->metadataModel->getAllValuesForIdMetadata($statusMetadata->getId());

        $statuses = [];
        foreach($dbStatuses as $dbs) {
            $status = array(
                'value' => $dbs->getValue(),
                'text' => $dbs->getName()
            );

            if($document->getStatus() == $dbs->getValue()) {
                $status['selected'] = 'selected';
            }

            $statuses[] = $status;
        }

        $dbGroups = $app->groupModel->getAllGroups();

        $groups = [];
        foreach($dbGroups as $dbg) {
            $group = array(
                'value' => $dbg->getId(),
                'text' => $dbg->getName()
            );

            if($document->getIdGroup() == $dbg->getId()) {
                $group['selected'] = 'selected';
            }

            $groups[] = $group;
        }

        $rankMetadata = $app->metadataModel->getMetadataByName('rank', 'documents');
        $dbRanks = $app->metadataModel->getAllValuesForIdMetadata($rankMetadata->getId());

        $ranks = [];
        foreach($dbRanks as $dbr) {
            $rank = array(
                'value' => $dbr->getValue(),
                'text' => $dbr->getName()
            );

            if($document->getRank() == $dbr->getValue()) {
                $rank['selected'] = 'selected';
            }

            $ranks[] = $rank;
        }

        $dbFolders = $app->folderModel->getAllFolders();

        $folders = [];
        $folders[] = array(
            'value' => '-1',
            'text' => '-'
        );

        foreach($dbFolders as $dbf) {
            $text = $dbf->getName();

            for($i = 0; $i < $dbf->getNestLevel(); $i++) {
                $text = '&nbsp;&nbsp;' . $text;
            }

            $folder = array(
                'value' => $dbf->getId(),
                'text' => $text
            );

            if($document->getIdFolder() == $dbf->getId()) {
                $folder['selected'] = 'selected';
            }

            $folders[] = $folder;
        }

        $customMetadata = $app->metadataModel->getAllMetadataForTableName('documents');
        // name = array('text' => 'text', 'options' => 'options from metadata_values')
        $metadata = [];

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
                    $option = array(
                        'value' => $value,
                        'text' => $vtext
                    );
                    if(!is_null($document->getMetadata($name)) && ($document->getMetadata($name) == $value)) {
                        $option['selected'] = 'selected';
                    }

                    $options[] = $option;
                }

                $metadata[$name] = array('text' => $text, 'options' => $options, 'type' => 'select', 'length' => $cm->getInputLength());
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
                
                    if(!is_null($document->getMetadata($name)) && ($document->getMetadata($name) == $v->getValue())) {
                        $option['selected'] = 'selected';
                        $hasDefault = true;
                    }

                    $options[] = $option;
                }

                if($hasDefault === FALSE) {
                    $options[0]['selected'] = 'selected';
                }

                $metadata[$name] = array('text' => $text, 'options' => $options, 'type' => $cm->getInputType(), 'length' => $cm->getInputLength());
            }
        }

        $fb = FormBuilder::getTemporaryObject();

        $fb ->setMethod('POST')->setAction('?page=UserModule:SingleDocument:updateDocument&id=' . $document->getId())
            ->addElement($fb->createLabel()->setText('Document name')
                                           ->setFor('name')
                                           ->setRequired())
            ->addElement($fb->createInput()->setType('text')
                                           ->setName('name')
                                           ->setValue($document->getName())
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

        return $fb->build();
    }

    private function internalCreateDocumentInfoGrid(Document $document) {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $status = '-';
        $statusMetadata = $app->metadataModel->getMetadataByName('status', 'documents');
        $dbStatuses = $app->metadataModel->getAllValuesForIdMetadata($statusMetadata->getId());
        
        $rank = DocumentRank::$texts[$document->getRank()];

        foreach($dbStatuses as $dbs) {
            if($dbs->getValue() == $document->getStatus()) {
                $status = $dbs->getName();
            }
        }

        $folder = '-';

        if($document->getIdFolder() != null) {
            $folder = $this->createFolderLink($document->getIdFolder());
        }

        $dateCreated = $document->getDateCreated();
        $dateCreated = DatetimeFormatHelper::formatDateByUserDefaultFormat($dateCreated, $app->user);
        $dateUpdated = $document->getDateUpdated();
        $dateUpdated = DatetimeFormatHelper::formatDateByUserDefaultFormat($dateUpdated, $app->user);
        $form = 'Physical';
        $lockedBy = TextHelper::colorText('Unlock', 'green');

        $lock = $app->documentLockComponent->isDocumentLocked($document->getId());

        if($lock !== FALSE) {
            if($lock->getType() == DocumentLockType::USER_LOCK) {
                $user = $app->userModel->getUserById($lock->getIdUser());

                $text = TextHelper::colorText($user->getFullname(), DocumentLockType::$colors[$lock->getType()]);

                $lockedBy = LinkBuilder::createAdvLink(['page' => 'UserModule:Users:showProfile', 'id' => $lock->getIdUser()], $text);
            } else {
                $process = $app->processModel->getProcessById($lock->getIdProcess());

                $text = TextHelper::colorText('#' . $process->getId() . ' - ' . ProcessTypes::$texts[$process->getType()], DocumentLockType::$colors[$lock->getType()]);

                $lockedBy = LinkBuilder::createAdvLink(['page' => 'UserModule:SingleProcess:showProcess', 'id' => $lock->getIdProcess()], $text);
            }
        }

        if($document->getFile() !== NULL) {
            $form = 'Electronic';
        }

        $data = array(
            'Name' => $document->getName(),
            'Author' => $this->createUserLink($document->getIdAuthor()),
            'Manager' => $this->createUserLink($document->getIdManager()),
            'Status' => $status,
            'Rank' => $rank,
            'Group' => $this->createGroupLink($document->getIdGroup()),
            'Deleted?' => $document->getIsDeleted() ? 'Yes' : 'No',
            'Folder' => $folder,
            'Date created' => $dateCreated,
            'Date updated' => $dateUpdated,
            'Form' => $form,
            'Document lock' => $lockedBy
        );

        foreach($document->getMetadata() as $k => $v) {
            $m = $app->metadataModel->getMetadataByName($k, 'documents');
            
            if($m->getInputType() == 'select_external') {
                $mValues = $app->externalEnumComponent->getEnumByName($m->getSelectExternalEnumName())->getValues();

                if($v === NULL) {
                    $data[$m->getText()] = '-';
                } else {
                    $data[$m->getText()] = $mValues[$v];
                }
            } else {
                $mValues = $app->metadataModel->getAllValuesForIdMetadata($m->getId());
            
                $vText = '-';

                if(empty($mValues)) {
                    // not select
                    $vText = $v;

                    if($m->getInputType() == 'boolean') {
                        $checkboxTrue = '<input type="checkbox" checked disabled>';
                        $checkboxFalse = '<input type="checkbox" disabled>';

                        $vText = $v ? $checkboxTrue : $checkboxFalse;
                    }
                } else {
                    foreach($mValues as $mv) {
                        if($mv->getValue() == $v) {
                            $vText = $mv->getName();
                        }
                    }
                }

                $data[$m->getText()] = $vText;
            }
        }

        foreach($data as $k => $v) {
            if(is_null($v)) {
                $v = '-';
            }

            $row = $tb->createRow();

            $row->addCol($tb->createCol()->setText($k)->setBold())
                ->addCol($tb->createCol()->setText($v));

            $tb->addRow($row);
        }

        $tb->addRow($tb->createRow()->addCol($tb->createCol()->setColspan('2')->setText('Shredding info')->setBold()));

        $shreddingData = array(
            'Shred year' => $document->getShredYear(),
            'Action after shredding' => DocumentAfterShredActions::$texts[$document->getAfterShredAction()],
            'Shredding status' => DocumentShreddingStatus::$texts[$document->getShreddingStatus()]
        );

        foreach($shreddingData as $k => $v) {
            if(is_null($v)) {
                $v = '-';
            }

            $row = $tb->createRow();

            $row->addCol($tb->createCol()->setText($k)->setBold())
                ->addCol($tb->createCol()->setText($v));

            $tb->addRow($row);
        }

        $tb->addRow($tb->createRow()->addCol($tb->createCol()->setColspan('2')->setText('Process')->setBold()));

        $data = [];

        if(!is_null($document->getIdOfficer())) {
            $data['Current officer'] = $this->createUserLink($document->getIdOfficer());

            $process = $app->processModel->getProcessForIdDocument($document->getId());

            if($process !== NULL) {
                $workflow = $process->getWorkflow();

                $i = 1;
                foreach($workflow as $wf) {
                    if($wf === NULL) {
                        break;
                    }

                    $data['Workflow #' . $i] = $this->createUserLink($wf);

                    $i++;
                }
            }
        }

        foreach($data as $k => $v) {
            $row = $tb->createRow();

            $row->addCol($tb->createCol()->setText($k)->setBold())
                ->addCol($tb->createCol()->setText($v));

            $tb->addRow($row);
        }

        return $tb->build();
    }

    private function createUserLink(int $id) {
        global $app;

        $ucm = new CacheManager(CacheCategories::USERS, AppConfiguration::getLogDir(), AppConfiguration::getCacheDir());

        $cacheUser = $ucm->loadUserByIdFromCache($id);

        if(is_null($cacheUser)) {
            $user = $app->userModel->getUserById($id);

            $ucm->saveUserToCache($user);
        } else {
            $user = $cacheUser;
        }

        return LinkBuilder::createAdvLink(array('page' => 'Users:showProfile', 'id' => $id), $user->getFullname());
    }
    
    private function createGroupLink(int $id) {
        global $app;

        $group = $app->groupModel->getGroupById($id);

        return LinkBuilder::createAdvLink(array('page' => 'Groups:showUsers', 'id' => $id), $group->getName());
    }

    private function createFolderLink(int $id) {
        global $app;

        $folder = $app->folderModel->getFolderById($id);

        return LinkBuilder::createAdvLink(array('page' => 'Settings:showFolders', 'id' => $id), $folder->getName());
    }

    private function internalCreateNewDocumentCommentForm(Document $document) {
        global $app;

        $canDelete = $app->actionAuthorizator->checkActionRight(UserActionRights::DELETE_COMMENTS) ? '1' : '0';

        return '<!--<script type="text/javascript" src="js/DocumentAjaxComment.js"></script>-->
        <textarea name="text" id="text" maxlength="32768" required></textarea><br><br>
        <button onclick="sendDocumentComment(' . $app->user->getId() . ', ' . $document->getId() . ', ' . $canDelete . ')">Send</button>
        ';
    }

    private function internalCreateDocumentComments(Document $document) {
        global $app;
        
        $canDelete = $app->actionAuthorizator->checkActionRight(UserActionRights::DELETE_COMMENTS) ? '1' : '0';

        return '
        <img id="comments-loading" style="position: fixed; top: 50%; left: 49%;" src="img/loading.gif" width="32" height="32">
        <script type="text/javascript">
            $(document).on("load", showCommentsLoading())
                       .ready(loadDocumentComments("' . $document->getId() . '", "' . $canDelete . '"));
        </script>';
    }

    private function internalCreateDocumentSharingForm(Document $document) {
        global $app;

        $fb = FormBuilder::getTemporaryObject();

        $dbUsers = $app->userModel->getAllUsers();
        $users = [];

        if(count($dbUsers) > 0) {
            foreach($dbUsers as $user) {
                if($app->documentModel->isDocumentSharedToUser($user->getId(), $document->getId())) {
                    continue;
                }

                $users[] = array(
                    'value' => $user->getId(),
                    'text' => $user->getFullname()
                );
            }
        } else {
            ScriptLoader::alert('Could not load any users', array('page' => 'Documents:showAll'));
        }

        $fb ->setMethod('POST')->setAction('?page=UserModule:SingleDocument:shareDocument&id_document=' . $document->getId())

            ->addElement($fb->createLabel()->setText('User')->setFor('user'))
            ->addElement($fb->createSelect()->setName('user')->addOptionsBasedOnArray($users))

            ->addElement($fb->createLabel()->setText('Date from')->setFor('date_from')->setRequired())
            ->addElement($fb->createInput()->setType('date')->setName('date_from')->setValue(date('Y-m-d'))->require())

            ->addElement($fb->createLabel()->setText('Date to')->setFor('date_to')->setRequired())
            ->addElement($fb->createInput()->setType('date')->setName('date_to')->require())

            ->addElement($fb->createSubmit('Share'))
        ;

        return $fb->build();
    }

    private function internalCreateLockHistoryGrid(int $idDocument) {
        global $app;

        $documentLockModel = $app->documentLockModel;
        $documentLockComponent = $app->documentLockComponent;
        $user = $app->user;

        $dataSource = function() use ($documentLockModel, $idDocument) {
            return $documentLockModel->getLockEntriesForIdDocumentForGrid($idDocument);
        };

        $gb = new GridBuilder();

        $gb->addDataSourceCallback($dataSource);
        $gb->addColumns(['type' => 'Type', 'status' => 'Active', 'dateCreated' => 'Date created', 'dateUpdated' => 'Date updated']);
        $gb->addOnColumnRender('type', function(DocumentLockEntity $dle) use ($documentLockComponent, $user) {
            return $documentLockComponent->createLockText($dle, $user->getId(), false);
        });
        $gb->addOnColumnRender('status', function(DocumentLockEntity $dle) {
            switch($dle->getStatus()) {
                case DocumentLockStatus::ACTIVE:
                    return TextHelper::colorText(DocumentLockStatus::$texts[$dle->getStatus()], 'green');

                case DocumentLockStatus::INACTIVE:
                    return TextHelper::colorText(DocumentLockStatus::$texts[$dle->getStatus()], 'red');
            }
        });

        return $gb->build();
    }

    private function internalCreateMetadataHistoryGrid(int $idDocument) {
        global $app;

        $userModel = $app->userModel;
        $metadataHistoryModel = $app->documentMetadataHistoryModel;
        $metadataModel = $app->metadataModel;

        $ucm = CacheManager::getTemporaryObject(CacheCategories::USERS);
        
        $users = [];

        $dataSource = function() use ($metadataHistoryModel, $idDocument) {
            return $metadataHistoryModel->getAllEntriesForIdDocument($idDocument, 'ASC');
        };

        $gb = new GridBuilder();

        $valueBefore = [];
        $cachedMetadata = [];

        $gb->addColumns(['user' => 'User', 'metadataName' => 'Metadata name', 'valueFrom' => 'Value before', 'valueTo' => 'Value to', 'dateCreated' => 'Date']);
        $gb->addDataSourceCallback($dataSource);
        $gb->addOnColumnRender('valueTo', function(DocumentMetadataHistoryEntity $entity) use (&$valueBefore, $metadataModel, &$cachedMetadata) {
            $metadataValues = [];
            
            if(!array_key_exists($entity->getMetadataName(), $cachedMetadata)) {
                $metadataEntity = $metadataModel->getMetadataByName($entity->getMetadataName(), 'documents');

                if($metadataEntity === NULL) {
                    $valueBefore[$entity->getMetadataName()] = $entity->getMetadataValue();
                    return $entity->getMetadataValue();
                }

                $metadataValues = $metadataModel->getAllValuesForIdMetadata($metadataEntity->getId());

                $cachedMetadata[$entity->getMetadataName()] = $metadataValues;
            } else {
                $metadataValues = $cachedMetadata[$entity->getMetadataName()];
            }

            $value = $entity->getMetadataValue();
            foreach($metadataValues as $mv) {
                if($mv->getValue() == $entity->getMetadataValue()) {
                    $value = $mv->getName();
                }
            }

            $valueBefore[$entity->getMetadataName()] = $entity->getMetadataValue();
            return $value;
        });
        $gb->addOnColumnRender('valueFrom', function(DocumentMetadataHistoryEntity $entity) use (&$valueBefore, $metadataModel, &$cachedMetadata) {
            if(empty($valueBefore)) {
                return '-';
            } else {
                if(!array_key_exists($entity->getMetadataName(), $valueBefore)) {
                    return '-';
                } else {
                    $metadataValues = [];

                    if(!array_key_exists($entity->getMetadataName(), $cachedMetadata)) {
                        $metadataEntity = $metadataModel->getMetadataByName($entity->getMetadataName(), 'documents');

                        if($metadataEntity === NULL) {
                            return $valueBefore[$entity->getMetadataName()];
                        }

                        $metadataValues = $metadataModel->getAllValuesForIdMetadata($metadataEntity->getId());
        
                        $cachedMetadata[$entity->getMetadataName()] = $metadataValues;
                    } else {
                        $metadataValues = $cachedMetadata[$entity->getMetadataName()];
                    }

                    $value = $entity->getMetadataValue();
                    foreach($metadataValues as $mv) {
                        if($mv->getValue() == $valueBefore[$entity->getMetadataName()]) {
                            $value = $mv->getName();
                        }
                    }

                    return $value;
                }
            }
        });
        $gb->addOnColumnRender('user', function(DocumentMetadataHistoryEntity $entity) use (&$users, $userModel, $ucm) {
            if(array_key_exists($entity->getIdUser(), $users)) {
                return $users[$entity->getIdUser()]->getFullname();
            } else {
                $valFromCache = $ucm->loadUserByIdFromCache($entity->getIdUser());

                $user = null;

                if($valFromCache === NULL) {
                    $user = $userModel->getUserById($entity->getIdUser());

                    $ucm->saveUserToCache($user);
                } else {
                    $user = $valFromCache;
                }

                return $user->getFullname();
            }
        });
        $gb->reverseData();

        return $gb->build();
    }
}

?>