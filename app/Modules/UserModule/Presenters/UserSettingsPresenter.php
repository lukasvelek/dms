<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\UserLoginAttemptResults;
use DMS\Core\ScriptLoader;
use DMS\Entities\UserLoginAttemptEntity;
use DMS\Helpers\GridDataHelper;
use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;

class UserSettingsPresenter extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('UserSettings', 'User settings');

        $this->getActionNamesFromClass($this);
    }

    protected function showBlockUserForm() {
        global $app;

        $app->flashMessageIfNotIsset(['id_user']);

        $idUser = $this->get('id_user');

        $template = $this->loadTemplate(__DIR__ . '/templates/settings/settings-new-entity-form.html');

        $data = [
            '$PAGE_TITLE$' => 'Block user #' . $idUser,
            '$LINKS$' => [],
            '$FORM$' => $this->internalCreateBlockUserForm($idUser)
        ];

        $data['$LINKS$'][] = LinkBuilder::createAdvLink(['page' => 'showLoginAttempts'], '&larr;');

        $this->fill($data, $template);

        return $template;
    }

    protected function processBlockUserForm() {
        global $app;

        $app->flashMessageIfNotIsset(['id_user', 'date_from', 'description']);

        $idUser = $this->get('id_user');
        $dateFrom = $this->post('date_from');
        $description = $this->post('description');

        $dateTo = null;
        if(isset($_POST['date_to']) && !empty($_POST['date_to'])) {
            $dateTo = $this->post('date_to');
        }

        $app->userRepository->blockUser($app->user->getId(), $idUser, $description, $dateFrom, $dateTo);

        $text = 'User #' . $idUser . ' has been blocked due to reason: \'' . $description . '\' from ' . $dateFrom;

        if($dateTo !== NULL) {
            $text .= ' to ' . $dateTo;
        }

        $app->flashMessage($text);
        $app->redirect('showLoginAttempts');
    }

    protected function unblockUser() {
        global $app;

        $app->flashMessageIfNotIsset(['id_user']);

        $idUser = $this->get('id_user');

        $app->userRepository->unblockUser($idUser);

        $app->flashMessage('User #' . $idUser . ' has been unblocked!');
        $app->redirect('showLoginAttempts');
    }

    protected function showLoginAttempts() {
        $template = $this->loadTemplate(__DIR__ . '/templates/settings/settings-grid.html');

        $type = 'all';

        if($this->get('type') !== NULL) {
            $type = $this->get('type');
        }

        $data = [
            '$PAGE_TITLE$' => 'Login attempts',
            '$LINKS$' => [],
            '$SETTINGS_GRID$' => $this->internalCreateLoginAttemptsGrid($type)
        ];

        $data['$LINKS$'][] = LinkBuilder::createAdvLink(['page' => 'showLoginAttempts'], 'All attempts') . '&nbsp;&nbsp;';
        $data['$LINKS$'][] = LinkBuilder::createAdvLink(['page' => 'showLoginAttempts', 'type' => 'unsuccessful'], 'Unsuccessful attempts');

        $this->fill($data, $template);

        return $template;
    }

    private function internalCreateLoginAttemptsGrid(string $type) {
        global $app;

        $userRepository = $app->userRepository;
        
        $dataSource = [];

        switch($type) {
            case 'all':
                $dataSource = $app->userRepository->getLoginAttemptsByDate();
                break;

            case 'unsuccessful':
                $dataSource = $app->userRepository->getUnsuccessfulLoginAttemptsByDate();
                break;
        }

        $usernames = [];
        
        $activeLoginBlockEntities = $app->userModel->getActiveUserLoginBlocks();
        
        $activeLoginBlocks = [];
        foreach($activeLoginBlockEntities as $entity) {
            if(strtotime($entity->getDateFrom()) > time()) {
                continue;
            }

            if($entity->getDateTo() !== NULL) {
                if(strtotime($entity->getDateTo()) < time() && $entity->isActive() === TRUE) {
                    continue;
                }
            }

            $activeLoginBlocks[] = $entity->getIdUser();
        }
        
        $gb = new GridBuilder();

        $gb->addDataSource($dataSource);
        $gb->addColumns(['username' => 'Username', 'result' => 'Result', 'description' => 'Description', 'dateCreated' => 'Date']);
        $gb->addOnColumnRender('result', function(UserLoginAttemptEntity $ulae) {
            $text = UserLoginAttemptResults::$texts[$ulae->getResult()];
            $value = ($ulae->getResult() == 1) ? true : false;
            return GridDataHelper::renderBooleanValueWithColors($value, $text, $text);
        });
        $gb->addAction(function(UserLoginAttemptEntity $ulae) use ($userRepository, &$usernames, $activeLoginBlocks) {
            $idUser = null;
            if(array_key_exists($ulae->getUsername(), $usernames)) {
                $idUser = $usernames[$ulae->getUsername()];
            } else {
                $user = $userRepository->getUserByUsername($ulae->getUsername());
                if($user !== NULL) {
                    $usernames[$user->getUsername()] = $user->getId();
                    $idUser = $user->getId();
                }
            }

            if($idUser === NULL) {
                return '-';
            }

            if(in_array($idUser, $activeLoginBlocks)) {
                return LinkBuilder::createAdvLink(['page' => 'unblockUser', 'id_user' => $idUser], 'Unblock user');
            } else {
                return LinkBuilder::createAdvLink(['page' => 'showBlockUserForm', 'id_user' => $idUser], 'Block user');
            }
        });

        return $gb->build();
    }

    private function internalCreateBlockUserForm(int $idUser) {
        $fb = new FormBuilder();

        $fb ->setMethod('POST')->setAction('?page=UserModule:UserSettings:processBlockUserForm&id_user=' . $idUser)
            
            ->addLabel('Date from', 'date_from')
            ->addElement($fb->createInput()->setType('date')->setName('date_from')->setMin(date('Y-m-d'))->require())

            ->addLabel('Date to', 'date_to')
            ->addElement($fb->createInput()->setType('date')->setName('date_to'))

            ->addLabel('Description', 'description')
            ->addElement($fb->createTextArea()->setName('description')->require())

            ->addElement($fb->createSubmit('Block'))
        ;

        $jsScript = ScriptLoader::loadJSScript('js/UserBlockForm.js');

        $fb->addJSScript($jsScript);

        return $fb->build();
    }
}

?>