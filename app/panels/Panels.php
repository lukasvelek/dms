<?php

namespace DMS\Panels;

use DMS\Constants\CacheCategories;
use DMS\Constants\UserActionRights;
use DMS\Core\AppConfiguration;
use DMS\Core\CacheManager;
use DMS\Core\TemplateManager;
use DMS\UI\LinkBuilder;

class Panels {
    public static function createTopPanel() {
        global $app;

        $templateManager = self::tm();

        $template = $templateManager->loadTemplate('app/panels/templates/toppanel.html');

        $data = array(
            '$LINKS$' => array(
                '&nbsp;'
            )
        );

        if(is_null($app->user)) {
            return;
        }

        $visibleRibbons = $app->ribbonComponent->getToppanelRibbonsVisibleToUser($app->user->getId());

        foreach($visibleRibbons as $ribbon) {
            if($ribbon->hasImage()) {
                $data['$LINKS$'][] = LinkBuilder::createImgLink($ribbon->getPageUrl() . '&id_ribbon=' . $ribbon->getId(), $ribbon->getName(), $ribbon->getImage(), 'general-link', true);
            } else {
                $data['$LINKS$'][] = LinkBuilder::createLink($ribbon->getPageUrl() . '&id_ribbon=' . $ribbon->getId(), $ribbon->getName(), 'general-link', true);
            }
        }

        if(!is_null($app->user)) {
            $data['$USER_NOTIFICATIONS_LINK$'] = '<img src="img/notifications.svg" width="32" height="32" loading="lazy"><span class="general-link" style="cursor: pointer" id="notificationsController" onclick="openNotifications()">Notifications (0)</span>';
            $data['$USER_PROFILE_LINK$'] = LinkBuilder::createImgAdvLink(array('page' => 'UserModule:Users:showProfile', 'id' => $app->user->getId()), $app->user->getFullname(), 'img/user.svg');
            $data['$USER_LOGOUT_LINK$'] = LinkBuilder::createImgLink('UserModule:UserLogout:logoutUser', 'Logout', 'img/logout.svg');
            
            if(AppConfiguration::getEnableRelogin() && $app->actionAuthorizator->checkActionRight(UserActionRights::ALLOW_RELOGIN)) {
                $data['$USER_RELOGIN_LINK$'] = LinkBuilder::createAdvLink(array('page' => 'UserModule:UserRelogin:showConnectedUsers'), 'Relogin');
            } else {
                $data['$USER_RELOGIN_LINK$'] = '';
            }
        } else {
            $data['$LINKS$'] = '';
            $data['$USER_PROFILE_LINK$'] = '';
            $data['$USER_LOGOUT_LINK$'] = '';
            $data['$USER_NOTIFICATIONS_LINK$'] = '';
            $data['$USER_RELOGIN_LINK$'] = '';
        }

        $templateManager->fill($data, $template);

        return $template;
    }

    public static function createSubpanel() {
        global $app;

        if($app->user === NULL) {
            return null;
        }

        $templateManager = self::tm();

        $template = $templateManager->loadTemplate('app/panels/templates/general-subpanel.html');

        $data = array(
            '$LINKS$' => array(
                '&nbsp;'
            )
        );

        $currentIdRibbon = $app->currentIdRibbon;
        $currentRibbon = null;

        if(is_null($currentIdRibbon)) {
            return null;
        }

        $currentRibbon = null;

        $cm = CacheManager::getTemporaryObject(CacheCategories::RIBBONS);
        $valFromCache = $cm->loadRibbonById($currentIdRibbon);

        if(!is_null($valFromCache)) {
            $currentRibbon = $valFromCache;
        } else {
            $currentRibbon = $app->ribbonModel->getRibbonById($currentIdRibbon);

            $cm->saveRibbon($currentRibbon);
        }
        
        $idRibbon = $currentRibbon->getId();
        
        if($currentRibbon->hasParent()) {
            $idRibbon = $currentRibbon->getIdParentRibbon();
        }

        $subRibbons = $app->ribbonComponent->getChildrenRibbonsVisibleToUser($app->user->getId(), $idRibbon);

        if(empty($subRibbons)) {
            return null;
        }

        foreach($subRibbons as $ribbon) {
            if($ribbon->getName() == 'SPLITTER') {
                $data['$LINKS$'][] = '&nbsp;|';
            } else {
                if($ribbon->isJS()) {
                    $data['$LINKS$'][] = '<a class="general-link" href="#" id="dropdown-ribbon-' . $ribbon->getId() . '" onclick="' . $ribbon->getJSMethodName() . '">' . $ribbon->getName() . '</a>';
                } else {
                    if($ribbon->hasImage()) {
                        $data['$LINKS$'][] = LinkBuilder::createImgLink($ribbon->getPageUrl() . '&id_ribbon=' . $ribbon->getId(), $ribbon->getName(), $ribbon->getImage(), 'general-link', true);
                    } else {
                        $data['$LINKS$'][] = LinkBuilder::createLink($ribbon->getPageUrl() . '&id_ribbon=' . $ribbon->getId(), $ribbon->getName(), 'general-link', true);
                    }
                }
            }
        }

        $templateManager->fill($data, $template);

        return $template;
    }

    private static function tm() {
        return TemplateManager::getTemporaryObject();
    }
}

?>