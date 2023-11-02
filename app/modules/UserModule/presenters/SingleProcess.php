<?php

namespace DMS\Modules\UserModule;

use DMS\Components\Process\DeleteProcess;
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

        $app->redirect('UserModule:Processes:showAll');
    }

    protected function decline() {
        global $app;

        $id = htmlspecialchars($_GET['id']);

        $app->processComponent->endProcess($id);

        $app->redirect('UserModule:Processes:showAll');
    }

    protected function finish() {
        global $app;

        $id = htmlspecialchars($_GET['id']);
        $process = $app->processModel->getProcessById($id);

        switch($process->getType()) {
            case ProcessTypes::DELETE:
                $dp = new DeleteProcess($id);
                $dp->work();
                break;
        }

        $app->redirect('UserModule:Processes:showAll');
    }

    private function internalCreateProcessInfoTable(Process $process) {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        if($process->getWorkflowStep(0) != null) {
            $workflow1User = $app->userModel->getUserById($process->getWorkflowStep(0))->getFullname();
        } else {
            $workflow1User = '-';
        }

        if($process->getWorkflowStep(1) != null) {
            $workflow2User = $app->userModel->getUserById($process->getWorkflowStep(1))->getFullname();
        } else {
            $workflow2User = '-';
        }

        if($process->getWorkflowStep(2) != null) {
            $workflow3User = $app->userModel->getUserById($process->getWorkflowStep(2))->getFullname();
        } else {
            $workflow3User = '-';
        }

        if($process->getWorkflowStep(3) != null) {
            $workflow4User = $app->userModel->getUserById($process->getWorkflowStep(3))->getFullname();
        } else {
            $workflow4User = '-';
        }

        $tb ->addRow($tb->createRow()->addCol($tb->createCol()->setText('Workflow 1')->setBold())
                                     ->addCol($tb->createCol()->setText($workflow1User)))
            ->addRow($tb->createRow()->addCol($tb->createCol()->setText('Workflow 2')->setBold())
                                     ->addCol($tb->createCol()->setText($workflow2User)))
            ->addRow($tb->createRow()->addCol($tb->createCol()->setText('Workflow 3')->setBold())
                                     ->addCol($tb->createCol()->setText($workflow3User)))
            ->addRow($tb->createRow()->addCol($tb->createCol()->setText('Workflow 4')->setBold())
                                     ->addCol($tb->createCol()->setText($workflow4User)))
            ->addRow($tb->createRow()->addCol($tb->createCol()->setText('Workflow status')->setBold())
                                     ->addCol($tb->createCol()->setText($process->getWorkflowStatus())))
            ->addRow($tb->createRow()->addCol($tb->createCol()->setText('Current officer')->setBold())
                                     ->addCol($tb->createCol()->setText(${'workflow' . $process->getWorkflowStatus() . 'User'})))
        ;

        $table = $tb->build();
        
        return $table;
    }

    private function internalCreateActions(Process $process) {
        global $app;

        $idCurrentUser = $app->user->getId();

        $actions = [];

        switch($process->getType()) {
            case ProcessTypes::DELETE:
                if(($process->getWorkflowIdUserPosition($idCurrentUser) + 1) == $process->getWorkflowStatus()) {
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
        }

        return $actions;
    }
}

?>