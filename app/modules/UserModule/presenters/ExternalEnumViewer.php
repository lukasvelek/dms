<?php

namespace DMS\Modules\UserModule;

use DMS\Modules\APresenter;
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
            '$LINKS$' => ''
        );

        $this->templateManager->fill($data, $template);

        return $template;
    }

    protected function showValues() {
        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/external-enum-viewer/external-enum-viewer-grid.html');

        $name = htmlspecialchars($_GET['name']);

        $data = array(
            '$PAGE_TITLE$' => 'External enum list',
            '$VIEWER_GRID$' => $this->internalCreateValues($name)
        );

        $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:ExternalEnumViewer:showList', '<-');

        $this->templateManager->fill($data, $template);

        return $template;
    }

    private function internalCreateValues(string $name) {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $enums = $app->externalEnumComponent->getEnumsList();
        $values = $app->externalEnumComponent->getEnumByName($name)->getValues();

        $headers = array(
            'Key',
            'Value'
        );

        $headerRow = null;

        foreach($values as $k => $v) {
            if(is_null($headerRow)) {
                $row = $tb->createRow();

                foreach($headers as $header) {
                    $col = $tb->createCol()->setText($header)->setBold();

                    $row->addCol($col);
                }

                $tb->addRow($row);
                $headerRow = $row;
            }

            $row = $tb->createRow();

            $row->addCol($tb->createCol()->setText($k))
                ->addCol($tb->createCol()->setText($v))
            ;

            $tb->addRow($row);
        }

        return $tb->build();
    }

    private function internalCreateList() {
        global $app;

        $tb = TableBuilder::getTemporaryObject();

        $enums = $app->externalEnumComponent->getEnumsList();

        $headers = array(
            'Actions',
            'Name'
        );

        $headerRow = null;

        foreach($enums as $enum) {
            $actionLinks = array(
                LinkBuilder::createAdvLink(array('page' => 'UserModule:ExternalEnumViewer:showValues', 'name' => $enum->getName()), 'Values')
            );

            if(is_null($headerRow)) {
                $row = $tb->createRow();

                foreach($headers as $header) {
                    $col = $tb->createCol()->setText($header)->setBold();

                    if($header == 'Actions') {
                        $col->setColspan(count($actionLinks));
                    }

                    $row->addCol($col);
                }

                $tb->addRow($row);
                $headerRow = $row;
            }

            $row = $tb->createRow();

            foreach($actionLinks as $actionLink) {
                $row->addCol($tb->createCol()->setText($actionLink));
            }

            $row->addCol($tb->createCol()->setText($enum->getName()));

            $tb->addRow($row);
        }

        return $tb->build();
    }
}

?>