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
                '&nbsp;',
                '<a class="general-link" href="?page=' . $app::URL_HOME_PAGE . '">Home</a>'
            )
        );

        $panelAuthorizator = self::pa();

        if($panelAuthorizator->checkPanelRight('documents')) {
            $data['$LINKS$'][] = '<a class="general-link" href="?page="' . $app::URL_DOCUMENTS_PAGE . '">Documents</a>';
        }

        if($panelAuthorizator->checkPanelRight('settings')) {
            $data['$LINKS$'][] = '<a class="general-link" href="?page=' . $app::URL_SETTINGS_PAGE . '">Settings</a>';
        }

        if(!is_null($app->user)) {
            $data['$USER_PROFILE_LINK$'] = '<a class="general-link" href="?page=UserModule:UserProfile:showProfile&id=' . $app->user->getId() . '">' . $app->user->getFullname() . '</a>';
            $data['$USER_LOGOUT_LINK$'] = '<a class="general-link" href="?page=UserModule:UserLogout:logoutUser">Logout</a>';
        } else {
            $data['$USER_PROFILE_LINK$'] = '';
            $data['$USER_LOGOUT_LINK$'] = '';
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