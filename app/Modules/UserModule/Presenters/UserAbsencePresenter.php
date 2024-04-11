<?php

namespace DMS\Modules\UserModule;

use DMS\Core\ScriptLoader;
use DMS\Entities\UserAbsenceEntity;
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

    protected function deleteAbsence() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);

        $id = $this->get('id');

        $app->userAbsenceRepository->deleteAbsence($id);

        $app->flashMessage('Deleted absence.');
        $app->redirect('showMyAbsence');
    }

    protected function processEditAbsenceForm() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'date_from', 'date_to']);

        $id = $this->get('id');
        $dateFrom = $this->post('date_from');
        $dateTo = $this->post('date_to');

        $app->userAbsenceRepository->editAbsence($id, $dateFrom, $dateTo);

        $app->flashMessage('Updated absence.');
        $app->redirect('showMyAbsence');
    }

    protected function showEditAbsenceForm() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);

        $id = $this->get('id');

        $template = $this->loadTemplate(__DIR__ . '/templates/users/user-new-entity-form.html');

        $data = [
            '$PAGE_TITLE$' => 'Edit absence',
            '$LINKS$' => [],
            '$FORM$' => $this->internalCreateEditAbsenceForm($id)
        ];

        $data['$LINKS$'][] = LinkBuilder::createAdvLink(['page' => 'showMyAbsence'], '&larr;');

        $this->fill($data, $template);

        return $template;
    }

    protected function processNewAbsenceForm() {
        global $app;

        $app->flashMessageIfNotIsset(['date_from', 'date_to']);

        $dateFrom = $this->post('date_from');
        $dateTo = $this->post('date_to');
        $idUser = $app->user->getId();

        $app->userAbsenceRepository->insertAbsence($idUser, $dateFrom, $dateTo);

        $app->flashMessage('Absence created.');
        $app->redirect('showMyAbsence');
    }

    protected function showNewAbsenceForm() {
        $template = $this->loadTemplate(__DIR__ . '/templates/users/user-new-entity-form.html');

        $data = [
            '$PAGE_TITLE$' => 'New absence',
            '$LINKS$' => [],
            '$FORM$' => $this->internalCreateNewAbsenceForm()
        ];

        $data['$LINKS$'][] = LinkBuilder::createAdvLink(['page' => 'showMyAbsence'], '&larr;');

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
        $gb->addOnColumnRender('dateFrom', function(UserAbsenceEntity $uae) {
            return explode(' ', $uae->getDateFrom())[0];
        });
        $gb->addOnColumnRender('dateTo', function(UserAbsenceEntity $uae) {
            return explode(' ', $uae->getDateTo())[0];
        });
        $gb->addAction(function(UserAbsenceEntity $uae) {
            return LinkBuilder::createAdvLink(['page' => 'showEditAbsenceForm', 'id' => $uae->getId()], 'Edit');
        });
        $gb->addAction(function(UserAbsenceEntity $uae) {
            return LinkBuilder::createAdvLink(['page' => 'deleteAbsence', 'id' => $uae->getId()], 'Delete');
        });

        return $gb->build();
    }

    private function internalCreateNewAbsenceForm() {
        $fb = new FormBuilder();

        $fb ->setMethod('POST')->setAction('?page=UserModule:UserAbsence:processNewAbsenceForm')
            
            ->addLabel('Date from', 'date_from')
            ->addElement($fb->createInput()->setType('date')->setName('date_from')->require())

            ->addLabel('Date to', 'date_to')
            ->addElement($fb->createInput()->setType('date')->setName('date_to')->require())

            ->addElement($fb->createSubmit('Create'))
        ;

        $script = ScriptLoader::loadJSScript('js/UserAbsenceForm.js');

        $fb->addJSScript($script);

        return $fb->build();
    }

    private function internalCreateEditAbsenceForm(int $id) {
        global $app;

        $entity = $app->userAbsenceRepository->getAbsenceById($id);

        $dateFrom = explode(' ', $entity->getDateFrom())[0];
        $dateTo = explode(' ', $entity->getDateTo())[0];

        $fb = new FormBuilder();

        $fb ->setMethod('POST')->setAction('?page=UserModule:UserAbsence:processEditAbsenceForm&id=' . $id)
            
            ->addLabel('Date from', 'date_from')
            ->addElement($fb->createInput()->setType('date')->setName('date_from')->require()->setValue($dateFrom))

            ->addLabel('Date to', 'date_to')
            ->addElement($fb->createInput()->setType('date')->setName('date_to')->require()->setValue($dateTo))

            ->addElement($fb->createSubmit('Save'))
        ;

        $script = ScriptLoader::loadJSScript('js/UserAbsenceForm.js');

        $fb->addJSScript($script);

        return $fb->build();
    }
}

?>