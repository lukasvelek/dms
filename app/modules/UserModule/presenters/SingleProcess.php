<?php

namespace DMS\Modules\UserModule;

use DMS\Components\Process\DeleteProcess;
use DMS\Components\Process\ShreddingProcess;
use DMS\Constants\CacheCategories;
use DMS\Constants\ProcessStatus;
use DMS\Constants\ProcessTypes;
use DMS\Constants\UserActionRights;
use DMS\Core\CacheManager;
use DMS\Core\ScriptLoader;
use DMS\Core\TemplateManager;
use DMS\Entities\Process;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

class SingleProcess extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('SingleProcess', 'Process');
    }

    protected function showProcess() {
        global $app;

        $id = htmlspecialchars($_GET['id']);

        $process = $app->processModel->getProcessById($id);

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/processes/single-process.html');

        $data = array(
            '$PROCESS_NAME$' => 'Process #' . $id . ': ' . ProcessTypes::$texts[$process->getType()],
            '$PROCESS_INFO_TABLE$' => $this->internalCreateProcessInfoTable($process),
            '$ACTIONS$' => $this->internalCreateActions($process),
            '$NEW_COMMENT_FORM$' => $this->internalCreateNewProcessCommentForm($process),
            '$PROCESS_COMMENTS$' => $this->internalCreateProcessComments($process)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function askToFinish() {
        $id = htmlspecialchars($_GET['id']);

        $urlConfirm = array(
            'page' => 'UserModule:SingleProcess:finish',
            'id' => $id
        );

        $urlClose = array(
            'page' => 'UserModule:SingleProcess:showProcess',
            'id' => $id
        );

        $code = ScriptLoader::confirmUser('Finish process?', $urlConfirm, $urlClose);

        return $code;
    }

    protected function askToApprove() {
        $id = htmlspecialchars($_GET['id']);

        $urlConfirm = array(
            'page' => 'UserModule:SingleProcess:approve',
            'id' => $id
        );

        $urlClose = array(
            'page' => 'UserModule:SingleProcess:showProcess',
            'id' => $id
        );

        $code = ScriptLoader::confirmUser('Approve?', $urlConfirm, $urlClose);

        return $code;
    }

    protected function askToDecline() {
        $id = htmlspecialchars($_GET['id']);

        $urlConfirm = array(
            'page' => 'UserModule:SingleProcess:decline',
            'id' => $id
        );

        $urlClose = array(
            'page' => 'UserModule:SingleProcess:showProcess',
            'id' => $id
        );

        $code = ScriptLoader::confirmUser('Decline?', $urlConfirm, $urlClose);

        return $code;
    }

    protected function approve() {
        global $app;

        $id = htmlspecialchars($_GET['id']);

        $app->processComponent->moveProcessToNextWorkflowUser($id);

        $app->logger->info('User #' . $app->user->getId() . ' approved process #' . $id, __METHOD__);

        $app->redirect('UserModule:SingleProcess:showProcess', array('id' => $id));
    }

    protected function decline() {
        global $app;

        $id = htmlspecialchars($_GET['id']);

        $app->processComponent->endProcess($id);

        $app->logger->info('User #' . $app->user->getId() . ' declined process #' . $id, __METHOD__);

        $app->redirect('UserModule:SingleProcess:showProcess', array('id' => $id));
    }

    protected function finish() {
        global $app;

        $id = htmlspecialchars($_GET['id']);
        $process = $app->processModel->getProcessById($id);

        switch($process->getType()) {
            case ProcessTypes::DELETE:
                $dp = new DeleteProcess($id, $app->processComponent, $app->documentModel, $app->processModel, $app->groupModel, $app->groupUserModel);
                $dp->work();
                break;
            
            case ProcessTypes::SHREDDING:
                $sp = new ShreddingProcess($id, $app->processModel, $app->documentModel, $app->processComponent, $app->documentCommentModel, $app->processCommentModel);
                $sp->work();
                break;
        }

        $app->logger->info('User #' . $app->user->getId() . ' finished process #' . $id, __METHOD__);

        $app->redirect('UserModule:Processes:showAll');
    }

    private function internalCreateProcessInfoTable(Process $process) {
        global $app;

        $ucm = CacheManager::getTemporaryObject(CacheCategories::USERS);

        $link = function(int $id, string $name) {
            return LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showProfile', 'id' => $id), $name);
        };

        $tb = TableBuilder::getTemporaryObject();

        if($process->getWorkflowStep(0) != null) {
            $workflow1User = null;

            $cacheUser = $ucm->loadUserByIdFromCache($process->getWorkflowStep(0));

            if(is_null($cacheUser)) {
                $workflow1User = $app->userModel->getUserById($process->getWorkflowStep(0));

                $ucm->saveUserToCache($workflow1User);
            } else {
                $workflow1User = $cacheUser;
            }

            $workflow1User = $link($workflow1User->getId(), $workflow1User->getFullname());
        } else {
            $workflow1User = '-';
        }

        if($process->getWorkflowStep(1) != null) {
            $workflow2User = null;

            $cacheUser = $ucm->loadUserByIdFromCache($process->getWorkflowStep(1));

            if(is_null($cacheUser)) {
                $workflow2User = $app->userModel->getUserById($process->getWorkflowStep(1));

                $ucm->saveUserToCache($workflow2User);
            } else {
                $workflow2User = $cacheUser;
            }

            $workflow2User = $link($workflow2User->getId(), $workflow2User->getFullname());
        } else {
            $workflow2User = '-';
        }

        if($process->getWorkflowStep(2) != null) {
            $workflow3User = null;

            $cacheUser = $ucm->loadUserByIdFromCache($process->getWorkflowStep(2));

            if(is_null($cacheUser)) {
                $workflow3User = $app->userModel->getUserById($process->getWorkflowStep(2));

                $ucm->saveUserToCache($workflow3User);
            } else {
                $workflow3User = $cacheUser;
            }

            $workflow3User = $link($workflow3User->getId(), $workflow3User->getFullname());
        } else {
            $workflow3User = '-';
        }

        if($process->getWorkflowStep(3) != null) {
            $workflow4User = null;

            $cacheUser = $ucm->loadUserByIdFromCache($process->getWorkflowStep(3));

            if(is_null($cacheUser)) {
                $workflow4User = $app->userModel->getUserById($process->getWorkflowStep(4));

                $ucm->saveUserToCache($workflow4User);
            } else {
                $workflow4User = $cacheUser;
            }

            $workflow4User = $link($workflow4User->getId(), $workflow4User->getFullname());
        } else {
            $workflow4User = '-';
        }

        $author = null;

        $cacheUser = $ucm->loadUserByIdFromCache($process->getIdAuthor());

        if(is_null($cacheUser)) {
            $author = $app->userModel->getUserById($process->getIdAuthor());

            $ucm->saveUserToCache($author);
        } else {
            $author = $cacheUser;
        }

        $author = $link($author->getId(), $author->getFullname());

        $currentOfficer = ${'workflow' . $process->getWorkflowStatus() . 'User'};

        $document = $app->documentModel->getDocumentById($process->getIdDocument());
        $documentLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showInfo', 'id' => $document->getId()), $document->getName());

        $tb ->addRow($tb->createRow()->addCol($tb->createCol()->setText('Workflow 1')->setBold())
                                     ->addCol($tb->createCol()->setText($workflow1User)))
            ->addRow($tb->createRow()->addCol($tb->createCol()->setText('Workflow 2')->setBold())
                                     ->addCol($tb->createCol()->setText($workflow2User)))
            ->addRow($tb->createRow()->addCol($tb->createCol()->setText('Workflow 3')->setBold())
                                     ->addCol($tb->createCol()->setText($workflow3User)))
            ->addRow($tb->createRow()->addCol($tb->createCol()->setText('Workflow 4')->setBold())
                                     ->addCol($tb->createCol()->setText($workflow4User)))
            ->addRow($tb->createRow()->addCol($tb->createCol()->setText('Workflow status')->setBold())
                                     ->addCol($tb->createCol()->setText($process->getWorkflowStatus() . ' (' . $currentOfficer . ')')))
            ->addRow($tb->createRow()->addCol($tb->createCol()->setText('Current officer')->setBold())
                                     ->addCol($tb->createCol()->setText($currentOfficer)))
            ->addRow($tb->createRow()->addCol($tb->createCol()->setText('Author')->setBold())
                                     ->addCol($tb->createCol()->setText($author)))
            ->addRow($tb->createRow()->addCol($tb->createCol()->setText('Document')->setBold())
                                     ->addCol($tb->createCol()->setText($documentLink)))
        ;

        $table = $tb->build();
        
        return $table;
    }

    private function internalCreateActions(Process $process) {
        global $app;

        $idCurrentUser = $app->user->getId();

        $actions = [];

        if($process->getStatus() == ProcessStatus::FINISHED) {
            return $actions;
        }

        switch($process->getType()) {
            case ProcessTypes::DELETE:
                if($idCurrentUser == ($process->getWorkflowStep($process->getWorkflowStatus() - 1))) {
                    // current officer

                    if($process->getWorkflowStep($process->getWorkflowStatus()) == null) {
                        // is last
                        $actions[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleProcess:finish', 'id' => $process->getId()), ProcessTypes::$texts[$process->getType()]);
                    } else {
                        $actions[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleProcess:approve', 'id' => $process->getId()), 'Approve');
                        $actions[] = '<br>';
                        $actions[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleProcess:decline', 'id' => $process->getId()), 'Decline');
                    }
                }

                break;

            case ProcessTypes::SHREDDING:
                if($idCurrentUser == ($process->getWorkflowStep($process->getWorkflowStatus() - 1))) {
                    if($process->getWorkflowStep($process->getWorkflowStatus()) == null) {
                        $actions[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleProcess:finish', 'id' => $process->getId()), 'Shred document');
                    } else {
                        $actions[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleProcess:approve', 'id' => $process->getId()), 'Approve');
                        $actions[] = '<br>';
                        $actions[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleProcess:decline', 'id' => $process->getId()), 'Decline');
                    }
                }

                break;
        }

        return $actions;
    }

    private function internalCreateNewProcessCommentForm(Process $process) {
        global $app;

        $canDelete = $app->actionAuthorizator->checkActionRight(UserActionRights::DELETE_COMMENTS) ? '1' : '0';

        $submitStyle = '';
        $textareaStyle = 'required';

        if($process->getStatus() == ProcessStatus::FINISHED) {
            $submitStyle = 'disabled';
            $textareaStyle = 'disabled';
        }

        return '<!--<script type="text/javascript" src="js/ProcessAjaxComment.js"></script>-->
        <textarea name="text" id="text" ' . $textareaStyle . '></textarea><br><br>
        <button onclick="sendProcessComment(' . $app->user->getId() . ', ' . $process->getId() . ', ' . $canDelete . ')" ' . $submitStyle . '>Send</button>
        ';
    }

    private function internalCreateProcessComments(Process $process) {
        global $app;
        
        $canDelete = $app->actionAuthorizator->checkActionRight(UserActionRights::DELETE_COMMENTS) ? '1' : '0';

        return '
        <img id="comments-loading" style="position: fixed; top: 50%; left: 49%;" src="img/loading.gif" width="32" height="32">
        <script type="text/javascript">
            $(document).on("load", showCommentsLoading())
                       .ready(loadProcessComments("' . $process->getId() . '", "' . $canDelete . '"));
        </script>';
    }
}

?>