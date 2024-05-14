<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\FileStorageTypes;
use DMS\Constants\Metadata\DocumentMetadata;
use DMS\Constants\Metadata\FileStorageLocationMetadata;
use DMS\Constants\UserActionRights;
use DMS\Core\AppConfiguration;
use DMS\Entities\FileStorageLocation;
use DMS\Modules\APresenter;
use DMS\UI\FormBuilder\FormBuilder;
use DMS\UI\LinkBuilder;

class FileStorageSettingsPresenter extends APresenter {
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

        $app->flashMessageIfNotIsset(['name', 'path', 'absolute_path', 'type']);

        $name = $this->post('name');
        $path = $this->post('path');
        $absolutePath = $this->post('absolute_path');
        $type = $this->post('type');

        if(isset($_POST['active'])) {
            $active = '1';
        } else {
            $active = '0';
        }

        $data = [
            FileStorageLocationMetadata::NAME => $name,
            FileStorageLocationMetadata::PATH => $path,
            FileStorageLocationMetadata::IS_ACTIVE => $active,
            FileStorageLocationMetadata::TYPE => $type,
            FileStorageLocationMetadata::ABSOLUTE_PATH => $absolutePath
        ];

        $lastOrder = $app->fileStorageModel->getLastLocationOrder();

        $data[FileStorageLocationMetadata::ORDER] = $lastOrder + 1;

        $app->fileStorageModel->insertNewLocation($data);

        $app->fileManager->createDirectory($path);

        $app->flashMessage('Created new location', 'success');
        $app->redirect('showLocations');
    }

    protected function showLocations() {
        global $app;

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/settings/settings-folders.html');

        $data = [
            '$PAGE_TITLE$' => 'File storage',
            '$LINKS$' => [],
            '$FOLDERS_GRID$' => $this->internalCreateLocationsGrid()
        ];

        $data['$LINKS$'][] = LinkBuilder::createAdvLink(['page' => 'Settings:showSystem'], '&larr;') . '&nbsp;&nbsp;';

        if($app->actionAuthorizator->checkActionRight(UserActionRights::EDIT_FILE_STORAGE_LOCATIONS)) {
            $data['$LINKS$'][] = LinkBuilder::createLink('showNewLocationForm', 'New location');
        }

        $data['$LINKS$'][] = '&nbsp;&nbsp;<span class="general-link" style="cursor: pointer" onclick="loadFileStorageLocations(false)">Refresh</span>';

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function setLocationAsDefault() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'type']);

        $id = $this->get('id');
        $type = $this->get('type');

        $app->fileStorageModel->unsetAllLocationsAsDefault($type);
        $app->fileStorageModel->setLocationAsDefault($id);

        $app->flashMessage('Changed default file storage location');
        $app->redirect('showLocations');
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

        $data['$LINKS$'][] = LinkBuilder::createLink('showLocations', '&larr;');
        
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

            $app->documentModel->updateDocument($document->getId(), [DocumentMetadata::FILE => $newLoc]);
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
        $app->redirect('showLocations');
    }

    protected function moveLocationUp() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'order']);

        $id = $this->get('id');
        $order = $this->get('order');

        $newOrder = $order - 1;

        $idNewOrder = $app->fileStorageModel->getLocationByOrder($newOrder)->getId();

        $app->fileStorageModel->switchLocationOrder($id, $newOrder, $idNewOrder, $order);

        $app->redirect('showLocations');
    }

    protected function moveLocationDown() {
        global $app;

        $app->flashMessageIfNotIsset(['id', 'order']);

        $id = $this->get('id');
        $order = $this->get('order');

        $newOrder = $order + 1;

        $idNewOrder = $app->fileStorageModel->getLocationByOrder($newOrder)->getId();

        $app->fileStorageModel->switchLocationOrder($id, $newOrder, $idNewOrder, $order);

        $app->redirect('showLocations');
    }

    private function internalCreateNewLocationForm() {
        $fb = new FormBuilder();

        $storageTypes = [];
        foreach(FileStorageTypes::$texts as $k => $v) {
            $storageTypes[] = [
                'value' => $k, 
                'text' => $v
            ];
        }

        $fb ->setAction('?page=UserModule:FileStorageSettings:processNewLocationForm')->setMethod('POST')
            ->addElement($fb->createLabel()->setText('Name')->setFor('name')->setRequired())
            ->addElement($fb->createInput()->setType('text')->setName('name')->require()->setPlaceHolder('File storage 1'))

            ->addElement($fb->createLabel()->setText('Path')->setFor('path')->setRequired())
            ->addElement($fb->createInput()->setType('text')->setName('path')->require()->setPlaceHolder('C:\\wwwroot\\' . (str_replace('/', '', AppConfiguration::getAbsoluteAppDir())) . '\\files\\'))

            ->addElement($fb->createLabel()->setText('Absolute path')->setFor('absolute_path')->setRequired())
            ->addElement($fb->createInput()->setType('text')->setName('absolute_path')->require()->setPlaceHolder(AppConfiguration::getAbsoluteAppDir() . 'files/'))

            ->addElement($fb->createLabel()->setText('Storage type')->setFor('type'))
            ->addElement($fb->createSelect()->setName('type')->addOptionsBasedOnArray($storageTypes))
            
            ->addElement($fb->createLabel()->setText('Active'))
            ->addElement($fb->createInput()->setType('checkbox')->setName('active')->setSpecial('checked'))

            ->addElement($fb->createSubmit('Create'))
            ;

        return $fb->build();
    }

    private function internalCreateLocationsGrid() {
        $code = '<script type="text/javascript">loadFileStorageLocations()</script>';
        $code .= '<div id="grid-loading"><img src="img/loading.gif" width="32" height="32"></div><table border="1"></table>';
        return $code;
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