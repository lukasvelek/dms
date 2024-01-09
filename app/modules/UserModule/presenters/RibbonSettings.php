<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\UserActionRights;
use DMS\Entities\Ribbon;
use DMS\Modules\APresenter;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

class RibbonSettings extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('RibbonSettings', 'Ribbon settings');

        $this->getActionNamesFromClass($this);
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
            '$LINKS$' => '',
            '$SETTINGS_GRID$' => $settingsGrid
        );

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
                    $actionLinks['edit'] = LinkBuilder::createAdvLink(array('page' => 'UserModule:RibbonSettings:showEditForm', 'id' => $ribbon->getId()), 'Edit');
                }

                if($app->ribbonAuthorizator->checkRibbonDeletable($app->user->getId(), $ribbon) &&
                   $app->actionAuthorizator->checkActionRight(UserActionRights::DELETE_RIBBONS)) {
                    $actionLinks['delete'] = LinkBuilder::createAdvLink(array('page' => 'UserModule:RibbonSettings:deleteRibbon', 'id' => $ribbon->getId()), 'Delete');
                }

                if($app->actionAuthorizator->checkActionRight(UserActionRights::EDIT_RIBBON_RIGHTS)) {
                    $actionLinks['edit_rights'] = LinkBuilder::createAdvLink(array('page' => 'UserModule:RibbonSettings:showEditRightsForm', 'id' => $ribbon->getId()), 'Edit rights');
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
}

?>