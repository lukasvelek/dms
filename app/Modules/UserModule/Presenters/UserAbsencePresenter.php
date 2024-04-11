<?php

namespace DMS\Modules\UserModule;

use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;

class UserAbsencePresenter extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('UserAbsence', 'User absence');

        $this->getActionNamesFromClass($this);
    }

    protected function showNewAbsenceForm() {
        $template = $this->loadTemplate(__DIR__ . '/templates/users/user-new-entity-form.html');

        $data = [
            '$PAGE_TITLE$' => 'New absence',
            '$LINKS$' => [],
            '$FORM$' => $this->internalCreateNewAbsenceForm()
        ];

        $this->fill($data, $template);

        return $template;
    }

    protected function showMyAbsence() {
        $template = $this->loadTemplate(__DIR__ . '/templates/users/user-profile-grid.html');

        $data = [
            '$PAGE_TITLE$' => 'My absence',
            '$LINKS$' => [],
            '$USER_PROFILE_GRID$' => $this->internalCreateMyAbsenceGrid()
        ];

        $data['$LINKS$'][] = LinkBuilder::createAdvLink(['page' => 'showNewAbsenceForm'], 'New absence');
        
        $this->fill($data, $template);

        return $template;
    }

    private function internalCreateMyAbsenceGrid() {
        global $app;

        $datasource = $app->userAbsenceRepository->getAbsenceForIdUser($app->user->getId());

        $gb = new GridBuilder();

        $gb->addColumns(['dateFrom' => 'Date from', 'dateTo' => 'Date to']);
        $gb->addDataSource($datasource);

        return $gb->build();
    }

    private function internalCreateNewAbsenceForm() {
        $fb = new FormBuilder();

        $fb ->setMethod('POST')->setAction('?page=UserModule:UserAbsence:processNewAbsenceForm')
            
            ->addLabel('Date from', 'date_from')
            ->addElement($fb->createInput()->setType('date')->setName('date_from'))

            ->addLabel('Date to', 'date_to')
            ->addElement($fb->createInput()->setType('date')->setName('date_to'))

            ->addElement($fb->createSubmit('Create'))
        ;

        return $fb->build();
    }
}

?>