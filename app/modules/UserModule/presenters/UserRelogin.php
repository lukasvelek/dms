<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\UserActionRights;
use DMS\Core\AppConfiguration;
use DMS\Core\CacheManager;
use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

class UserRelogin extends APresenter {
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
            $newConnectionLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:UserRelogin:showNewConnectionForm'), 'New connection');
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

        $app->flashMessageIfNotIsset(array('user'), true, array('page' => 'UserModule:UserRelogin:showNewConnectionForm'));

        $user = htmlspecialchars($_POST['user']); // id user

        $data = array(
            'id_user1' => $app->user->getId(),
            'id_user2' => $user
        );

        $app->userModel->insertNewUserConnect($data);

        $app->flashMessage('Successfully connected users', 'success');
        $app->redirect('UserModule:UserRelogin:showConnectedUsers');
    }

    protected function removeConnectedUser() {
        global $app;

        $this->checkEnabled();

        $app->flashMessageIfNotIsset(array('id_user'), true, array('page' => 'UserModule:UserRelogin:showConnectedUsers'));

        $idUser = htmlspecialchars($_GET['id_user']);

        $app->userModel->removeConnectionForTwoUsers($idUser, $app->user->getId());

        $app->flashMessage('Successfully removed connection', 'success');
        $app->redirect('UserModule:UserRelogin:showConnectedUsers');
    }

    protected function reloginAsUser() {
        global $app;

        $this->checkEnabled();

        $app->flashMessageIfNotIsset(array('id_user'), true, array('page' => 'UserModule:UserRelogin:showConnectedUsers'));

        $idUser = htmlspecialchars($_GET['id_user']);
        $user = $app->userModel->getUserById($idUser);

        $_SESSION['id_current_user'] = $idUser;
        $_SESSION['session_end_date'] = date('Y-m-d H:i:s', (time() + (24 * 60 * 60)));

        CacheManager::invalidateAllCache();

        $app->flashMessage('Logged in as <i>' . $user->getFullname() . '</i>');

        if(!is_null($user->getDefaultUserPageUrl())) {
            $app->redirect($user->getDefaultUserPageUrl());
        } else {
            $app->redirect('UserModule:HomePage:showHomepage');
        }
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

        $connectedUsers = $app->userModel->getConnectedUsersForIdUser($app->user->getId());

        $tb = TableBuilder::getTemporaryObject();

        $headers = array(
            'Actions',
            'User'
        );

        $headerRow = null;

        $allowRelogin = $app->actionAuthorizator->checkActionRight(UserActionRights::ALLOW_RELOGIN);
        $removeUserConnections = $app->actionAuthorizator->checkActionRight(UserActionRights::REMOVE_USER_CONNECTIONS);

        if(empty($connectedUsers)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($connectedUsers as $user) {
                $actionLinks = [];

                if($allowRelogin) {
                    $actionLinks[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:UserRelogin:reloginAsUser', 'id_user' => $user->getId()), 'Login');
                } else {
                    $actionLinks[] = '-';
                }

                if($removeUserConnections) {
                    $actionLinks[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:UserRelogin:removeConnectedUser', 'id_user' => $user->getId()), 'Remove connection');
                } else {
                    $actionLinks[] = '-';
                }

                if(is_null($headerRow)) {
                    $row = $tb->createRow();

                    foreach($headers as $header) {
                        $col = $tb->createCol()->setText($header)->setBold();

                        if($header == 'Actions') {
                            $col->setColspan(count($actionLinks));
                        }

                        $row->addCol($col);
                    }

                    $headerRow = $row;
                    $tb->addRow($row);
                }

                $userRow = $tb->createRow();

                foreach($actionLinks as $actionLink) {
                    $userRow->addCol($tb->createCol()->setText($actionLink));
                }

                $userRow->addCol($tb->createCol()->setText($user->getFullname()));

                $tb->addRow($userRow);
            }
        }

        return $tb->build();
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