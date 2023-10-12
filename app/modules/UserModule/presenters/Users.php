<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\UserStatus;
use DMS\Core\TemplateManager;
use DMS\Entities\User;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;
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