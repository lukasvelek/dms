<?php

namespace DMS\Components\Process;

class DeleteProcess implements IProcessComponent {
    /**
     * @var Process
     */
    private $process;

    /**
     * @var Document
     */
    private $document;

    public function __construct(int $idProcess) {
        global $app;

        $this->process = $app->processModel->getProcessById($idProcess);
        $this->document = $app->documentModel->getDocumentById($this->process->getIdDocument());
    }

    public function work() {
        global $app;

        $app->processComponent->endProcess($this->process->getId());
        $app->documentModel->updateDocument($this->document->getId(), array(
            'is_deleted' => '1',
            'status' => '2'
        ));
        $app->documentModel->nullIdOfficer($this->document->getId());

        return true;
    }
}

?>