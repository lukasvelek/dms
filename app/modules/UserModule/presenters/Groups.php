<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\BulkActionRights;
use DMS\Constants\PanelRights;
use DMS\Constants\UserActionRights;
use DMS\Constants\UserStatus;
use DMS\Core\CacheManager;
use DMS\Core\TemplateManager;
use DMS\Modules\APresenter;
use DMS\Modules\IModule;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

class Groups extends APresenter {
    private string $name;
    private TemplateManager $templateManager;
    private IModule $module;

    public const DRAW_TOPPANEL = true;

    public function __construct() {
        $this->name = 'Groups';

        $this->templateManager = TemplateManager::getTemporaryObject();
    }

    public function setModule(IModule $module) {
        $this->module = $module;
    }

    public function getModuleE() {
        return $this->module;
    }

    public function getName() {
        return $this->name;
    }

    protected function showUsers() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/groups/groups-grid.html');

        if(!isset($_GET['id'])) {
            $app->redirect('UserModule:Settings:showGroups');
        }

        $id = htmlspecialchars($_GET['id']);
        $group = $app->groupModel->getGroupById($id);

        $data = array(
            '$PAGE_TITLE$' => 'Users in group <i>' . $group->getName() . '</i>',
            '$GROUP_GRID$' => $this->internalCreateGroupGrid($id)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showGroupRights() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/groups/group-rights-grid.html');

        $id = htmlspecialchars($_GET['id']);
        $group = $app->groupModel->getGroupById($id);

        $data = array(
            '$PAGE_TITLE$' => '<i>' . $group->getName() . '</i> rights',
            '$GROUP_RIGHTS_GRID$' => $this->internalCreateGroupRightsGrid($id)
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function allowActionRight() {
        global $app;

        $name = htmlspecialchars($_GET['name']);
        $idGroup = htmlspecialchars($_GET['id']);

        $app->groupRightModel->updateActionRight($idGroup, $name, true);

        $cm = CacheManager::getTemporaryObject();
        $cm->invalidateCache();

        $app->redirect('UserModule:Groups:showGroupRights', array('id' => $idGroup));
    }

    protected function denyActionRight() {
        global $app;

        $name = htmlspecialchars($_GET['name']);
        $idGroup = htmlspecialchars($_GET['id']);

        $app->groupRightModel->updateActionRight($idGroup, $name, false);

        $cm = CacheManager::getTemporaryObject();
        $cm->invalidateCache();

        $app->redirect('UserModule:Groups:showGroupRights', array('id' => $idGroup));
    }

    protected function allowPanelRight() {
        global $app;

        $name = htmlspecialchars($_GET['name']);
        $idGroup = htmlspecialchars($_GET['id']);

        $app->groupRightModel->updatePanelRight($idGroup, $name, true);

        $cm = CacheManager::getTemporaryObject();
        $cm->invalidateCache();

        $app->redirect('UserModule:Groups:showGroupRights', array('id' => $idGroup));
    }

    protected function denyPanelRight() {
        global $app;

        $name = htmlspecialchars($_GET['name']);
        $idGroup = htmlspecialchars($_GET['id']);

        $app->groupRightModel->updatePanelRight($idGroup, $name, false);

        $cm = CacheManager::getTemporaryObject();
        $cm->invalidateCache();

        $app->redirect('UserModule:Groups:showGroupRights', array('id' => $idGroup));
    }

    protected function allowBulkActionRight() {
        global $app;

        $name = htmlspecialchars($_GET['name']);
        $idGroup = htmlspecialchars($_GET['id']);

        $app->groupRightModel->updateBulkActionRight($idGroup, $name, true);

        $cm = CacheManager::getTemporaryObject();
        $cm->invalidateCache();

        $app->redirect('UserModule:Groups:showGroupRights', array('id' => $idGroup));
    }

    protected function denyBulkActionRight() {
        global $app;

        $name = htmlspecialchars($_GET['name']);
        $idGroup = htmlspecialchars($_GET['id']);

        $app->groupRightModel->updateBulkActionRight($idGroup, $name, false);

        $cm = CacheManager::getTemporaryObject();
        $cm->invalidateCache();

        $app->redirect('UserModule:Groups:showGroupRights', array('id' => $idGroup));
    }

    private function internalCreateGroupRightsGrid(int $idGroup) {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('Actions')->setBold()->setColspan('2'))
                                    ->addCol($tb->createCol()->setText('Type')->setBold())
                                    ->addCol($tb->createCol()->setText('Right name')->setBold())
                                    ->addCol($tb->createCol()->setText('Status')->setBold()));

        $rights = [];

        $defaultActionRights = UserActionRights::$all;
        $defaultPanelRights = PanelRights::$all;
        $defaultBulkActionRights = BulkActionRights::$all;

        $actionRights = $app->groupRightModel->getActionRightsForIdGroup($idGroup);
        $panelRights = $app->groupRightModel->getPanelRightsForIdGroup($idGroup);
        $bulkActionRights = $app->groupRightModel->getBulkActionRightsForIdGroup($idGroup);

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
                    $allowLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:allowActionRight', 'name' => $name, 'id' => $idGroup), 'Allow');
                    $denyLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:denyActionRight', 'name' => $name, 'id' => $idGroup), 'Deny');
                    break;

                case 'panel':
                    $allowLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:allowPanelRight', 'name' => $name, 'id' => $idGroup), 'Allow');
                    $denyLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:denyPanelRight', 'name' => $name, 'id' => $idGroup), 'Deny');
                    break;

                case 'bulk':
                    $allowLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:allowBulkActionRight', 'name' => $name, 'id' => $idGroup), 'Allow');
                    $denyLink = LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:denyBulkActionRight', 'name' => $name, 'id' => $idGroup), 'Deny');
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

    private function internalCreateGroupGrid(int $idGroup) {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $headers = array(
            'Actions',
            'First name',
            'Last name',
            'User name',
            'Status',
            'Is manager'
        );

        $headerRow = null;

        $groupUsers = $app->groupUserModel->getGroupUsersByGroupId($idGroup);

        if(empty($groupUsers)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($groupUsers as $groupUser) {
                $user = $app->userModel->getUserById($groupUser->getIdUser());

                $actionLinks = array(
                    LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showProfile', 'id' => $user->getId()), 'Profile')
                );

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

                $userRow->addCol($tb->createCol()->setText($user->getFirstname()))
                        ->addCol($tb->createCol()->setText($user->getLastname()))
                        ->addCol($tb->createCol()->setText($user->getUsername()))
                        ->addCol($tb->createCol()->setText(UserStatus::$texts[$user->getStatus()]))
                        ->addCol($tb->createCol()->setText($groupUser->getIsManager() ? 'Yes' : 'No'))
                ;

                $tb->addRow($userRow);
            }
        }

        return $tb->build();
    }
}

?>