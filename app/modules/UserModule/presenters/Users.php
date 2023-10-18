<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\UserActionRights;
use DMS\Constants\UserStatus;
use DMS\Core\CacheManager;
use DMS\Core\TemplateManager;
use DMS\Entities\User;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

class Users extends APresenter {
    private string $name;
    private TemplateManager $templateManager;
    private IModule $module;

    public const DRAW_TOPPANEL = true;

    public function __construct() {
        $this->name = 'Users';

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

    protected function showProfile() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/users/user-profile-grid.html');

        if(!isset($_GET['id'])) {
            $app->redirect('UserModule:HomePage:showHomepage');
        }

        $id = htmlspecialchars($_GET['id']);

        $user = $app->userModel->getUserById($id);

        $data = array(
            '$PAGE_TITLE$' => '<i>' . $user->getFullname() . '</i>',
            '$USER_PROFILE_GRID$' => $this->internalCreateUserProfileGrid($id)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showUserRights() {
        global $app;

        if(!$app->actionAuthorizator->checkActionRight(UserActionRights::MANAGE_USER_RIGHTS)) {
            $app->redirect('UserModule:Settings:showUsers');
        }

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/users/user-rights-grid.html');

        $id = htmlspecialchars($_GET['id']);
        $user = $app->userModel->getUserById($id);

        $data = array(
            '$PAGE_TITLE$' => '<i>' . $user->getFullname() . '</i> rights',
            '$USER_RIGHTS_GRID$' => $this->internalCreateUserRightsGrid($id)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateUserRightsGrid(int $idUser) {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('Actions')->setBold()->setColspan('2'))
                                    ->addCol($tb->createCol()->setText('Type')->setBold())
                                    ->addCol($tb->createCol()->setText('Right name')->setBold())
                                    ->addCol($tb->createCol()->setText('Status')->setBold()));

        $rights = [];

        $actionRights = $app->userRightModel->getActionRightsForIdUser($idUser);
        $panelRights = $app->userRightModel->getPanelRightsForIdUser($idUser);
        $bulkActionRights = $app->userRightModel->getBulkActionRightsForIdUser($idUser);

        foreach($actionRights as $name => $value) {
            $rights[] = array(
                'type' => 'action',
                'name' => $name,
                'value' => $value
            );
        }

        foreach($bulkActionRights as $name => $value) {
            $rights[] = array(
                'type' => 'bulk',
                'name' => $name,
                'value' => $value
            );
        }

        foreach($panelRights as $name => $value) {
            $rights[] = array(
                'type' => 'panel',
                'name' => $name,
                'value' => $value
            );
        }

        foreach($rights as $right) {
            $type = $right['type'];
            $name = $right['name'];
            $value = $right['value'];

            $row = $tb->createRow();

            $allowLink = '';
            $denyLink = '';

            switch($type) {
                case 'action':
                    $allowLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:allowActionRight', 'name' => $name), 'Allow');
                    $denyLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:denyActionRight', 'name' => $name), 'Deny');
                    break;

                case 'panel':
                    $allowLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:allowPanelRight', 'name' => $name), 'Allow');
                    $denyLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:denyPanelRight', 'name' => $name), 'Deny');
                    break;

                case 'bulk':
                    $allowLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:allowBulkActionRight', 'name' => $name), 'Allow');
                    $denyLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:denyBulkActionRight', 'name' => $name), 'Deny');
                    break;
            }

            $allowedText = '<span style="color: green">Allowed</span>';
            $deniedText = '<span style="color: red">Denied</span>';

            $row->addCol($tb->createCol()->setText($allowLink))
                ->addCol($tb->createCol()->setText($denyLink))
                ->addCol($tb->createCol()->setText($type))
                ->addCol($tb->createCol()->setText($name))
                ->addCol($tb->createCol()->setText($value ? $allowedText : $deniedText))
            ;

            $tb->addRow($row);
        }

        $table = $tb->build();

        return $table;
    }

    protected function allowActionRight() {
        global $app;

        $name = htmlspecialchars($_GET['name']);
        $idUser = $app->user->getId();

        $app->userRightModel->updateActionRight($idUser, $name, true);

        $cm = CacheManager::getTemporaryObject();
        $cm->invalidateCache();

        $app->redirect('UserModule:Users:showUserRights', array('id' => $idUser));
    }

    protected function denyActionRight() {
        global $app;

        $name = htmlspecialchars($_GET['name']);
        $idUser = $app->user->getId();

        $app->userRightModel->updateActionRight($idUser, $name, false);

        $cm = CacheManager::getTemporaryObject();
        $cm->invalidateCache();

        $app->redirect('UserModule:Users:showUserRights', array('id' => $idUser));
    }

    protected function allowPanelRight() {
        global $app;

        $name = htmlspecialchars($_GET['name']);
        $idUser = $app->user->getId();

        $app->userRightModel->updatePanelRight($idUser, $name, true);

        $cm = CacheManager::getTemporaryObject();
        $cm->invalidateCache();

        $app->redirect('UserModule:Users:showUserRights', array('id' => $idUser));
    }

    protected function denyPanelRight() {
        global $app;

        $name = htmlspecialchars($_GET['name']);
        $idUser = $app->user->getId();

        $app->userRightModel->updatePanelRight($idUser, $name, false);

        $cm = CacheManager::getTemporaryObject();
        $cm->invalidateCache();

        $app->redirect('UserModule:Users:showUserRights', array('id' => $idUser));
    }

    protected function allowBulkActionRight() {
        global $app;

        $name = htmlspecialchars($_GET['name']);
        $idUser = $app->user->getId();

        $app->userRightModel->updateBulkActionRight($idUser, $name, true);

        $cm = CacheManager::getTemporaryObject();
        $cm->invalidateCache();

        $app->redirect('UserModule:Users:showUserRights', array('id' => $idUser));
    }

    protected function denyBulkActionRight() {
        global $app;

        $name = htmlspecialchars($_GET['name']);
        $idUser = $app->user->getId();

        $app->userRightModel->updateBulkActionRight($idUser, $name, false);

        $cm = CacheManager::getTemporaryObject();
        $cm->invalidateCache();

        $app->redirect('UserModule:Users:showUserRights', array('id' => $idUser));
    }

    private function internalCreateUserProfileGrid(int $idUser) {
        global $app;

        $user = $app->userModel->getUserById($idUser);

        $code = '';
        $code .= '<table border="1">';

        $data = array(
            'First name' => $user->getFirstname() ?? '-',
            'Last name' => $user->getLastname() ?? '-',
            'Username' => $user->getUsername() ?? '-',
            'Email' => $user->getEmail() ?? '-',
            'Status' => UserStatus::$texts[$user->getStatus()],
            'Address' => '',
            'House number' => $user->getAddressHouseNumber() ?? '-',
            'Street' => $user->getAddressStreet() ?? '-',
            'City' => $user->getAddressCity() ?? '-',
            'Zip code' => $user->getAddressZipCode() ?? '-',
            'Country' => $user->getAddressCountry() ?? '-'
        );

        foreach($data as $key => $value) {
            $code .= '<tr>';
            $code .= '<th>' . $key . '</th>';
            $code .= '<td>' . $value . '</td>';
            $code .= '</tr>';
        }

        $code .= '</table>';

        return $code;
    }
}

?>