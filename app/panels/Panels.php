<?php

namespace DMS\Panels;

use DMS\Core\TemplateManager;

class Panels {
    public static function createTopPanel() {
        global $app;

        $templateManager = self::tm();

        $template = $templateManager->loadTemplate('app/panels/templates/toppanel.html');

        $data = array(
            '$LINKS$' => array(
                '&nbsp;&nbsp;',
                '<a class="general-link" href="?page=' . $app::URL_HOME_PAGE . '">Home</a>'
            )
        );

        $panelAuthorizator = self::pa();

        if($panelAuthorizator->checkPanelRight('settings')) {
            $data['$LINKS$'][] = '<a class="general-link" href="?page=' . $app::URL_SETTINGS_PAGE . '">Settings</a>';
        }

        $templateManager->fill($data, $template);

        return $template;
    }

    private static function tm() {
        return TemplateManager::getTemporaryObject();
    }

    private static function pa() {
        global $app;

        return $app->panelAuthorizator;
    }
}

?>