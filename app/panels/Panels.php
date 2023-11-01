<?php

namespace DMS\Panels;

use DMS\Constants\PanelRights;
use DMS\Core\TemplateManager;
use DMS\UI\LinkBuilder;

class Panels {
    public static function createProcessesPanel() {
        global $app;

        $templateManager = self::tm();

        $template = $templateManager->loadTemplate('app/panels/templates/general-subpanel.html');

        $data = array(
            '$LINKS$' => array(
                '&nbsp;',
                LinkBuilder::createLink('UserModule:Processes:showMenu', 'Menu'),
                LinkBuilder::createLink('UserModule:Processes:showAll', 'All processes')
            )
        );
    
        $templateManager->fill($data, $template);

        return $template;
    }

    public static function createSettingsPanel() {
        global $app;

        $templateManager = self::tm();

        $template = $templateManager->loadTemplate('app/panels/templates/general-subpanel.html');

        $data = array(
            '$LINKS$' => array(
                '&nbsp;',
                LinkBuilder::createLink('UserModule:Settings:showDashboard', 'Dashboard')
            )
        );

        $panelAuthorizator = self::pa();

        if($panelAuthorizator->checkPanelRight(PanelRights::FOLDERS)) {
            $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:Settings:showFolders', 'Document folders');
        }

        if($panelAuthorizator->checkPanelRight(PanelRights::SETTINGS_USERS)) {
            $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:Settings:showUsers', 'Users');
        }

        if($panelAuthorizator->checkPanelRight(PanelRights::SETTINGS_GROUPS)) {
            $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:Settings:showGroups', 'Groups');
        }

        if($panelAuthorizator->checkPanelRight(PanelRights::SETTINGS_METADATA)) {
            $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:Settings:showMetadata', 'Metadata');
        }

        if($panelAuthorizator->checkPanelRight(PanelRights::SETTINGS_SYSTEM)) {
            $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:Settings:showSystem', 'System');
        }

        if($panelAuthorizator->checkPanelRight(PanelRights::SETTINGS_SERVICES)) {
            $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:Settings:showServices', 'Services');
        }

        $templateManager->fill($data, $template);

        return $template;
    }

    public static function createTopPanel() {
        global $app;

        $templateManager = self::tm();

        $template = $templateManager->loadTemplate('app/panels/templates/toppanel.html');

        $data = array(
            '$LINKS$' => array(
                '&nbsp;',
                //LinkBuilder::createLink($app::URL_HOME_PAGE, 'Home')
                LinkBuilder::createImgLink($app::URL_HOME_PAGE, '', 'img/home-icon.png')
            )
        );

        $panelAuthorizator = self::pa();

        if($panelAuthorizator->checkPanelRight('documents')) {
            //$data['$LINKS$'][] = LinkBuilder::createLink($app::URL_DOCUMENTS_PAGE, 'Documents');
            $data['$LINKS$'][] = LinkBuilder::createImgLink($app::URL_DOCUMENTS_PAGE, '', 'img/documents-icon.png');
        }

        if($panelAuthorizator->checkPanelRight('processes')) {
            //$data['$LINKS$'][] = LinkBuilder::createLink($app::URL_PROCESSES_PAGE, 'Processes');
            $data['$LINKS$'][] = LinkBuilder::createImgLink($app::URL_PROCESSES_PAGE, '', 'img/processes-icon.png');
        }

        if($panelAuthorizator->checkPanelRight('settings')) {
            //$data['$LINKS$'][] = LinkBuilder::createLink($app::URL_SETTINGS_PAGE, 'Settings');
            $data['$LINKS$'][] = LinkBuilder::createImgLink($app::URL_SETTINGS_PAGE, '', 'img/settings-icon.png');
        }

        if(!is_null($app->user)) {
            //$data['$USER_PROFILE_LINK$'] = '<a class="general-link" href="?page=UserModule:UserProfile:showProfile&id=' . $app->user->getId() . '">' . $app->user->getFullname() . '</a>';
            //$data['$USER_PROFILE_LINK$'] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showProfile', 'id' => $app->user->getId()), $app->user->getFullname());
            $data['$USER_PROFILE_LINK$'] = LinkBuilder::createImgAdvLink(array('page' => 'UserModule:Users:showProfile', 'id' => $app->user->getId()), $app->user->getFullname(), 'img/user-icon.png');
            //$data['$USER_LOGOUT_LINK$'] = '<a class="general-link" href="?page=UserModule:UserLogout:logoutUser">Logout</a>';
            //$data['$USER_LOGOUT_LINK$'] = LinkBuilder::createLink('UserModule:UserLogout:logoutUser', 'Logout');
            $data['$USER_LOGOUT_LINK$'] = LinkBuilder::createImgLink('UserModule:UserLogout:logoutUser', '', 'img/logout-icon.png');
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