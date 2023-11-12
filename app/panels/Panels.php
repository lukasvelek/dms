<?php

namespace DMS\Panels;

use DMS\Constants\PanelRights;
use DMS\Core\TemplateManager;
use DMS\UI\LinkBuilder;

class Panels {
    private CONST TOPPANEL_USE_TEXT = FALSE;
    private const SETTINGSPANEL_USE_TEXT = FALSE;

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
                '&nbsp;'
            )
        );

        if(self::SETTINGSPANEL_USE_TEXT) {
            $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:Settings:showDashboard', 'Dashboard');
        } else {
            $data['$LINKS$'][] = LinkBuilder::createImgLink('UserModule:Settings:showDashboard', 'Dashboard', 'img/dashboard.svg');
        }

        $panelAuthorizator = self::pa();

        if($panelAuthorizator->checkPanelRight(PanelRights::FOLDERS)) {
            if(self::SETTINGSPANEL_USE_TEXT) {
                $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:Settings:showFolders', 'Document folders');
            } else {
                $data['$LINKS$'][] = LinkBuilder::createImgLink('UserModule:Settings:showFolders', 'Document folders', 'img/folder.svg');
            }
        }

        if($panelAuthorizator->checkPanelRight(PanelRights::SETTINGS_USERS)) {
            if(self::SETTINGSPANEL_USE_TEXT) {
                $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:Settings:showUsers', 'Users');
            } else {
                $data['$LINKS$'][] = LinkBuilder::createImgLink('UserModule:Settings:showUsers', 'Users', 'img/users.svg');
            }
        }

        if($panelAuthorizator->checkPanelRight(PanelRights::SETTINGS_GROUPS)) {
            if(self::SETTINGSPANEL_USE_TEXT) {
                $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:Settings:showGroups', 'Groups');
            } else {
                $data['$LINKS$'][] = LinkBuilder::createImgLink('UserModule:Settings:showGroups', 'Groups', 'img/groups.svg');
            }
        }

        if($panelAuthorizator->checkPanelRight(PanelRights::SETTINGS_METADATA)) {
            if(self::SETTINGSPANEL_USE_TEXT) {
                $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:Settings:showMetadata', 'Metadata');
            } else {
                $data['$LINKS$'][] = LinkBuilder::createImgLink('UserModule:Settings:showMetadata', 'Metadata', 'img/metadata.svg');
            }
        }

        if($panelAuthorizator->checkPanelRight(PanelRights::SETTINGS_SYSTEM)) {
            if(self::SETTINGSPANEL_USE_TEXT) {
                $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:Settings:showSystem', 'System');
            } else {
                $data['$LINKS$'][] = LinkBuilder::createImgLink('UserModule:Settings:showSystem', 'System', 'img/system.svg');
            }
        }

        if($panelAuthorizator->checkPanelRight(PanelRights::SETTINGS_SERVICES)) {
            if(self::SETTINGSPANEL_USE_TEXT) {
                $data['$LINKS$'][] = LinkBuilder::createLink('UserModule:Settings:showServices', 'Services');
            } else {
                $data['$LINKS$'][] = LinkBuilder::createImgLink('UserModule:Settings:showServices', 'Services', 'img/services.svg');
            }
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
            $data['$LINKS$'][] = LinkBuilder::createImgLink($app::URL_HOME_PAGE, 'Home', 'img/home.svg');
        }

        $panelAuthorizator = self::pa();

        if($panelAuthorizator->checkPanelRight('documents')) {
            if(self::TOPPANEL_USE_TEXT) {
                $data['$LINKS$'][] = LinkBuilder::createLink($app::URL_DOCUMENTS_PAGE, 'Documents');
            } else {
                $data['$LINKS$'][] = LinkBuilder::createImgLink($app::URL_DOCUMENTS_PAGE, 'Documents', 'img/documents.svg');
            }
        }

        if($panelAuthorizator->checkPanelRight('processes')) {
            if(self::TOPPANEL_USE_TEXT) {
                $data['$LINKS$'][] = LinkBuilder::createLink($app::URL_PROCESSES_PAGE, 'Processes');
            } else {
                $data['$LINKS$'][] = LinkBuilder::createImgLink($app::URL_PROCESSES_PAGE, 'Processes', 'img/processes.svg');
            }
        }

        if($panelAuthorizator->checkPanelRight('settings')) {
            if(self::TOPPANEL_USE_TEXT) {
                $data['$LINKS$'][] = LinkBuilder::createLink($app::URL_SETTINGS_PAGE, 'Settings');
            } else {
                $data['$LINKS$'][] = LinkBuilder::createImgLink($app::URL_SETTINGS_PAGE, 'Settings', 'img/settings.svg');
            }
        }

        if(!is_null($app->user)) {
            if(self::TOPPANEL_USE_TEXT) {
                $data['$USER_PROFILE_LINK$'] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showProfile', 'id' => $app->user->getId()), $app->user->getFullname());
                $data['$USER_LOGOUT_LINK$'] = LinkBuilder::createLink('UserModule:UserLogout:logoutUser', 'Logout');
            } else {
                $data['$USER_PROFILE_LINK$'] = LinkBuilder::createImgAdvLink(array('page' => 'UserModule:Users:showProfile', 'id' => $app->user->getId()), $app->user->getFullname(), 'img/user.svg');
                $data['$USER_LOGOUT_LINK$'] = LinkBuilder::createImgLink('UserModule:UserLogout:logoutUser', 'Logout', 'img/logout.svg');
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