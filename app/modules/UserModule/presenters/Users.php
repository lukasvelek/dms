<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\BulkActionRights;
use DMS\Constants\CacheCategories;
use DMS\Constants\PanelRights;
use DMS\Constants\UserActionRights;
use DMS\Constants\UserStatus;
use DMS\Core\CacheManager;
use DMS\Core\TemplateManager;
use DMS\Entities\User;
use DMS\Helpers\ArrayStringHelper;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;
use DMS\UI\FormBuilder\FormBuilder;
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

        $editLink = '';

        if($app->actionAuthorizator->checkActionRight(UserActionRights::EDIT_USER)) {
            $editLink = LinkBuilder::createAdvLink(array(
                'page' => 'UserModule:Users:showEditForm',
                'id' => $id
            ), 'Edit user');
        }

        $data = array(
            '$PAGE_TITLE$' => '<i>' . $user->getFullname() . '</i>\'s profile',
            '$USER_PROFILE_GRID$' => $this->internalCreateUserProfileGrid($id),
            '$LINKS$' => array($editLink)
        );

        $requestPasswordChangeLink = LinkBuilder::createAdvLink(array(
            'page' => 'UserModule:Users:requestPasswordChange',
            'id' => $id
        ), 'Request password change');

        $data['$LINKS$'][] = '&nbsp;&nbsp;' . $requestPasswordChangeLink;

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function requestPasswordChange() {
        global $app;

        $id = htmlspecialchars($_GET['id']);

        $data = array(
            'status' => UserStatus::PASSWORD_UPDATE_REQUIRED
        );

        $app->userModel->updateUser($id, $data);
        $app->userModel->nullUserPassword($id);

        $app->flashMessage('Request password change for user #' . $id . ' successful.');
        $app->redirect('UserModule:Users:showProfile', array('id' => $id));
    }

    protected function showEditForm() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/users/user-new-entity-form.html');

        $id = htmlspecialchars($_GET['id']);
        $user = $app->userModel->getUserById($id);

        $data = array(
            '$PAGE_TITLE$' => 'Edit user \'' . $user->getFullname() . '\'',
            '$FORM$' => $this->internalCreateEditUserForm($user)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function saveUserEdit() {
        global $app;

        $id = htmlspecialchars($_GET['id']);
        
        $required = array('firstname', 'lastname', 'username');
        
        $data = [];
        foreach($required as $r) {
            $data[$r] = htmlspecialchars($_POST[$r]);
        }

        if(isset($_POST['email']) && !empty($_POST['email'])) {
            $data['email'] = htmlspecialchars($_POST['email']);
        }
        if(isset($_POST['address_street']) && !empty($_POST['address_street'])) {
            $data['address_street'] = htmlspecialchars($_POST['address_street']);
        }
        if(isset($_POST['address_house_number']) && !empty($_POST['address_house_number'])) {
            $data['address_house_number'] = htmlspecialchars($_POST['address_house_number']);
        }
        if(isset($_POST['address_city']) && !empty($_POST['address_city'])) {
            $data['address_city'] = htmlspecialchars($_POST['address_city']);
        }
        if(isset($_POST['address_zip_code']) && !empty($_POST['address_zip_code'])) {
            $data['address_zip_code'] = htmlspecialchars($_POST['address_zip_code']);
        }
        if(isset($_POST['address_country']) && !empty($_POST['address_country'])) {
            $data['address_country'] = htmlspecialchars($_POST['address_country']);
        }

        $app->userModel->updateUser($id, $data);

        $app->flashMessage('Successfully edited user #' . $id);
        $app->redirect('UserModule:Users:showProfile', array('id' => $id));
    }

    protected function showUserRights() {
        global $app;

        if(!$app->actionAuthorizator->checkActionRight(UserActionRights::MANAGE_USER_RIGHTS)) {
            $app->redirect('UserModule:Settings:showUsers');
        }

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/users/user-rights-grid.html');

        $id = htmlspecialchars($_GET['id']);
        $user = $app->userModel->getUserById($id);

        $userRights = '';

        $app->logger->logFunction(function() use (&$userRights, $id) {
            $userRights = $this->internalCreateUserRightsGrid($id);
        }, __METHOD__);

        $links = array(
            '<a class="general-link" href="?page=UserModule:Users:allowAllRights&id_user=' . $id . '">Allow all</a>',
            '&nbsp;&nbsp;',
            '<a class="general-link" href="?page=UserModule:Users:denyAllRights&id_user=' . $id . '">Deny all</a>'
        );

        $data = array(
            '$PAGE_TITLE$' => '<i>' . $user->getFullname() . '</i> rights',
            '$USER_RIGHTS_GRID$' => $userRights,
            '$LINKS$' => '<div class="row"><div class="col-md">' . ArrayStringHelper::createUnindexedStringFromUnindexedArray($links) . '</div></div>'
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function allowAllRights() {
        global $app;

        $idUser = htmlspecialchars($_GET['id_user']);

        $allow = true;

        foreach(UserActionRights::$all as $ar) {
            $app->userRightModel->updateActionRight($idUser, $ar, $allow);
        }

        foreach(PanelRights::$all as $pr) {
            $app->userRightModel->updatePanelRight($idUser, $pr, $allow);
        }

        foreach(BulkActionRights::$all as $bar) {
            $app->userRightModel->updateBulkActionRight($idUser, $bar, $allow);
        }

        $cms = array(
            CacheManager::getTemporaryObject(CacheCategories::ACTIONS),
            CacheManager::getTemporaryObject(CacheCategories::BULK_ACTIONS),
            CacheManager::getTemporaryObject(CacheCategories::PANELS)
        );

        foreach($cms as $cm) {
            $cm->invalidateCache();
        }

        $app->redirect('UserModule:Users:showUserRights', array('id' => $idUser));
    }

    protected function denyAllRights() {
        global $app;

        $idUser = htmlspecialchars($_GET['id_user']);

        $allow = false;

        foreach(UserActionRights::$all as $ar) {
            $app->userRightModel->updateActionRight($idUser, $ar, $allow);
        }

        foreach(PanelRights::$all as $pr) {
            $app->userRightModel->updatePanelRight($idUser, $pr, $allow);
        }

        foreach(BulkActionRights::$all as $bar) {
            $app->userRightModel->updateBulkActionRight($idUser, $bar, $allow);
        }

        $cms = array(
            CacheManager::getTemporaryObject(CacheCategories::ACTIONS),
            CacheManager::getTemporaryObject(CacheCategories::BULK_ACTIONS),
            CacheManager::getTemporaryObject(CacheCategories::PANELS)
        );

        foreach($cms as $cm) {
            $cm->invalidateCache();
        }

        $app->redirect('UserModule:Users:showUserRights', array('id' => $idUser));
    }

    protected function allowActionRight() {
        global $app;

        $name = htmlspecialchars($_GET['name']);
        $idUser = htmlspecialchars($_GET['id']);

        $app->userRightModel->updateActionRight($idUser, $name, true);

        $app->logger->info('Allowed action right to user #' . $idUser, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::ACTIONS);
        $cm->invalidateCache();

        $app->redirect('UserModule:Users:showUserRights', array('id' => $idUser));
    }

    protected function denyActionRight() {
        global $app;

        $name = htmlspecialchars($_GET['name']);
        $idUser = htmlspecialchars($_GET['id']);

        $app->userRightModel->updateActionRight($idUser, $name, false);

        $app->logger->info('Denied action right to user #' . $idUser, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::ACTIONS);
        $cm->invalidateCache();

        $app->redirect('UserModule:Users:showUserRights', array('id' => $idUser));
    }

    protected function allowPanelRight() {
        global $app;

        $name = htmlspecialchars($_GET['name']);
        $idUser = htmlspecialchars($_GET['id']);

        $app->userRightModel->updatePanelRight($idUser, $name, true);

        $app->logger->info('Allowed panel right to user #' . $idUser, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::PANELS);
        $cm->invalidateCache();

        $app->redirect('UserModule:Users:showUserRights', array('id' => $idUser));
    }

    protected function denyPanelRight() {
        global $app;

        $name = htmlspecialchars($_GET['name']);
        $idUser = htmlspecialchars($_GET['id']);

        $app->userRightModel->updatePanelRight($idUser, $name, false);

        $app->logger->info('Denied panel right to user #' . $idUser, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::PANELS);
        $cm->invalidateCache();

        $app->redirect('UserModule:Users:showUserRights', array('id' => $idUser));
    }

    protected function allowBulkActionRight() {
        global $app;

        $name = htmlspecialchars($_GET['name']);
        $idUser = htmlspecialchars($_GET['id']);

        $app->userRightModel->updateBulkActionRight($idUser, $name, true);

        $app->logger->info('Allowed bulk action right to user #' . $idUser, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::BULK_ACTIONS);
        $cm->invalidateCache();

        $app->redirect('UserModule:Users:showUserRights', array('id' => $idUser));
    }

    protected function denyBulkActionRight() {
        global $app;

        $name = htmlspecialchars($_GET['name']);
        $idUser = htmlspecialchars($_GET['id']);

        $app->userRightModel->updateBulkActionRight($idUser, $name, false);

        $app->logger->info('Denied bulk action right to user #' . $idUser, __METHOD__);

        $cm = CacheManager::getTemporaryObject(CacheCategories::BULK_ACTIONS);
        $cm->invalidateCache();

        $app->redirect('UserModule:Users:showUserRights', array('id' => $idUser));
    }

    private function internalCreateUserRightsGrid(int $idUser) {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('Actions')->setBold()->setColspan('2'))
                                    ->addCol($tb->createCol()->setText('Status')->setBold())
                                    ->addCol($tb->createCol()->setText('Right name')->setBold())
                                    ->addCol($tb->createCol()->setText('Type')->setBold()))
        ;

        $rights = [];

        $defaultActionRights = UserActionRights::$all;
        $defaultPanelRights = PanelRights::$all;
        $defaultBulkActionRights = BulkActionRights::$all;

        $actionRights = $app->userRightModel->getActionRightsForIdUser($idUser);
        $panelRights = $app->userRightModel->getPanelRightsForIdUser($idUser);
        $bulkActionRights = $app->userRightModel->getBulkActionRightsForIdUser($idUser);

        foreach($defaultActionRights as $dar)  {
            $rights[$dar] = array(
                'type' => 'action',
                'name' => $dar,
                'value' => 0
            );
        }

        foreach($defaultPanelRights as $dpr) {
            $rights[$dpr] = array(
                'type' => 'panel',
                'name' => $dpr,
                'value' => 0
            );
        }

        foreach($defaultBulkActionRights as $dbar) {
            $rights[$dbar] = array(
                'type' => 'bulk',
                'name' => $dbar,
                'value' => 0
            );
        }

        foreach($actionRights as $name => $value) {
            if(array_key_exists($name, $rights)) {
                $rights[$name] = array(
                    'type' => 'action',
                    'name' => $name,
                    'value' => $value
                );
            }
        }

        foreach($bulkActionRights as $name => $value) {
            if(array_key_exists($name, $rights)) {
                $rights[$name] = array(
                    'type' => 'bulk',
                    'name' => $name,
                    'value' => $value
                );
            }
        }

        foreach($panelRights as $name => $value) {
            if(array_key_exists($name, $rights)) {
                $rights[$name] = array(
                    'type' => 'panel',
                    'name' => $name,
                    'value' => $value
                );
            }
        }

        foreach($rights as $rightname => $right) {
            $type = $right['type'];
            $name = $right['name'];
            $value = $right['value'];

            $row = $tb->createRow();

            $allowLink = '';
            $denyLink = '';

            switch($type) {
                case 'action':
                    $allowLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:allowActionRight', 'name' => $name, 'id' => $idUser), 'Allow');
                    $denyLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:denyActionRight', 'name' => $name, 'id' => $idUser), 'Deny');
                    break;

                case 'panel':
                    $allowLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:allowPanelRight', 'name' => $name, 'id' => $idUser), 'Allow');
                    $denyLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:denyPanelRight', 'name' => $name, 'id' => $idUser), 'Deny');
                    break;

                case 'bulk':
                    $allowLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:allowBulkActionRight', 'name' => $name, 'id' => $idUser), 'Allow');
                    $denyLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:denyBulkActionRight', 'name' => $name, 'id' => $idUser), 'Deny');
                    break;
            }

            $allowedText = '<span style="color: green">Allowed</span>';
            $deniedText = '<span style="color: red">Denied</span>';

            $row->addCol($tb->createCol()->setText($allowLink))
                ->addCol($tb->createCol()->setText($denyLink))
                ->addCol($tb->createCol()->setText($value ? $allowedText : $deniedText))
                ->addCol($tb->createCol()->setText($name))
                ->addCol($tb->createCol()->setText($type))
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

    private function internalCreateEditUserForm(User $user) {
        $fb = FormBuilder::getTemporaryObject();

        $fb ->setMethod('POST')->setAction('?page=UserModule:Users:saveUserEdit&id=' . $user->getId())
            ->addElement($fb->createLabel()->setFor('firstname')->setText('First name'))
            ->addElement($fb->createInput()->setType('text')->setName('firstname')->require()->setValue($user->getFirstname() ?? ''))

            ->addElement($fb->createLabel()->setFor('lastname')->setText('Last name'))
            ->addElement($fb->createInput()->setType('text')->setName('lastname')->require()->setValue($user->getLastname() ?? ''))

            ->addElement($fb->createlabel()->setFor('email')->setText('Email'))
            ->addElement($fb->createInput()->setType('email')->setName('email')->setValue($user->getEmail() ?? ''))

            ->addElement($fb->createlabel()->setFor('username')->setText('Username'))
            ->addElement($fb->createInput()->setType('text')->setName('username')->setValue($user->getUsername())->setSpecial('readonly'))

            ->addElement($fb->createlabel()->setText('Address'))
            ->addElement($fb->createlabel()->setFor('address_street')->setText('Street'))
            ->addElement($fb->createInput()->setType('text')->setName('address_street')->setValue($user->getAddressStreet() ?? ''))

            ->addElement($fb->createlabel()->setFor('address_house_number')->setText('House number'))
            ->addElement($fb->createInput()->setType('text')->setName('address_house_number')->setValue($user->getAddressHouseNumber() ?? ''))

            ->addElement($fb->createlabel()->setFor('address_city')->setText('City'))
            ->addElement($fb->createInput()->setType('text')->setName('address_city')->setValue($user->getAddressCity() ?? ''))

            ->addElement($fb->createlabel()->setFor('address_zip_code')->setText('Zip code'))
            ->addElement($fb->createInput()->setType('text')->setName('address_zip_code')->setValue($user->getAddressZipCode() ?? ''))

            ->addElement($fb->createlabel()->setFor('address_country')->setText('Country'))
            ->addElement($fb->createInput()->setType('text')->setName('address_country')->setValue($user->getAddressCountry() ?? ''))

            ->addElement($fb->createSubmit('Save'))
        ;

        $form = $fb->build();

        return $form;
    }
}

?>