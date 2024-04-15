<?php

namespace DMS\Panels;

use DMS\Authorizators\RibbonAuthorizator;
use DMS\Constants\CacheCategories;
use DMS\Constants\UserActionRights;
use DMS\Core\AppConfiguration;
use DMS\Core\CacheManager;
use DMS\Core\TemplateManager;
use DMS\Entities\User;
use DMS\Models\RibbonModel;
use DMS\UI\LinkBuilder;

class Panels {
    public static function generateRibbons(RibbonAuthorizator $ra, RibbonModel $rm, User $user) {
        $rcm = CacheManager::getTemporaryObject(CacheCategories::RIBBONS);
        
        $ribbons = $rm->getAllRibbons();

        foreach($ribbons as $ribbon) {
            if($ra->checkRibbonVisible($user->getId(), $ribbon)) {
                $rcm->saveRibbon($ribbon);
            }
        }
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

        if(is_null($app->user)) {
            return;
        }

        $rcm = CacheManager::getTemporaryObject(CacheCategories::RIBBONS);

        $cacheRibbons = $rcm->loadRibbons();

        $visibleRibbons = [];
        foreach($cacheRibbons['null'] as $cr) {
            $visibleRibbons[] = $cr;
        }

        foreach($visibleRibbons as $ribbon) {
            if($ribbon->hasImage()) {
                $data['$LINKS$'][] = LinkBuilder::createImgLink($ribbon->getPageUrl() . '&id_ribbon=' . $ribbon->getId(), $ribbon->getName(), $ribbon->getImage(), 'toppanel-link', true);
            } else {
                $data['$LINKS$'][] = LinkBuilder::createLink($ribbon->getPageUrl() . '&id_ribbon=' . $ribbon->getId(), $ribbon->getName(), 'toppanel-link', true);
            }
        }

        if(!is_null($app->user)) {
            $data['$USER_NOTIFICATIONS_LINK$'] = '<span class="toppanel-link" style="cursor: pointer" id="notificationsController" onclick="openNotifications()">Notifications (0)</span>';
            $data['$USER_PROFILE_LINK$'] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showProfile', 'id_ribbon' => '26'), $app->user->getFullname(), 'toppanel-link');
            $data['$USER_LOGOUT_LINK$'] = LinkBuilder::createLink('UserModule:UserLogout:logoutUser', 'Logout', 'toppanel-link');

            if(AppConfiguration::getEnableRelogin() && ($app->actionAuthorizator->checkActionRight(UserActionRights::ALLOW_RELOGIN) || $app->userModel->getUserConnectionsByIdUser($app->user->getId())->num_rows > 0)) {
                $data['$USER_RELOGIN_LINK$'] = LinkBuilder::createAdvLink(array('page' => 'UserModule:UserRelogin:showConnectedUsers'), 'Relogin', 'toppanel-link');
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

        $idFolder = null;
        if(isset($_GET['id_folder'])) {
            $idFolder = htmlspecialchars($_GET['id_folder']);
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
        $currentRibbon = $cm->loadRibbonById($currentIdRibbon);

        if($currentRibbon === NULL) {
            $currentRibbon = $app->ribbonModel->getRibbonById($currentIdRibbon);
        }
        
        if($currentRibbon->hasParent()) {
            $currentIdRibbon = $currentRibbon->getIdParentRibbon();

            $currentRibbon = $app->ribbonModel->getRibbonById($currentIdRibbon);
            if($currentRibbon !== NULL && $currentRibbon->isJS()) {
                $currentIdRibbon = $currentRibbon->getIdParentRibbon();
            }
        }

        $subRibbons = $app->ribbonComponent->getChildrenRibbonsVisibleToUser($app->user->getId(), $currentIdRibbon);

        if(empty($subRibbons)) {
            return null;
        }

        foreach($subRibbons as $ribbon) {
            if($ribbon->getName() == 'SPLITTER') {
                $data['$LINKS$'][] = '&nbsp;|';
            } else {
                if($ribbon->isJS()) {
                    //$data['$LINKS$'][] = '<a class="general-link" href="#" id="dropdown-ribbon-' . $ribbon->getId() . '" onclick="' . $ribbon->getJSMethodName() . '">' . $ribbon->getName() . '</a>';
                    $data['$LINKS$'][] = '<span class="toppanel-link" id="dropdown-ribbon-' . $ribbon->getId() . '" style="cursor: pointer" onclick="' . $ribbon->getJSMethodName() . '">' . $ribbon->getName() . '</span>';
                } else {
                    if($ribbon->hasImage()) {
                        $data['$LINKS$'][] = LinkBuilder::createImgLink($ribbon->getPageUrl() . '&id_ribbon=' . $ribbon->getId() . ((!is_null($idFolder)) ? ('&id_folder=' . $idFolder) : ''), $ribbon->getName(), $ribbon->getImage(), 'toppanel-link', true);
                    } else {
                        $data['$LINKS$'][] = LinkBuilder::createLink($ribbon->getPageUrl() . '&id_ribbon=' . $ribbon->getId() . ((!is_null($idFolder)) ? ('&id_folder=' . $idFolder) : ''), $ribbon->getName(), 'toppanel-link', true);
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