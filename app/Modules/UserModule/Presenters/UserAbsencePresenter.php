<?php

namespace DMS\Modules\UserModule;

use DMS\Core\ScriptLoader;
use DMS\Entities\CalendarEventEntity;
use DMS\Entities\UserAbsenceEntity;
use DMS\Modules\APresenter;
use DMS\UI\CalendarBuilder\CalendarBuilder;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;

class UserAbsencePresenter extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('UserAbsence', 'User absence');

        $this->getActionNamesFromClass($this);
    }

    protected function showMyAbsenceCalendar() {
        $template = $this->loadTemplate(__DIR__ . '/templates/users/user-absence-calendar.html');

        $month = date('m');
        if(isset($_GET['month'])) {
            $month = $this->get('month');
        }

        $year = date('Y');
        if(isset($_GET['year'])) {
            $year = $this->get('year');
        }

        $temp = $this->internalCreateMyAbsenceCalendar($month, $year);
        $calendar = $temp['calendar'];
        $controller = $temp['controller'];

        $data = [
            '$PAGE_TITLE$' => 'My absence calendar',
            '$LINKS$' => [],
            '$CALENDAR$' => $calendar,
            '$CALENDAR_CONTROLLER$' => $controller
        ];

        $data['$LINKS$'][] = LinkBuilder::createAdvLink(['page' => 'showMyAbsence'], '&larr;');

        $this->fill($data, $template);

        return $template;
    }

    protected function processMySubstituteForm() {
        global $app;

        $app->flashMessageIfNotIsset(['user', 'exists']);

        $idSubstitute = $this->post('user');
        $idUser = $app->user->getId();
        $exists = $this->get('exists');

        if($exists == '0') {
            // create new
            $app->userAbsenceRepository->createSubstituteForIdUser($idUser, $idSubstitute);
        } else {
            // update
            $app->userAbsenceRepository->editSubstituteForIdUser($idUser, $idSubstitute);
        }

        $app->flashMessage('Updated substitute.');
        $app->redirect('showMySubstituteForm');
    }

    protected function showMySubstituteForm() {
        $template = $this->loadTemplate(__DIR__ . '/templates/users/user-new-entity-form.html');

        $data = [
            '$PAGE_TITLE$' => 'My substitute',
            '$LINKS$' => [],
            '$FORM$' => $this->internalCreateMySubstituteForm()
        ];

        $data['$LINKS$'][] = LinkBuilder::createAdvLink(['page' => 'showMyAbsence'], '&larr;');
        
        $this->fill($data, $template);

        return $template;
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

        $data['$LINKS$'][] = LinkBuilder::createAdvLink(['page' => 'showNewAbsenceForm'], 'New absence') . '&nbsp;&nbsp;';
        $data['$LINKS$'][] = LinkBuilder::createAdvLink(['page' => 'showMyAbsenceCalendar'], 'Show calendar') . '&nbsp;&nbsp;';
        $data['$LINKS$'][] = LinkBuilder::createAdvLink(['page' => 'showMySubstituteForm'], 'My substitute');
        
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

    private function internalCreateMySubstituteForm() {
        global $app;

        $dbUsers = $app->userModel->getAllUsers();

        $substitute = $app->userAbsenceRepository->getSubstituteForIdUser($app->user->getId());

        $users = [];
        foreach($dbUsers as $dbu) {
            $user = [
                'value' => $dbu->getId(),
                'text' => $dbu->getFullname()
            ];

            if($substitute !== NULL) {
                if($substitute->getIdSubstitute() == $dbu->getId()) {
                    $user['selected'] = 'selected';
                }
            }

            $users[] = $user;
        }

        $exists = 1;

        if($substitute === NULL) {
            $exists = 0;
        }

        $fb = new FormBuilder();

        $fb ->setMethod('POST')->setAction('?page=UserModule:UserAbsence:processMySubstituteForm&exists=' . $exists)

            ->addLabel('User', 'user')
            ->addElement($fb->createSelect()->setName('user')->addOptionsBasedOnArray($users))

            ->addElement($fb->createSubmit('Save'))
        ;

        return $fb->build();
    }

    private function internalCreateMyAbsenceCalendar(string $month, string $year) {
        global $app;

        $absenceEntities = $app->userAbsenceRepository->getAbsenceForIdUser($app->user->getId());
        
        $calendarEvents = [];
        foreach($absenceEntities as $entity) {
            $calendarEvent = new CalendarEventEntity($entity->getId(), date('Y-m-d'), 'Absence', 'BLUE', null, $entity->getDateFrom(), $entity->getDateTo(), '0');

            $calendarEvents[] = $calendarEvent;
        }

        $cb = new CalendarBuilder();

        $cb->setMonth($month);
        $cb->setYear($year);
        $cb->addEventObjects($calendarEvents);

        return ['calendar' => $cb->build(), 'controller' => $cb->getController('UserModule:UserAbsence:showMyAbsenceCalendar')];
    }
}

?>