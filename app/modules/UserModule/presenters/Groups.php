<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\UserStatus;
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