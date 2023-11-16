<?php

namespace DMS\Modules\UserModule;

use DMS\Components\Process\DeleteProcess;
use DMS\Components\Process\ShreddingProcess;
use DMS\Constants\ProcessStatus;
use DMS\Constants\ProcessTypes;
use DMS\Core\ScriptLoader;
use DMS\Core\TemplateManager;
use DMS\Entities\Process;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

class SingleProcess extends APresenter {
    private string $name;
    private TemplateManager $templateManager;
    private IModule $module;

    public const DRAW_TOPPANEL = true;

    public function __construct() {
        $this->name = 'SingleProcess';

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

    protected function showProcess() {
        global $app;

        $id = htmlspecialchars($_GET['id']);

        $process = $app->processModel->getProcessById($id);

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/processes/single-process.html');

        $data = array(
            '$PROCESS_NAME$' => 'Process #' . $id . ': ' . ProcessTypes::$texts[$process->getType()]
        );

        $data['$PROCESS_INFO_TABLE$'] = $this->internalCreateProcessInfoTable($process);
        $data['$ACTIONS$'] = $this->internalCreateActions($process);

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

        $app->redirect('UserModule:Processes:showAll');
    }

    protected function decline() {
        global $app;

        $id = htmlspecialchars($_GET['id']);

        $app->processComponent->endProcess($id);

        $app->logger->info('User #' . $app->user->getId() . ' declined process #' . $id, __METHOD__);

        $app->redirect('UserModule:Processes:showAll');
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
                $sp = new ShreddingProcess($id, $app->processModel, $app->documentModel, $app->processComponent);
                $sp->work();
                break;
        }

        $app->logger->info('User #' . $app->user->getId() . ' finished process #' . $id, __METHOD__);

        $app->redirect('UserModule:Processes:showAll');
    }

    private function internalCreateProcessInfoTable(Process $process) {
        global $app;

        $link = function(int $id, string $name) {
            return LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showProfile', 'id' => $id), $name);
        };

        $tb = TableBuilder::getTemporaryObject();

        if($process->getWorkflowStep(0) != null) {
            $workflow1User = $app->userModel->getUserById($process->getWorkflowStep(0));
            $workflow1User = $link($workflow1User->getId(), $workflow1User->getFullname());
        } else {
            $workflow1User = '-';
        }

        if($process->getWorkflowStep(1) != null) {
            $workflow2User = $app->userModel->getUserById($process->getWorkflowStep(1));
            $workflow2User = $link($workflow2User->getId(), $workflow2User->getFullname());
        } else {
            $workflow2User = '-';
        }

        if($process->getWorkflowStep(2) != null) {
            $workflow3User = $app->userModel->getUserById($process->getWorkflowStep(2));
            $workflow3User = $link($workflow3User->getId(), $workflow3User->getFullname());
        } else {
            $workflow3User = '-';
        }

        if($process->getWorkflowStep(3) != null) {
            $workflow4User = $app->userModel->getUserById($process->getWorkflowStep(3));
            $workflow4User = $link($workflow4User->getId(), $workflow4User->getFullname());
        } else {
            $workflow4User = '-';
        }

        $author = $app->userModel->getUserById($process->getIdAuthor());
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
                        $actions[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleProcess:askToFinish', 'id' => $process->getId()), ProcessTypes::$texts[$process->getType()]);
                    } else {
                        $actions[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleProcess:askToApprove', 'id' => $process->getId()), 'Approve');
                        $actions[] = '<br>';
                        $actions[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleProcess:askToDecline', 'id' => $process->getId()), 'Decline');
                    }
                }

                break;

            case ProcessTypes::SHREDDING:
                if($idCurrentUser == ($process->getWorkflowStep($process->getWorkflowStatus() - 1))) {
                    if($process->getWorkflowStep($process->getWorkflowStatus()) == null) {
                        $actions[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleProcess:askToFinish', 'id' => $process->getId()), 'Shred document');
                    } else {
                        $actions[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleProcess:askToApprove', 'id' => $process->getId()), 'Approve');
                        $actions[] = '<br>';
                        $actions[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleProcess:askToDecline', 'id' => $process->getId()), 'Decline');
                    }
                }

                break;
        }

        return $actions;
    }
}

?>