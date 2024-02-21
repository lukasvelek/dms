<?php

use DMS\Constants\CacheCategories;
use DMS\Constants\UserActionRights;
use DMS\Constants\UserStatus;
use DMS\Core\AppConfiguration;
use DMS\Core\CacheManager;
use DMS\Entities\User;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

require_once('Ajax.php');

$ucm = new CacheManager(true, CacheCategories::USERS, '../../' . AppConfiguration::getLogDir(), '../../' . AppConfiguration::getCacheDir());

$action = null;

if(isset($_GET['action'])) {
    $action = htmlspecialchars($_GET['action']);
} else if(isset($_POST['action'])) {
    $action = htmlspecialchars($_POST['action']);
}

if($action === NULL) {
    exit;
}

echo($action());

function search() {
    global $userModel, $gridSize, $gridUseFastLoad, $actionAuthorizator, $user;

    if($user === NULL) {
        exit;
    }
    
    $currentUser = $user;
    unset($user);

    $page = 1;

    if(isset($_GET['page'])) {
        $page = (int)(htmlspecialchars($_GET['page']));
    }

    $dataSourceCallback = function() use ($gridUseFastLoad, $userModel, $page, $gridSize) {
        if($gridUseFastLoad) {
            $page -= 1;
    
            $firstIdUserOnPage = $userModel->getFirstIdUserOnAGridPage(($page * $gridSize));
    
            return $userModel->getAllUsersFromId($firstIdUserOnPage, $gridSize);
        } else {
            return $userModel->getAllUsers($gridSize);
        }
    };

    $canViewUserProfile = $actionAuthorizator->checkActionRight(UserActionRights::VIEW_USER_PROFILE, null, false);
    $canManageUserRights = $actionAuthorizator->checkActionRight(UserActionRights::MANAGE_USER_RIGHTS, null, false);
    $canDeleteUsers = $actionAuthorizator->checkActionRight(UserActionRights::DELETE_USER, null, false);

    $gb = new GridBuilder();

    $gb->addColumns(['firstname' => 'Firstname', 'lastname' => 'Lastname', 'username' => 'Username', 'email' => 'Email', 'status' => 'Status']);
    $gb->addDataSourceCallback($dataSourceCallback);
    $gb->addOnColumnRender('status', function(User $user) {
        return UserStatus::$texts[$user->getStatus()];
    });
    $gb->addAction(function(User $user) use ($canViewUserProfile) {
        if($canViewUserProfile) {
            return LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showProfile', 'id' => $user->getId()), 'Profile');
        } else {
            return '-';
        }
    });
    $gb->addAction(function(User $user) use ($canManageUserRights) {
        if($canManageUserRights) {
            return LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showUserRights', 'id' => $user->getId(), 'filter' => 'actions'), 'Action rights');
        } else {
            return '-';
        }
    });
    $gb->addAction(function(User $user) use ($canManageUserRights) {
        if($canManageUserRights) {
            return LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showUserRights', 'id' => $user->getId(), 'filter' => 'bulk_actions'), 'Bulk action rights');
        } else {
            return '-';
        }
    });
    $gb->addAction(function(User $user) use ($canManageUserRights) {
        if($canManageUserRights) {
            return LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showUserRights', 'id' => $user->getId(), 'filter' => 'panels'), 'Panel rights');
        } else {
            return '-';
        }
    });
    $gb->addAction(function(User $user) use ($canDeleteUsers, $currentUser) {
        $notDeletableIdUsers = array($currentUser->getId(), AppConfiguration::getIdServiceUser());

        if($canDeleteUsers &&
           !in_array($user->getId(), $notDeletableIdUsers) &&
           $user->getUsername() != 'admin') {
            return LinkBuilder::createAdvLink(array('page' => 'UserModule:Settings:deleteUser', 'id' => $user->getId()), 'Delete');
        } else {
            return '-';
        }
    });

    echo $gb->build();
}

?>