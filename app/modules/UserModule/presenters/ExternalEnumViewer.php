<?php

namespace DMS\Modules\UserModule;

use DMS\Helpers\ArrayStringHelper;
use DMS\Modules\APresenter;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

class ExternalEnumViewer extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('ExternalEnumViewer', 'External Enum Viewer');

        $this->getActionNamesFromClass($this);
    }

    protected function showList() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/external-enum-viewer/external-enum-viewer-grid.html');

        $data = array(
            '$PAGE_TITLE$' => 'External enum list',
            '$VIEWER_GRID$' => $this->internalCreateList(),
            '$LINKS$' => LinkBuilder::createLink('Settings:showMetadata', '<-')
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showValues() {
        global $app;
        
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/external-enum-viewer/external-enum-viewer-grid.html');

        $app->flashMessageIfNotIsset(['name']);

        $name = htmlspecialchars($_GET['name']);

        $data = array(
            '$PAGE_TITLE$' => 'External enum list',
            '$VIEWER_GRID$' => $this->internalCreateValues($name)
        );

        $data['$LINKS$'][] = LinkBuilder::createLink('showList', '<-');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateValues(string $name) {
        global $app;

        $externalEnumComponent = $app->externalEnumComponent;

        $dataSourceCallback = function() use ($externalEnumComponent, $name) {
            $values = $externalEnumComponent->getEnumByName($name)->getValues();

            $arr = [];
            foreach($values as $k => $v) {
                $arr[] = new class($k, $v) {
                    private $key;
                    private $value;

                    function __construct($k, $v) {
                        $this->key = $k;
                        $this->value = $v;
                    }

                    function getKey() {
                        return $this->key;
                    }

                    function getValue() {
                        return $this->value;
                    }
                };
            }

            return $arr;
        };

        $gb = new GridBuilder();

        $gb->addColumns(['key' => 'Key', 'value' => 'Value']);
        $gb->addDataSourceCallback($dataSourceCallback);

        return $gb->build();
    }

    private function internalCreateList() {
        global $app;

        $externalEnumComponent = $app->externalEnumComponent;

        $data = function() use ($externalEnumComponent) {
            $enums = $externalEnumComponent->getEnumsList();

            return $enums;
        };

        $gb = new GridBuilder();

        $gb->addColumns(['name' => 'Name']);
        $gb->addDataSourceCallback($data);
        $gb->addAction(function($enum) {
            return LinkBuilder::createAdvLink(array('page' => 'showValues', 'name' => $enum->getName()), 'Values');
        });

        return $gb->build();
    }
}

?>