<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\CacheCategories;
use DMS\Constants\Groups;
use DMS\Constants\UserActionRights;
use DMS\Core\CacheManager;
use DMS\Entities\Ribbon;
use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

class RibbonSettings extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('RibbonSettings', 'Ribbon settings');

        $this->getActionNamesFromClass($this);
    }

    protected function processNewForm() {
        global $app;

        $app->flashMessageIfNotIsset(array('name', 'code', 'parent', 'page_url'), true, array('page' => 'UserModule:RibbonSettings:showNewForm'));

        $data = [];
        $data['name'] = htmlspecialchars($_POST['name']);
        $data['code'] = htmlspecialchars($_POST['code']);
        $data['page_url'] = htmlspecialchars($_POST['page_url']);

        if($_POST['parent'] != '0') {
            $data['id_parent_ribbon'] = htmlspecialchars($_POST['parent']);
        }

        if(isset($_POST['title'])) {
            $data['title'] = htmlspecialchars($_POST['title']);
        }

        if(isset($_POST['image'])) {
            $data['image'] = htmlspecialchars($_POST['image']);
        }

        if(isset($_POST['is_visible'])) {
            $data['is_visible'] = '1';
        } else {
            $data['is_visible'] = '0';
        }

        $app->ribbonModel->insertNewRibbon($data);
        $idRibbon = $app->ribbonModel->getLastInsertedRibbonId();

        if($idRibbon === FALSE) {
            die();
        }

        $admGroup = $app->groupModel->getGroupByCode('ADMINISTRATORS');
        $app->ribbonRightsModel->insertAllGrantedRightsForGroup($idRibbon, $admGroup->getId());

        /*$admin = $app->userModel->getUserByUsername('admin');
        $app->ribbonRightsModel->insertAllGrantedRightsForUser($idRibbon, $admin->getId());*/

        $app->ribbonRightsModel->insertAllGrantedRightsForUser($idRibbon, $app->user->getId());

        $app->flashMessage('Created new ribbon');

        $app->redirect('UserModule:RibbonSettings:showAll');
    }

    protected function showNewForm() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-new-entity-form.html');

        $data = array(
            '$PAGE_TITLE$' => 'New ribbon form',
            '$FORM$' => $this->internalCreateNewRibbonForm()
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function clearCache() {
        global $app;
        
        $rcm = CacheManager::getTemporaryObject(CacheCategories::RIBBONS);
        $rcm->invalidateCache();
        
        $rucm = CacheManager::getTemporaryObject(CacheCategories::RIBBON_USER_RIGHTS);
        $rucm->invalidateCache();
        
        $rgcm = CacheManager::getTemporaryObject(CacheCategories::RIBBON_GROUP_RIGHTS);
        $rgcm->invalidateCache();
        
        unset($rcm, $rucm, $rgcm);
        
        $rcm = CacheManager::getTemporaryObject(CacheCategories::RIBBONS);
        
        $ribbons = $app->ribbonModel->getAllRibbons();
        
        foreach($ribbons as $ribbon) {
            $rcm->saveRibbon($ribbon);
        }
        
        unset($rcm);
        
        $app->redirect('UserModule:RibbonSettings:showAll');
    }

    protected function showAll() {
        global $app;

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/settings/settings-grid-wider.html');

        $settingsGrid = '';

        $app->logger->logFunction(function() use (&$settingsGrid) {
            $settingsGrid = $this->internalCreateRibbonGrid();
        }, __METHOD__);

        $data = array(
            '$PAGE_TITLE$' => 'Ribbon settings',
            '$LINKS$' => [],
            '$SETTINGS_GRID$' => $settingsGrid
        );

        if($app->actionAuthorizator->checkActionRight(UserActionRights::CREATE_RIBBONS)) {
            $data['$LINKS$'][] = LinkBuilder::createAdvLink(array('page' => 'UserModule:RibbonSettings:showNewForm'), 'New ribbon');
        }

        $data['$LINKS$'][] = '&nbsp;&nbsp;' . LinkBuilder::createAdvLink(array('page' => 'UserModule:RibbonSettings:clearCache'), 'Clear cache');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateRibbonGrid() {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $headers = array(
            'Actions',
            'Name',
            'Code',
            'Visible',
            'URL'
        );

        $headerRow = null;
        $ribbons = [];

        $app->logger->logFunction(function() use ($app, &$ribbons) {
            $ribbons = $app->ribbonModel->getAllRibbons(true);
        }, __METHOD__);

        if(empty($ribbons)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($ribbons as $ribbon) {
                if(!($ribbon instanceof Ribbon)) {
                    continue;
                }

                $actionLinks = array(
                    'edit' => '-',
                    'edit_rights' => '-',
                    'delete' => '-'
                );

                if($app->ribbonAuthorizator->checkRibbonEditable($app->user->getId(), $ribbon) &&
                   $app->actionAuthorizator->checkActionRight(UserActionRights::EDIT_RIBBONS)) {
                    $actionLinks['edit'] = LinkBuilder::createAdvLink(array('page' => 'UserModule:RibbonSettings:showEditForm', 'id' => $ribbon->getId(), 'id_ribbon' => $app->currentIdRibbon), 'Edit');
                }

                if($app->ribbonAuthorizator->checkRibbonDeletable($app->user->getId(), $ribbon) &&
                   $app->actionAuthorizator->checkActionRight(UserActionRights::DELETE_RIBBONS)) {
                    $actionLinks['delete'] = LinkBuilder::createAdvLink(array('page' => 'UserModule:RibbonSettings:deleteRibbon', 'id' => $ribbon->getId(), 'id_ribbon' => $app->currentIdRibbon), 'Delete');
                }

                if($app->actionAuthorizator->checkActionRight(UserActionRights::EDIT_RIBBON_RIGHTS)) {
                    $actionLinks['edit_rights'] = LinkBuilder::createAdvLink(array('page' => 'UserModule:RibbonSettings:showEditRightsForm', 'id' => $ribbon->getId(), 'id_ribbon' => $app->currentIdRibbon), 'Edit rights');
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

                $ribbonRow = $tb->createRow();

                foreach($actionLinks as $actionLink) {
                    $ribbonRow->addCol($tb->createCol()->setText($actionLink));
                }

                $visible = $ribbon->isVisible() ? '<span style="color: green">Yes</span>' : '<span style="color: red">No</span>';

                $ribbonRow  ->addCol($tb->createCol()->setText($ribbon->getName()))
                            ->addCol($tb->createCol()->setText($ribbon->getCode()))
                            ->addCol($tb->createCol()->setText($visible))
                            ->addCol($tb->createCol()->setText($ribbon->getPageUrl()))
                ;

                $tb->addRow($ribbonRow);
            }
        }
        
        return $tb->build();
    }

    private function internalCreateNewRibbonForm() {
        global $app;

        $fb = FormBuilder::getTemporaryObject();

        $parentRibbons = null;

        $cm = CacheManager::getTemporaryObject(CacheCategories::RIBBONS);
        $valFromCache = $cm->loadTopRibbons();

        if(!is_null($valFromCache)) {
            $parentRibbons = $valFromCache;
        } else {
            $parentRibbons = $app->ribbonModel->getToppanelRibbons();
        }

        $parentRibbonsArr = [['value' => '0', 'text' => '- (root)']];
        foreach($parentRibbons as $ribbon) {
            $parentRibbonsArr[] = array(
                'value' => $ribbon->getId(),
                'text' => $ribbon->getName() . ' (' . $ribbon->getCode() . ')'
            );
        }

        $fb
            ->setAction('?page=UserModule:RibbonSettings:processNewForm')
            ->setMethod('POST')

            ->addElement($fb->createLabel()->setText('Name')->setFor('name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->setMaxLength('256')->require())

            ->addElement($fb->createLabel()->setText('Title (display name, can be same as Name)'))
            ->addElement($fb->createInput()->setType('text')->setName('title')->setMaxLength('256'))

            ->addElement($fb->createLabel()->setText('Code'))
            ->addElement($fb->createInput()->setType('text')->setName('code')->setMaxLength('256')->require())

            ->addElement($fb->createLabel()->setText('Page URL'))
            ->addElement($fb->createInput()->setType('text')->setName('page_url')->setMaxLength('256')->require())

            ->addElement($fb->createLabel()->setText('Parent'))
            ->addElement($fb->createSelect()->setName('parent')->addOptionsBasedOnArray($parentRibbonsArr))

            ->addElement($fb->createLabel()->setText('Image'))
            ->addElement($fb->createInput()->setType('text')->setName('title')->setMaxLength('256'))

            ->addElement($fb->createLabel()->setText('Is visible'))
            ->addElement($fb->createInput()->setType('checkbox')->setName('is_visible')->setSpecial('checked'))

            ->addElement($fb->createSubmit('Create'))
        ;

        return $fb->build();
    }
}

?>