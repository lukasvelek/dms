<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\Metadata\UserConnectionMetadata;
use DMS\Constants\UserActionRights;
use DMS\Core\AppConfiguration;
use DMS\Core\CacheManager;
use DMS\Entities\User;
use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;

class UserReloginPresenter extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('UserRelogin', 'User relogin');

        $this->getActionNamesFromClass($this);
    }

    protected function showConnectedUsers() {
        global $app;

        $this->checkEnabled();

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/users/user-profile-grid.html');

        $newConnectionLink = '';
        
        if($app->actionAuthorizator->checkActionRight(UserActionRights::CREATE_USER_CONNECTIONS)) {
            $newConnectionLink = LinkBuilder::createAdvLink(array('page' => 'showNewConnectionForm'), 'New connection');
        }

        $data = array(
            '$PAGE_TITLE$' => 'Connected users',
            '$LINKS$' => array(
                $newConnectionLink
            ),
            '$USER_PROFILE_GRID$' => $this->internalCreateConnectedUsersGrid()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showNewConnectionForm() {
        $this->checkEnabled();

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/users/user-new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'New user connection',
            '$FORM$' => $this->internalCreateNewConnectionForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function createNewConnection() {
        global $app;

        $this->checkEnabled();

        $app->flashMessageIfNotIsset(array('user'), true, array('page' => 'showNewConnectionForm'));

        $user = $this->post('user'); // id user

        $data = array(
            UserConnectionMetadata::ID_USER1 => $app->user->getId(),
            UserConnectionMetadata::ID_USER2 => $user
        );

        $app->userModel->insertNewUserConnect($data);

        $app->flashMessage('Successfully connected users', 'success');
        $app->redirect('showConnectedUsers');
    }

    protected function removeConnectedUser() {
        global $app;

        $this->checkEnabled();

        $app->flashMessageIfNotIsset(array('id_user'), true, array('page' => 'showConnectedUsers'));

        $idUser = $this->get('id_user');

        $app->userModel->removeConnectionForTwoUsers($idUser, $app->user->getId());

        $app->flashMessage('Successfully removed connection', 'success');
        $app->redirect('showConnectedUsers');
    }

    protected function reloginAsUser() {
        global $app;

        $this->checkEnabled();

        $app->flashMessageIfNotIsset(array('id_user'), true, array('page' => 'showConnectedUsers'));

        $idUser = $this->get('id_user');
        $user = $app->userModel->getUserById($idUser);

        $_SESSION['id_current_user'] = $idUser;
        $_SESSION['session_end_date'] = date('Y-m-d H:i:s', (time() + (24 * 60 * 60)));

        if(isset($_SESSION['is_relogin']) && isset($_SESSION['id_original_user'])) {
            if($_SESSION['id_original_user'] == $idUser) {
                // logging back
                unset($_SESSION['is_relogin']);
                unset($_SESSION['id_original_user']);
            } else {
                // relogging as different user (again)
                $_SESSION['id_original_user'] = $app->user->getId();
                $_SESSION['is_relogin'] = true;
            }
        } else {
            $_SESSION['is_relogin'] = true;
            $_SESSION['id_original_user'] = $app->user->getId();
        }

        CacheManager::invalidateAllCache();

        $app->flashMessage('Logged in as <i>' . $user->getFullname() . '</i>');

        $app->redirect('HomePage:showHomepage');
    }

    private function internalCreateNewConnectionForm() {
        global $app;

        $fb = FormBuilder::getTemporaryObject();

        $usersArr = [];

        $currentConnections = $app->userModel->getIdConnectedUsersForIdUser($app->user->getId());
        $current = '';

        $i = 0;
        foreach($currentConnections as $conn) {
            if(($i + 1) == count($currentConnections)) {
                $current .= $conn;
            } else {
                $current .= $conn . ', ';
            }

            $i++;
        }

        $app->logger->logFunction(function() use ($app, &$usersArr, $current) {
            $condition = "WHERE `id` <> " . $app->user->getId();

            if($current != '') {
                $condition .= " AND `id` NOT IN ($current)";
            }

            $users = $app->userModel->getAllUsersMeetingCondition($condition);

            foreach($users as $user) {
                $usersArr[] = array(
                    'value' => $user->getId(),
                    'text' => $user->getFullname()
                );
            }
        }, __METHOD__);

        $fb ->setMethod('POST')->setAction('?page=UserModule:UserRelogin:createNewConnection')
            
            ->addElement($fb->createLabel()->setText('User')->setFor('user'))
            ->addElement($fb->createSelect()->setName('user')->addOptionsBasedOnArray($usersArr))

            ->addElement($fb->createSubmit('Create'))
        ;

        return $fb->build();
    }

    private function internalCreateConnectedUsersGrid() {
        global $app;

        $userModel = $app->userModel;
        $idUser = $app->user->getId();
        $actionAuthorizator = $app->actionAuthorizator;

        $dataSourceCallback = function() use ($userModel, $idUser) {
            return $userModel->getConnectedUsersForIdUser($idUser);
        };

        $gb = new GridBuilder();

        $gb->addColumns(['user' => 'User']);
        $gb->addOnColumnRender('user', function(User $user) {
            return $user->getFullname();
        });
        $gb->addAction(function(User $user) use ($actionAuthorizator) {
            if($actionAuthorizator->checkActionRight(UserActionRights::ALLOW_RELOGIN) || (isset($_SESSION['id_original_user']) && $user->getId() == $_SESSION['id_original_user'])) {
                return LinkBuilder::createAdvLink(array('page' => 'reloginAsUser', 'id_user' => $user->getId()), 'Login');
            } else {
                return '-';
            }
        });
        $gb->addAction(function(User $user) use ($actionAuthorizator) {
            if($actionAuthorizator->checkActionRight(UserActionRights::REMOVE_USER_CONNECTIONS)) {
                return LinkBuilder::createAdvLink(array('page' => 'removeConnectedUser', 'id_user' => $user->getId()), 'Remove connection');
            } else {
                return '-';
            }
        });
        $gb->addDataSourceCallback($dataSourceCallback);

        return $gb->build();
    }

    private function checkEnabled() {
        global $app;

        if(!AppConfiguration::getEnableRelogin()) {
            $app->flashMessage('User relogin is disabled!', 'error');
            $app->redirect('UserModule:HomePage:showHomepage');
        }
    }
}

?>