<?php

namespace DMS\Panels;

use DMS\Core\TemplateManager;
use DMS\UI\LinkBuilder;

class Panels {
    public static function createTopPanel() {
        global $app;

        $templateManager = self::tm();

        $template = $templateManager->loadTemplate('app/panels/templates/toppanel.html');

        $data = array(
            '$LINKS$' => array(
                '&nbsp;',
                LinkBuilder::createLink($app::URL_HOME_PAGE, 'Home')
            )
        );

        $panelAuthorizator = self::pa();

        if($panelAuthorizator->checkPanelRight('documents')) {
            $data['$LINKS$'][] = LinkBuilder::createLink($app::URL_DOCUMENTS_PAGE, 'Documents');
        }

        if($panelAuthorizator->checkPanelRight('processes')) {
            $data['$LINKS$'][] = LinkBuilder::createLink($app::URL_PROCESSES_PAGE, 'Processes');
        }

        if($panelAuthorizator->checkPanelRight('settings')) {
            $data['$LINKS$'][] = LinkBuilder::createLink($app::URL_SETTINGS_PAGE, 'Settings');
        }

        if(!is_null($app->user)) {
            //$data['$USER_PROFILE_LINK$'] = '<a class="general-link" href="?page=UserModule:UserProfile:showProfile&id=' . $app->user->getId() . '">' . $app->user->getFullname() . '</a>';
            $data['$USER_PROFILE_LINK$'] = LinkBuilder::createAdvLink(array('page' => 'UserModule:UserProfile:showProfile', 'id' => $app->user->getId()), $app->user->getFullname());
            //$data['$USER_LOGOUT_LINK$'] = '<a class="general-link" href="?page=UserModule:UserLogout:logoutUser">Logout</a>';
            $data['$USER_LOGOUT_LINK$'] = LinkBuilder::createLink('UserModule:UserLogout:logoutUser', 'Logout');
        } else {
            $data['$LINKS$'] = '';
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