<?php

namespace DMS\Modules\UserModule;

use DMS\Core\FileManager;
use DMS\Helpers\FormDataHelper;
use DMS\Modules\APresenter;
use DMS\UI\LinkBuilder;

class ImageBrowserPresenter extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('ImageBrowser', 'Image browser');

        $this->getActionNamesFromClass($this);
    }

    protected function showAll() {
        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/settings/settings-grid.html');

        $data = [
            '$PAGE_TITLE$' => 'All images',
            '$SETTINGS_GRID$' => $this->internalCreateImagesList(),
            '$LINKS$' => []
        ];

        $data['$LINKS$'][] = LinkBuilder::createLink('Settings:showSystem', '&larr;');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showSingle() {
        global $app;

        $app->flashMessageIfNotIsset(['name']);

        $name = $this->get('name');

        $template = $this->templateManager->loadTemplate(__DIR__ . '/templates/settings/settings-grid.html');

        $data = [
            '$PAGE_TITLE$' => 'Image <i>' . $name . '</i>',
            '$LINKS$' => [],
            '$SETTINGS_GRID$' => $this->internalCreateSingleImage($name)
        ];

        $data['$LINKS$'][] = LinkBuilder::createLink('showAll', '&larr;');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateSingleImage(string $name) {
        $code = '<div id="center"><img src="' . $name . '" width="256px"></div>';

        return $code;
    }

    private function internalCreateImagesList() {
        $code = '';

        $fm = FileManager::getTemporaryObject();
        $images = [];
        $fm->readFilesInFolder('img/', $images);

        foreach($images as $image) {
            $code .= LinkBuilder::createImgAdvLink(['page' => 'showSingle', 'name' => FormDataHelper::escape($image)], '', $image, 'general-link', 64);
        }

        return $code;
    }
}

?>