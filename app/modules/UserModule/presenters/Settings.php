<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\UserActionRights;
use DMS\Constants\UserStatus;
use DMS\Core\TemplateManager;
use DMS\Helpers\ArrayStringHelper;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;
use DMS\Panels\Panels;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;
use DMS\UI\FormBuilder\Option;

class Settings extends APresenter {
    private string $name;
    private TemplateManager $templateManager;
    private IModule $module;

    public const DRAW_TOPPANEL = true;

    public function __construct() {
        $this->name = 'Settings';

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

    protected function showDashboard() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-dashboard.html');

        $data = array(
            '$PAGE_TITLE$' => 'Settings',
            '$SETTINGS_PANEL$' => Panels::createSettingsPanel()
        );

        $widgets = $this->internalDashboardCreateWidgets();

        $data['$WIDGETS$'] = $widgets;

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showUsers() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-grid.html');

        $data = array(
            '$PAGE_TITLE$' => 'Users',
            '$NEW_ENTITY_LINK$' => '',
            '$SETTINGS_GRID$' => $this->internalCreateUsersGrid(),
            '$SETTINGS_PANEL$' => Panels::createSettingsPanel()
        );

        if($app->actionAuthorizator->checkActionRight('create_user')) {
            $data['$NEW_ENTITY_LINK$'] = '<div class="row"><div class="col-md" id="right">' . LinkBuilder::createLink('UserModule:Settings:showNewUserForm', 'New user') . '</div></div>';
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showGroups() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-grid.html');

        $data = array(
            '$PAGE_TITLE$' => 'Groups',
            '$NEW_ENTITY_LINK$' => '',
            '$SETTINGS_GRID$' => $this->internalCreateGroupGrid(),
            '$SETTINGS_PANEL$' => Panels::createSettingsPanel()
        );

        if($app->actionAuthorizator->checkActionRight('create_group')) {
            $data['$NEW_ENTITY_LINK$'] = '<div class="row"><div class="col-md" id="right">' . LinkBuilder::createLink('UserModule:Settings:showNewGroupForm', 'New group') . '</div></div>';
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showMetadata() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-grid.html');

        $data = array(
            '$PAGE_TITLE$' => 'Metadata manager',
            '$SETTINGS_PANEL$' => Panels::createSettingsPanel(),
            '$SETTINGS_GRID$' => $this->internalCreateMetadataGrid()
        );

        if($app->actionAuthorizator->checkActionRight('create_metadata')) {
            $data['$NEW_ENTITY_LINK$'] = '<div class="row"><div class="col-md" id="right">' . LinkBuilder::createLink('UserModule:Settings:showNewMetadataForm', 'New metadata') . '</div></div>';
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showNewMetadataForm() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'New metadata form',
            '$SETTINGS_PANEL$' => Panels::createSettingsPanel(),
            '$FORM$' => $this->internalCreateNewMetadataForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showNewUserForm() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $data = array(
            '$SETTINGS_PANEL$' => Panels::createSettingsPanel(),
            '$PAGE_TITLE$' => 'New user form',
            '$FORM$' => $this->internalCreateNewUserForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showNewGroupForm() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $data = array(
            '$SETTINGS_PANEL$' => Panels::createSettingsPanel(),
            '$PAGE_TITLE$' => 'New group form',
            '$FORM$' => $this->internalCreateNewGroupForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function createNewMetadata() {
        global $app;

        $name = htmlspecialchars($_POST['name']);
        $text = htmlspecialchars($_POST['text']);
        $tableName = htmlspecialchars($_POST['table_name']);
        $type = htmlspecialchars($_POST['type']);
        $length = htmlspecialchars($_POST['length']);

        $app->metadataModel->insertNewMetadata($name, $text, $tableName);
        $idMetadata = $app->metadataModel->getLastInsertedMetadata()->getId();

        $app->tableModel->addColToTable($tableName, $name, $type, $length);

        $app->redirect('UserModule:Metadata:showValues', array('id' => $idMetadata));
    }

    protected function deleteMetadata() {
        global $app;

        $id = htmlspecialchars($_GET['id']);
        $metadata = $app->metadataModel->getMetadataById($id);

        // delete table column
        // delete values
        // delete metadata

        $app->tableModel->removeColFromTable($metadata->getTableName(), $metadata->getName());
        $app->metadataModel->deleteMetadataValues($id);
        $app->metadataModel->deleteMetadata($id);

        $app->redirect('UserModule:Settings:showMetadata');
    }

    protected function createNewGroup() {
        global $app;

        $name = htmlspecialchars($_POST['name']);
        $code = null;

        if(isset($_POST['code'])) {
            $code = htmlspecialchars($_POST['code']);
        }

        $app->groupModel->insertNewGroup($name, $code);
        $idGroup = $app->groupModel->getLastInsertedGroup()->getId();

        $app->groupRightModel->insertActionRightsForIdGroup($idGroup);
        $app->groupRightModel->insertPanelRightsForIdGroup($idGroup);
        $app->groupRightModel->insertBulkActionRightsForIdGroup($idGroup);

        $app->redirect('UserModule:Groups:showUsers', array('id' => $idGroup));
    }

    protected function createNewUser() {
        global $app;

        $data = [];

        $required = array('firstname', 'lastname', 'username');

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

        $data['status'] = UserStatus::PASSWORD_CREATION_REQUIRED;

        $app->userModel->insertUserFromArray($data);
        $idUser = $app->userModel->getLastInsertedUser()->getId();

        $app->userRightModel->insertActionRightsForIdUser($idUser);
        $app->userRightModel->insertPanelRightsForIdUser($idUser);
        $app->userRightModel->insertBulkActionRightsForIdUser($idUser);

        $app->redirect('UserModule:Users:showProfile', array('id' => $idUser));
    }

    private function internalCreateNewGroupForm() {
        $fb = FormBuilder::getTemporaryObject();

        $fb ->setAction('?page=UserModule:Settings:createNewGroup')->setMethod('POST')
            ->addElement($fb->createLabel()->setFor('name')->setText('Group name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->require())

            ->addElement($fb->createLabel()->setFor('code')->setText('Code'))
            ->addElement($fb->createInput()->setType('text')->setName('code'))

            ->addElement($fb->createSubmit('Create'))
        ;

        $form = $fb->build();

        return $form;
    }

    private function internalCreateNewUserForm() {
        $fb = FormBuilder::getTemporaryObject();

        $fb ->setMethod('POST')->setAction('?page=UserModule:Settings:createNewUser')
            ->addElement($fb->createLabel()->setFor('firstname')->setText('First name'))
            ->addElement($fb->createInput()->setType('text')->setName('firstname')->require())

            ->addElement($fb->createLabel()->setFor('lastname')->setText('Last name'))
            ->addElement($fb->createInput()->setType('text')->setName('lastname')->require())

            ->addElement($fb->createlabel()->setFor('email')->setText('Email'))
            ->addElement($fb->createInput()->setType('email')->setName('email'))

            ->addElement($fb->createlabel()->setFor('username')->setText('Username'))
            ->addElement($fb->createInput()->setType('text')->setName('username')->require())

            ->addElement($fb->createlabel()->setText('Address'))
            ->addElement($fb->createlabel()->setFor('address_street')->setText('Street'))
            ->addElement($fb->createInput()->setType('text')->setName('address_street'))

            ->addElement($fb->createlabel()->setFor('address_house_number')->setText('House number'))
            ->addElement($fb->createInput()->setType('text')->setName('address_house_number'))

            ->addElement($fb->createlabel()->setFor('address_city')->setText('City'))
            ->addElement($fb->createInput()->setType('text')->setName('address_city'))

            ->addElement($fb->createlabel()->setFor('address_zip_code')->setText('Zip code'))
            ->addElement($fb->createInput()->setType('text')->setName('address_zip_code'))

            ->addElement($fb->createlabel()->setFor('address_country')->setText('Country'))
            ->addElement($fb->createInput()->setType('text')->setName('address_country'))

            ->addElement($fb->createSubmit('Create'))
        ;

        $form = $fb->build();

        return $form;
    }

    private function internalCreateNewMetadataForm() {
        $fb = FormBuilder::getTemporaryObject();

        $fb ->setMethod('POST')->setAction('?page=UserModule:Settings:createNewMetadata')
            ->addElement($fb->createLabel()->setFor('name')->setText('Name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->require())

            ->addElement($fb->createLabel()->setFor('text')->setText('Text'))
            ->addElement($fb->createInput()->setType('text')->setName('text')->require())

            ->addElement($fb->createLabel()->setFor('table_name')->setText('Database table name'))
            ->addElement($fb->createInput()->setType('text')->setName('table_name')->require())

            ->addElement($fb->createLabel()->setFor('type')->setText('Type'))
            ->addElement($fb->createSelect()->setName('type')->addOptions(array(
                (new Option())->setValue('INT')->setText('int'),
                (new Option())->setValue('VARCHAR')->setText('varchar')
            )))

            ->addElement($fb->createLabel()->setFor('length')->setText('Length'))
            ->addElement($fb->createInput()->setType('text')->setName('length')->require())

            ->addElement($fb->createSubmit('Create'))
        ;

        return $fb->build();
    }

    private function internalCreateGroupGrid() {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $headers = array(
            'Actions',
            'Name',
            'Code'
        );

        $headerRow = null;

        $groups = $app->groupModel->getAllGroups();

        if(empty($groups)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($groups as $group) {
                $actionLinks = array(
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:showUsers', 'id' => $group->getId()), 'Users')
                );

                if($app->actionAuthorizator->checkActionRight(UserActionRights::MANAGE_GROUP_RIGHTS)) {
                    $actionLinks[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:showGroupRights', 'id' => $group->getId()), 'Group rights');
                }

                if(is_null($headerRow)) {
                    $row = $tb->createRow();

                    foreach($headers as $header) {
                        $col = $tb->createCol()->setText($header)
                                               ->setBold();

                        if($header == 'Actions') {
                            $col->setColspan(count($actionLinks));
                        }

                        $row->addCol($col);
                    }

                    $headerRow = $row;
                
                    $tb->addRow($row);
                }

                $groupRow = $tb->createRow();

                foreach($actionLinks as $actionLink) {
                    $groupRow->addCol($tb->createCol()->setText($actionLink));
                }

                $groupData = array(
                    $group->getName() ?? '-',
                    $group->getCode() ?? '-'
                );

                foreach($groupData as $gd) {
                    $groupRow->addCol($tb->createCol()->setText($gd));
                }

                $tb->addRow($groupRow);
            }
        }

        return $tb->build();
    }

    private function internalCreateUsersGrid() {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $headers = array(
            'Actions',
            'Firstname',
            'Lastname',
            'Username',
            'Email',
            'Status',
            'Address Street',
            'Address House number',
            'Address City',
            'Address Zip code',
            'Address Country'
        );

        $headerRow = null;

        $users = $app->userModel->getAllUsers();

        if(empty($users)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($users as $user) {
                $actionLinks = array(
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showProfile', 'id' => $user->getId()), 'Profile')
                );

                if($app->actionAuthorizator->checkActionRight(UserActionRights::MANAGE_USER_RIGHTS)) {
                    $actionLinks[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showUserRights', 'id' => $user->getId()), 'User rights');
                }

                if(is_null($headerRow)) {
                    $row = $tb->createRow();

                    foreach($headers as $header) {
                        $col = $tb->createCol()->setText($header)
                                               ->setBold();

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

                $userData = array(
                    $user->getFirstname() ?? '-',
                    $user->getLastname() ?? '-',
                    $user->getUsername() ?? '-',
                    $user->getEmail() ?? '-',
                    UserStatus::$texts[$user->getStatus()],
                    $user->getAddressStreet() ?? '-',
                    $user->getAddressHouseNumber() ?? '-',
                    $user->getAddressCity() ?? '-',
                    $user->getAddressZipCode() ?? '-',
                    $user->getAddressCountry() ?? '-'
                );

                foreach($userData as $ud) {
                    $userRow->addCol($tb->createCol()->setText($ud));
                }

                $tb->addRow($userRow);
            }
        }

        return $tb->build();
    }

    private function internalDashboardCreateWidgets() {
        $widgets = array($this->internalCreateCountWidget());

        $code = array();
        $code[] = '<div class="row">';

        $i = 0;
        foreach($widgets as $widget) {
            $code[] = $widget;

            if(($i + 1) == count($widgets) || ($i % 2) == 0) {
                $code[] = '</div>';
            }
        }

        return ArrayStringHelper::createUnindexedStringFromUnindexedArray($code);
    }

    private function internalCreateCountWidget() {
        global $app;

        $users = count($app->userModel->getAllUsers());
        $groups = count($app->groupModel->getAllGroups());
        $documents = count($app->documentModel->getAllDocuments());

        $code = '<div class="col-md">
                    <div class="row">
                        <div class="col-md" id="center">
                            <p class="page-title">Statistics</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md">
                            <p><b>Total users: </b>' . $users . '</p>
                            <p><b>Total groups: </b>' . $groups . '</p>
                            <p><b>Total documents: </b>' . $documents . '</p>
                        </div>
                    </div>
                </div>';

        return $code;
    }

    private function internalCreateMetadataGrid() {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $headers = array(
            'Actions',
            'Name',
            'Text',
            'Database table'
        );
        
        $headerRow = null;

        $metadata = $app->metadataModel->getAllMetadata();

        if(empty($metadata)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($metadata as $m) {
                $actionLinks = array(
                    'values' => '-',
                    'delete' => '-'
                );

                if(!$app->metadataAuthorizator->canUserViewMetadata($app->user->getId(), $m->getId())) continue;

                if($app->metadataAuthorizator->canUserEditMetadata($app->user->getId(), $m->getId()) && !$m->getIsSystem()) {
                    $actionLinks['delete'] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Settings:deleteMetadata', 'id' => $m->getId()), 'Delete');
                }

                if($app->metadataAuthorizator->canUserViewMetadataValues($app->user->getId(), $m->getId())) {
                    $actionLinks['values'] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Metadata:showValues', 'id' => $m->getId()), 'Values');
                }

                if(is_null($headerRow)) {
                    $row = $tb->createRow();

                    foreach($headers as $header) {
                        $col = $tb->createCol()->setText($header)
                                               ->setBold();

                        if($header == 'Actions') {
                            $col->setColspan(count($actionLinks));
                        }

                        $row->addCol($col);
                    }

                    $headerRow = $row;

                    $tb->addRow($row);
                }

                $metaRow = $tb->createRow();

                foreach($actionLinks as $actionLink) {
                    $metaRow->addCol($tb->createCol()->setText($actionLink));
                }

                $metaArray = array(
                    $m->getName(),
                    $m->getText(),
                    $m->getTableName()
                );

                foreach($metaArray as $ma) {
                    $metaRow->addCol($tb->createCol()->setText($ma));
                }

                $tb->addRow($metaRow);
            }
        }

        return $tb->build();
    }
}

?>