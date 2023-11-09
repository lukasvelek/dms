<?php

namespace DMS\Panels;

use DMS\Constants\PanelRights;
use DMS\Core\TemplateManager;
use DMS\UI\LinkBuilder;

class Panels {
    private CONST TOPPANEL_USE_TEXT = FALSE;

    public static function createProcessesPanel() {
        $templateManager = self::tm();

        $template = $templateManager->loadTemplate('app/panels/templates/general-subpanel.html');

        $data = array(
            '$LINKS$' => array(
                '&nbsp;',
                LinkBuilder::createAdvLink(array('page' => 'UserModule:Processes:showAll', 'filter' => 'startedByMe'), 'Processes started by me'),
                LinkBuilder::createAdvLink(array('page' => 'UserModule:Processes:showAll', 'filter' => 'waitingForMe'), 'Processes waiting for me'),
                LinkBuilder::createAdvLink(array('page' => 'UserModule:Processes:showAll', 'filter' => 'finished'), 'Finished processes'),
                /*LinkBuilder::createLink('UserModule:Processes:showList', 'Processes started by me'),
                LinkBuilder::createLink()*/
            )
        );
    
        $templateManager->fill($data, $template);

        return $template;
    }

    public static function createSettingsPanel() {
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
                '&nbsp;'
            )
        );

        if(self::TOPPANEL_USE_TEXT) {
            $data['$LINKS$'][] = LinkBuilder::createLink($app::URL_HOME_PAGE, 'Home');
        } else {
            $data['$LINKS$'][] = LinkBuilder::createImgLink($app::URL_HOME_PAGE, '', 'img/home-icon.png');
        }

        $panelAuthorizator = self::pa();

        if($panelAuthorizator->checkPanelRight('documents')) {
            if(self::TOPPANEL_USE_TEXT) {
                $data['$LINKS$'][] = LinkBuilder::createLink($app::URL_DOCUMENTS_PAGE, 'Documents');
            } else {
                $data['$LINKS$'][] = LinkBuilder::createImgLink($app::URL_DOCUMENTS_PAGE, '', 'img/documents-icon.png');
            }
        }

        if($panelAuthorizator->checkPanelRight('processes')) {
            if(self::TOPPANEL_USE_TEXT) {
                $data['$LINKS$'][] = LinkBuilder::createLink($app::URL_PROCESSES_PAGE, 'Processes');
            } else {
                $data['$LINKS$'][] = LinkBuilder::createImgLink($app::URL_PROCESSES_PAGE, '', 'img/processes-icon.png');
            }
        }

        if($panelAuthorizator->checkPanelRight('settings')) {
            if(self::TOPPANEL_USE_TEXT) {
                $data['$LINKS$'][] = LinkBuilder::createLink($app::URL_SETTINGS_PAGE, 'Settings');
            } else {
                $data['$LINKS$'][] = LinkBuilder::createImgLink($app::URL_SETTINGS_PAGE, '', 'img/settings-icon.png');
            }
        }

        if(!is_null($app->user)) {
            if(self::TOPPANEL_USE_TEXT) {
                $data['$USER_PROFILE_LINK$'] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showProfile', 'id' => $app->user->getId()), $app->user->getFullname());
                $data['$USER_LOGOUT_LINK$'] = LinkBuilder::createLink('UserModule:UserLogout:logoutUser', 'Logout');
            } else {
                $data['$USER_PROFILE_LINK$'] = LinkBuilder::createImgAdvLink(array('page' => 'UserModule:Users:showProfile', 'id' => $app->user->getId()), $app->user->getFullname(), 'img/user-icon.png');
                $data['$USER_LOGOUT_LINK$'] = LinkBuilder::createImgLink('UserModule:UserLogout:logoutUser', '', 'img/logout-icon.png');
            }
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