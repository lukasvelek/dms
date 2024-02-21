<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\UserActionRights;
use DMS\Core\Logger\LogCategoryEnum;
use DMS\Entities\FileStorageLocation;
use DMS\Helpers\GridDataHelper;
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

        $app->fileManager->createDirectory($path);

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

    protected function setLocationAsDefault() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);

        $id = $this->get('id');

        $app->fileStorageModel->unsetAllLocationsAsDefault();
        $app->fileStorageModel->setLocationAsDefault($id);

        $app->flashMessage('Changed default file storage location');
        $app->redirect('UserModule:FileStorageSettings:showLocations');
    }

    protected function showRemoveLocationForm() {
        global $app;

        $app->flashMessageIfNotIsset(['id']);

        $id = $this->get('id');
        $location = $app->fileStorageModel->getLocationById($id);

        $template = $this->loadTemplate(__DIR__ . '/templates/settings/settings-new-entity-form.html');

        $data = [
            '$PAGE_TITLE$' => 'Remove location <i>' . $location->getName() . '</i>',
            '$LINKS$' => [],
            '$FORM$' => $this->internalCreateRemoveLocationForm($location)
        ];

        $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:FileStorageSettings:showLocations', '&larr;');
        
        $this->fill($data, $template);

        return $template;
    }

    protected function processRemoveLocationForm() {
        global $app;

        $log = true;

        $app->flashMessageIfNotIsset(['id', 'new_location']);

        $id = $this->get('id');
        $location = $app->fileStorageModel->getLocationById($id);
        $idNewLocation = $this->post('new_location');
        $newLocation = $app->fileStorageModel->getLocationById($idNewLocation);

        // move files
        if($log) {
            $app->logger->info('Moving files from \'' . $location->getPath() . '\' to \'' . $newLocation->getPath() . '\'', __METHOD__);
        }
        $files = $app->fsManager->getStoredFilesInDirectory($location->getPath());

        foreach($files as $file) {
            $old = $file->getPath();
            $new = str_replace($location->getPath(), $newLocation->getPath(), $file->getPath());

            $result = $app->fileManager->moveFileToDirectory($old, $new);

            if($log) {
                if($result === TRUE) {
                    $app->logger->info('Successfully moved file from \'' . $old . '\' to \'' . $new . '\'', __METHOD__);
                } else {
                    $app->logger->error('Could not move file from \'' . $old . '\' to \'' . $new . '\'', __METHOD__);
                }
            }
        }

        $documents = $app->documentModel->getDocumentsForDirectory($location->getPath());

        if($log) {
            $app->logger->info('Updating documents (total: ' . count($documents) . ')', __METHOD__);
        }
        foreach($documents as $document) {
            $newLoc = str_replace($location->getPath(), $newLocation->getPath(), $document->getFile());

            $app->documentModel->updateDocument($document->getId(), ['file' => $newLoc]);
        }

        // delete location from db
        if($log) {
            $app->logger->info('Removing location from the database', __METHOD__);
        }
        $app->fileStorageModel->removeLocation($id);

        // delete disk directory
        $folders = [];
        $app->fileManager->readFoldersInFolder($location->getPath(), $folders, false);

        if($log) {
            $app->logger->info('Deleting directories in \'' . $location->getPath() . '\'', __METHOD__);
        }
        foreach($folders as $folder) {
            $app->fileManager->deleteDirectory($folder);
        }

        $app->flashMessage('Sucessfully removed file storage location', 'success');
        $app->redirect('UserModule:FileStorageSettings:showLocations');
    }

    protected function moveLocationUp() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'order']);

        $id = $this->get('id');
        $order = $this->get('order');

        $newOrder = $order - 1;

        $idNewOrder = $app->fileStorageModel->getLocationByOrder($newOrder)->getId();

        $app->fileStorageModel->switchLocationOrder($id, $newOrder, $idNewOrder, $order);

        $app->redirect('UserModule:FileStorageSettings:showLocations');
    }

    protected function moveLocationDown() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'order']);

        $id = $this->get('id');
        $order = $this->get('order');

        $newOrder = $order + 1;

        $idNewOrder = $app->fileStorageModel->getLocationByOrder($newOrder)->getId();

        $app->fileStorageModel->switchLocationOrder($id, $newOrder, $idNewOrder, $order);

        $app->redirect('UserModule:FileStorageSettings:showLocations');
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

        $fsManager = $app->fsManager;

        $locations = $app->fileStorageModel->getAllFileStorageLocations(true);
        $locationCount = count($locations);

        $gb = new GridBuilder();

        $gb->addColumns(['order' => 'Order', 'name' => 'Name', 'path' => 'Path', 'isDefault' => 'Default', 'isActive' => 'Active', 'freeSpace' => 'Free space']);
        $gb->addDataSource($locations);
        $gb->addOnColumnRender('isDefault', function(FileStorageLocation $loc) {
            return GridDataHelper::renderBooleanValueWithColors($loc->isDefault(), 'Yes', 'No');
        });
        $gb->addOnColumnRender('isActive', function(FileStorageLocation $loc) {
            return GridDataHelper::renderBooleanValueWithColors($loc->isActive(), 'Yes', 'No');
        });
        $gb->addOnColumnRender('freeSpace', function(FileStorageLocation $loc) use ($fsManager) {
            return $fsManager->getFreeSpaceLeft($loc->getPath());
        });
        $gb->addAction(function(FileStorageLocation $loc) use ($locationCount) {
            if($locationCount > 1) {
                if($loc->getOrder() < $locationCount) {
                    return LinkBuilder::createAdvLink(['page' => 'UserModule:FileStorageSettings:moveLocationDown', 'id' => $loc->getId(), 'order' => $loc->getOrder()], '&darr;');
                }
            } else {
                return '-';
            }
        });
        $gb->addAction(function(FileStorageLocation $loc) use ($locationCount) {
            if($locationCount > 1) {
                if($loc->getOrder() > 1) {
                    return LinkBuilder::createAdvLink(['page' => 'UserModule:FileStorageSettings:moveLocationUp', 'id' => $loc->getId(), 'order' => $loc->getOrder()], '&uarr;');
                }
            } else {
                return '-';
            }
        });
        $gb->addAction(function(FileStorageLocation $loc) use ($locationCount) {
            if($locationCount > 1 && $loc->isSystem() === FALSE) {
                return LinkBuilder::createAdvLink(['page' => 'UserModule:FileStorageSettings:showRemoveLocationForm', 'id' => $loc->getId()], 'Remove');
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

    private function internalCreateRemoveLocationForm(FileStorageLocation $location) {
        global $app;

        $dbFileStorageLocations = $app->fileStorageModel->getAllActiveFileStorageLocations(true);

        $fileStorageLocations = [];
        foreach($dbFileStorageLocations as $loc) {
            if($loc == $location) continue;

            $fsl = [
                'value' => $loc->getId(),
                'text' => $loc->getName()
            ];

            if($loc->isDefault()) {
                $fsl['selected'] = 'selected';
            }

            $fileStorageLocations[] = $fsl;
        }

        $fb = new FormBuilder();

        $fb ->setAction('?page=UserModule:FileStorageSettings:processRemoveLocationForm&id=' . $location->getId())->setMethod('POST')

            ->addElement($fb->createLabel()->setText('New location for currently saved files')->setFor('new_location'))
            ->addElement($fb->createSelect()->setName('new_location')->addOptionsBasedOnArray($fileStorageLocations))

            ->addElement($fb->createSubmit('Remove'))
        ;

        return $fb->build();
    }
}

?>