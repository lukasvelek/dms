<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\CacheCategories;
use DMS\Constants\DocumentAfterShredActions;
use DMS\Constants\DocumentRank;
use DMS\Constants\DocumentShreddingStatus;
use DMS\Constants\DocumentStatus;
use DMS\Constants\UserActionRights;
use DMS\Core\CacheManager;
use DMS\Core\CypherManager;
use DMS\Core\ScriptLoader;
use DMS\Core\TemplateManager;
use DMS\Entities\Document;
use DMS\Helpers\ArrayStringHelper;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

class SingleDocument extends APresenter {
    private string $name;
    private TemplateManager $templateManager;
    private IModule $module;

    public const DRAW_TOPPANEL = true;

    public function __construct() {
        $this->name = 'SingleDocument';

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

    protected function shareDocument() {
        global $app;

        $idDocument = htmlspecialchars($_GET['id_document']);
        $idUser = htmlspecialchars($_POST['user']);
        $dateFrom = htmlspecialchars($_POST['date_from']);
        $dateTo = htmlspecialchars($_POST['date_to']);
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

        $app->redirect('UserModule:Documents:showAll');
    }

    protected function showShare() {
        global $app;

        $idDocument = htmlspecialchars($_GET['id']);
        $document = $app->documentModel->getDocumentById($idDocument);

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

        $idComment = htmlspecialchars($_GET['id_comment']);
        $idDocument = htmlspecialchars($_GET['id_document']);

        $app->documentCommentModel->deleteComment($idComment);

        $app->logger->info('Deleted comment #' . $idComment, __METHOD__);

        $app->redirect('UserModule:SingleDocument:showInfo', array('id' => $idDocument));
    }

    protected function askToDeleteComment() {
        $idDocument = htmlspecialchars($_GET['id_document']);
        $idComment = htmlspecialchars($_GET['id_comment']);

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

        $idDocument = htmlspecialchars($_GET['id_document']);
        $idAuthor = $app->user->getId();
        $text = htmlspecialchars($_POST['text']);

        $data = array(
            'id_document' => $idDocument,
            'id_author' => $idAuthor,
            'text' => $text
        );

        $app->documentCommentModel->insertComment($data);

        $app->redirect('UserModule:SingleDocument:showInfo', array('id' => $idDocument));
    }

    protected function showInfo() {
        global $app;

        $id = htmlspecialchars($_GET['id']);
        $document = $app->documentModel->getDocumentById($id);

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
            '$DOCUMENT_COMMENTS$' => $documentComments
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showEdit() {
        global $app;

        $id = htmlspecialchars($_GET['id']);
        $document  = $app->documentModel->getDocumentById($id);

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

        $id = htmlspecialchars($_GET['id']);

        $data = [];

        $idGroup = htmlspecialchars($_POST['group']);
        $idFolder = htmlspecialchars($_POST['folder']);
        
        $data['name'] = htmlspecialchars($_POST['name']);
        $data['id_manager'] = htmlspecialchars($_POST['manager']);
        $data['status'] = htmlspecialchars($_POST['status']);
        $data['id_group'] = htmlspecialchars($idGroup);

        if($idFolder != '-1') {
            $data['id_folder'] = $idFolder;
        }

        unset($_POST['name']);
        unset($_POST['manager']);
        unset($_POST['status']);
        unset($_POST['group']);
        unset($_POST['folder']);

        $customMetadata = $_POST;

        $data = array_merge($data, $customMetadata);

        $app->documentModel->updateDocument($id, $data);

        $app->logger->info('Updated document #' . $id, __METHOD__);

        $app->redirect('UserModule:SingleDocument:showInfo', array('id' => $id));
    }

    private function internalCreateDocumentEditForm(Document $document) {
        global $app;

        $idFolder = $document->getIdFolder();

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

                $options = [];
                foreach($values as $v) {
                    $option = array(
                        'value' => $v->getValue(),
                        'text' => $v->getName()
                    );
                
                    if(!is_null($document->getMetadata($name))) {
                        $option['selected'] = 'selected';
                    }

                    $options[] = $option;
                }

                $metadata[$name] = array('text' => $text, 'options' => $options, 'type' => $cm->getInputType(), 'length' => $cm->getInputLength());
            }
        }

        $fb = FormBuilder::getTemporaryObject();

        $fb ->setMethod('POST')->setAction('?page=UserModule:SingleDocument:updateDocument&id=' . $document->getId())
            ->addElement($fb->createLabel()->setText('Document name')
                                           ->setFor('name'))
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

        $data = array(
            'Name' => $document->getName(),
            'Author' => $this->createUserLink($document->getIdAuthor()),
            'Manager' => $this->createUserLink($document->getIdManager()),
            'Status' => $status,
            'Rank' => $rank,
            'Group' => $this->createGroupLink($document->getIdGroup()),
            'Deleted?' => $document->getIsDeleted() ? 'Yes' : 'No',
            'Folder' => $folder,
            'Shred year' => $document->getShredYear(),
            'Action after shredding' => DocumentAfterShredActions::$texts[$document->getAfterShredAction()],
            'Shredding status' => DocumentShreddingStatus::$texts[$document->getShreddingStatus()]
        );

        foreach($document->getMetadata() as $k => $v) {
            $m = $app->metadataModel->getMetadataByName($k, 'documents');
            
            if($m->getInputType() == 'select_external') {
                $mValues = $app->externalEnumComponent->getEnumByName($m->getSelectExternalEnumName())->getValues();

                $data[$m->getText()] = $mValues[$v];
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

        $ucm = new CacheManager(true, CacheCategories::USERS);

        $cacheUser = $ucm->loadUserByIdFromCache($id);

        if(is_null($cacheUser)) {
            $user = $app->userModel->getUserById($id);

            $ucm->saveUserToCache($user);
        } else {
            $user = $cacheUser;
        }

        return LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showProfile', 'id' => $id), $user->getFullname());
    }
    
    private function createGroupLink(int $id) {
        global $app;

        $group = $app->groupModel->getGroupById($id);

        return LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:showUsers', 'id' => $id), $group->getName());
    }

    private function createFolderLink(int $id) {
        global $app;

        $folder = $app->folderModel->getFolderById($id);

        return LinkBuilder::createAdvLink(array('page' => 'UserModule:Settings:showFolders', 'id' => $id), $folder->getName());
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
            ScriptLoader::alert('Could not load any users', array('page' => 'UserModule:Documents:showAll'));
        }

        $fb ->setMethod('POST')->setAction('?page=UserModule:SingleDocument:shareDocument&id_document=' . $document->getId())

            ->addElement($fb->createLabel()->setText('User')->setFor('user'))
            ->addElement($fb->createSelect()->setName('user')->addOptionsBasedOnArray($users))

            ->addElement($fb->createLabel()->setText('Date from')->setFor('date_from'))
            ->addElement($fb->createInput()->setType('date')->setName('date_from')->setValue(date('Y-m-d'))->require())

            ->addElement($fb->createLabel()->setText('Date to')->setFor('date_to'))
            ->addElement($fb->createInput()->setType('date')->setName('date_to')->require())

            ->addElement($fb->createSubmit('Share'))
        ;

        return $fb->build();
    }
}

?>