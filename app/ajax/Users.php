<?php

use DMS\Constants\CacheCategories;
use DMS\Constants\UserActionRights;
use DMS\Constants\UserStatus;
use DMS\Core\AppConfiguration;
use DMS\Core\CacheManager;
use DMS\Entities\User;
use DMS\Exceptions\AException;
use DMS\Exceptions\ValueIsNullException;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;

require_once('Ajax.php');

$ucm = new CacheManager(CacheCategories::USERS, '../../' . AppConfiguration::getLogDir(), '../../' . AppConfiguration::getCacheDir());

$action = null;

if(isset($_GET['action'])) {
    $action = htmlspecialchars($_GET['action']);
} else if(isset($_POST['action'])) {
    $action = htmlspecialchars($_POST['action']);
}

if($action == null) {
    throw new ValueIsNullException('$action');
}

try {
    echo($action());
} catch(AException $e) {
    echo('<b>Exception: </b>' . $e->getMessage() . '<br><b>Stack trace: </b>' . $e->getTraceAsString());
    exit;
}

function search() {
    global $userModel, $gridSize, $actionAuthorizator, $user;

    $returnArray = [];

    if($user === NULL) {
        exit;
    }
    
    $currentUser = $user;
    unset($user);

    $page = 1;

    if(isset($_GET['page'])) {
        $page = (int)(htmlspecialchars($_GET['page']));
    }

    $dataSourceCallback = function() use ($userModel, $page, $gridSize) {
        $page -= 1;
        return $userModel->getUsersWithOffset($gridSize, ($page * $gridSize));
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
            return LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showRibbonRights', 'id' => $user->getId()), 'Ribbon rights');
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

    $returnArray['grid'] = $gb->build();
    $returnArray['controls'] = _createGridPageControls($page);

    return json_encode($returnArray);
}

function _createGridPageControls(int $page) {
    global $userModel;
    $userCount = $userModel->getUserCount();

    $pageControl = '';

    $firstPageLink = '<button id="grid-first-page-control-btn" type="button" onclick="loadUsers(\'';
    $previousPageLink = '<button id="grid-previous-page-control-btn" type="button" onclick="loadUsers(\'';
    $nextPageLink = '<button id="grid-next-page-control-btn" type="button" onclick="loadUsers(\'';
    $lastPageLink = '<button id="grid-last-page-control-btn" type="button" onclick="loadUsers(\'';

    $pageCheck = $page - 1;

    $firstPageLink .= '1\')"';
    $firstPageLink .= '>&lt;&lt;</button>';


    if($page >= 2) {
        $previousPageLink .= ($page - 1) . '\')';
    } else {
        $previousPageLink .= '1\')';
    }
    $previousPageLink .= '"';
    $previousPageLink .= '>&lt;</button>';


    if($page < ceil($userCount / AppConfiguration::getGridSize())) {
        $nextPageLink .= ($page + 1) . '\')';
    } else if($userCount == 0) {
        $nextPageLink .= '1\')';
    } else {
        $nextPageLink .= ceil($userCount / AppConfiguration::getGridSize()) . '\')';
    }
    $nextPageLink .= '"';
    $nextPageLink .= '>&gt;</button>';


    if($userCount == 0) {
        $lastPageLink .= '1\')';
    } else {
        $lastPageLink .= ceil($userCount / AppConfiguration::getGridSize()) . '\')';
    }
    $lastPageLink .= '"';
    $lastPageLink .= '>&gt;&gt;</button>';

    $pageControl = 'Total count: ' . $userCount . ' | ';
    if($userCount > AppConfiguration::getGridSize()) {
        if($pageCheck * AppConfiguration::getGridSize() >= $userCount) {
            $pageControl .= (1 + ($page * AppConfiguration::getGridSize()));
        } else {
            $from = 1 + ($pageCheck * AppConfiguration::getGridSize());
            $to = AppConfiguration::getGridSize() + ($pageCheck * AppConfiguration::getGridSize());

            if($to > $userCount) {
                $to = $userCount;
            }

            $pageControl .= $from . '-' . $to;
        }
    } else {
        $pageControl = 'Total count: ' . $userCount;
    }
    $pageControl .= ' | ' . $firstPageLink . ' ' . $previousPageLink . ' ' . $nextPageLink . ' ' . $lastPageLink;

    return $pageControl;
}

?>