<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\DocumentStatus;
use DMS\Core\TemplateManager;
use DMS\Entities\Document;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;
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

    protected function showInfo() {
        global $app;

        $id = htmlspecialchars($_GET['id']);
        $document = $app->documentModel->getDocumentById($id);

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/documents/single-document-grid.html');

        $data = array(
            '$PAGE_TITLE$' => 'Document <i>' . $document->getName() . '</i>',
            '$DOCUMENT_GRID$' => $this->internalCreateDocumentInfoGrid($document)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateDocumentInfoGrid(Document $document) {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $data = array(
            'Name' => $document->getName(),
            'Author' => $this->createUserLink($document->getIdAuthor()),
            'Manager' => $this->createUserLink($document->getIdManager()),
            'Status' => DocumentStatus::$texts[$document->getStatus()],
            'Group' => $this->createGroupLink($document->getIdGroup()),
            'Deleted?' => $document->getIsDeleted() ? 'Yes' : 'No'
        );

        foreach($document->getMetadata() as $k => $v) {
            $m = $app->metadataModel->getMetadataByName($k);
            $mValues = $app->metadataModel->getAllValuesForIdMetadata($m->getId());

            $vText = '-';

            foreach($mValues as $mv) {
                if($mv->getValue() == $v) {
                    $vText = $mv->getName();
                }
            }

            $data[$m->getText()] = $vText;
        }

        foreach($data as $k => $v) {
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

        $user = $app->userModel->getUserById($id);

        return LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showProfile', 'id' => $id), $user->getFullname());
    }
    
    private function createGroupLink(int $id) {
        global $app;

        $group = $app->groupModel->getGroupById($id);

        return LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:showUsers', 'id' => $id), $group->getName());
    }
}

?>