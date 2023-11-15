<?php

namespace DMS\Components\Process;

use DMS\Entities\Process;
use DMS\UI\FormBuilder\FormBuilder;

class HomeOffice implements IFormable {
    private Process $process;

    public function __construct(int $idProcess) {
        global $app;

        $this->process = $app->processModel->getProcessById($idProcess);
    }

    public static function getForm(string $action) {
        $fb = FormBuilder::getTemporaryObject();

        $fb ->setAction($action)->setMethod('POST')
            ->addElement($fb->createLabel()->setText('Date from')
                                           ->setFor('date_from'))
            ->addElement($fb->createInput()->setType('date')
                                           ->setName('date_from')
                                           ->require())
            ->addElement($fb->createLabel()->setText('Date to')
                                           ->setFor('date_to'))
            ->addElement($fb->createInput()->setType('date')
                                           ->setName('date_to')
                                           ->require())
            ->addElement($fb->createLabel()->setText('Reason')
                                           ->setFor('reason'))
            ->addElement($fb->createTextArea()->setName('reason')
                                              ->require())
            ->addElement($fb->createSubmit('Send'));

        $form = $fb->build();

        return $form;
    }

    public function work() {

    }

    public function getWorkflow() {

    }
}

?>