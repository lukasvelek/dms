<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\UserActionRights;
use DMS\Entities\FileStorageLocation;
use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;

class FileStorageSettings extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('FileStorageSettings', 'File storage settings');

        $this->getActionNamesFromClass($this);
    }

    protected function showNewLocationForm() {
        $template = $this->loadTemplate(__DIR__ . '/templates/settings/settings-new-entity-form.html');

        $data = [
            '$PAGE_TITLE$' => 'New file storage location form',
            '$LINKS$' => [],
            '$FORM$' => $this->internalCreateNewLocationForm()
        ];

        $this->fill($data, $template);

        return $template;
    }

    protected function processNewLocationForm() {
        global $app;

        $app->flashMessageIfNotIsset(['name', 'path']);

        $name = $this->post('name');
        $path = $this->post('path');

        if(isset($_POST['active'])) {
            $active = '1';
        } else {
            $active = '0';
        }

        $data = [
            'name' => $name,
            'path' => $path,
            'is_active' => $active
        ];

        $lastOrder = $app->fileStorageModel->getLastLocationOrder();

        $data['order'] = $lastOrder + 1;

        $app->fileStorageModel->insertNewLocation($data);

        $app->flashMessage('Created new location', 'success');
        $app->redirect('UserModule:FileStorageSettings:showLocations');
    }

    protected function showLocations() {
        global $app;

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/settings/settings-folders.html');

        $data = [
            '$PAGE_TITLE$' => 'File storage',
            '$LINKS$' => [],
            '$FOLDERS_GRID$' => $this->internalCreateLocationsGrid()
        ];

        if($app->actionAuthorizator->checkActionRight(UserActionRights::EDIT_FILE_STORAGE_LOCATIONS)) {
            $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:FileStorageSettings:showNewLocationForm', 'New location');
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateNewLocationForm() {
        $fb = new FormBuilder();

        $fb ->setAction('?page=UserModule:FileStorageSettings:processNewLocationForm')->setMethod('POST')
            ->addElement($fb->createLabel()->setText('Name')->setFor('name'))
            ->addElement($fb->createInput()->setType('text')->setName('name')->require())

            ->addElement($fb->createLabel()->setText('Path')->setFor('path'))
            ->addElement($fb->createInput()->setType('text')->setName('path')->require())
            
            ->addElement($fb->createLabel()->setText('Active'))
            ->addElement($fb->createInput()->setType('checkbox')->setName('active')->setSpecial('checked'))

            ->addElement($fb->createSubmit('Create'))
            ;

        return $fb->build();
    }

    private function internalCreateLocationsGrid() {
        global $app;

        $locations = $app->fileStorageModel->getAllFileStorageLocations(true);
        $locationCount = count($locations);

        $gb = new GridBuilder();

        $gb->addColumns(['order' => 'Order', 'name' => 'Name', 'path' => 'Path', 'isDefault' => 'Default', 'isActive' => 'Active']);
        $gb->addDataSource($locations);
        $gb->addOnColumnRender('isDefault', function(FileStorageLocation $loc) {
            return $loc->isDefault() ? 'Yes' : 'No';
        });
        $gb->addOnColumnRender('isActive', function(FileStorageLocation $loc) {
            return $loc->isActive() ? 'Yes' : 'No';
        });
        $gb->addAction(function(FileStorageLocation $loc) use ($locationCount) {
            if($locationCount > 1) {
                if($loc->getOrder() > 1) {
                    return LinkBuilder::createAdvLink(['page' => 'UserModule:FileStorageSettings:moveLocationDown', 'id' => $loc->getId()], '&darr;');
                }
            } else {
                return '-';
            }
        });
        $gb->addAction(function(FileStorageLocation $loc) use ($locationCount) {
            if($locationCount > 1) {
                if($loc->getOrder() > 1) {
                    return LinkBuilder::createAdvLink(['page' => 'UserModule:FileStorageSettings:moveLocationUp', 'id' => $loc->getId()], '&uarr;');
                }
            } else {
                return '-';
            }
        });
        $gb->addAction(function(FileStorageLocation $loc) use ($locationCount) {
            if($locationCount > 1) {
                return LinkBuilder::createAdvLink(['page' => 'UserModule:FileStorageSettings:removeLocation', 'id' => $loc->getId()], 'Remove');
            } else {
                return '-';
            }
        });
        $gb->addAction(function(FileStorageLocation $loc) {
            if($loc->isDefault() === FALSE) {
                return LinkBuilder::createAdvLink(['page' => 'UserModule:FileStorageSettings:setLocationAsDefault', 'id' => $loc->getId()], 'Set as default');
            } else {
                return '-';
            }
        });

        return $gb->build();
    }
}

?>