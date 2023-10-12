<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\UserStatus;
use DMS\Core\TemplateManager;
use DMS\Entities\User;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

class Users extends APresenter {
    /**
     * @var string
     */
    private $name;

    /**
     * @var TemplateManager
     */
    private $templateManager;

    /**
     * @var IModule
     */
    private $module;

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
                                    ->addCol($tb->createCol()->setText('Right name')->setBold())
                                    ->addCol($tb->createCol()->setText('Status')->setBold()));

        $rights = [];

        $actionRights = $app->userRightModel->getActionRightsForIdUser($app->user->getId());

        foreach($actionRights as $name => $value) {
            $rights[] = array(
                'type' => 'action',
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
            }

            $row->addCol($tb->createCol()->setText($allowLink))
                ->addCol($tb->createCol()->setText($denyLink))
                ->addCol($tb->createCol()->setText($name))
                ->addCol($tb->createCol()->setText($value ? 'Allowed' : 'Denied'))
            ;

            $tb->addRow($row);
        }

        $table = $tb->build();

        return $table;
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