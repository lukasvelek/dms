<?php

use DMS\Constants\CacheCategories;
use DMS\Constants\UserActionRights;
use DMS\Constants\UserStatus;
use DMS\Core\AppConfiguration;
use DMS\Core\CacheManager;
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

    $tb = TableBuilder::getTemporaryObject();

    $headers = array(
        'Actions',
        'Firstname',
        'Lastname',
        'Username',
        'Email',
        'Status'
    );

    $headerRow = null;

    $users = [];
    if($gridUseFastLoad) {
        $page -= 1;

        $firstIdUserOnPage = $userModel->getFirstIdUserOnAGridPage(($page * $gridSize));

        $users = $userModel->getAllUsersFromId($firstIdUserOnPage, $gridSize);
    } else {
        $users = $userModel->getAllUsers($gridSize);
    }

    $notDeletableIdUsers = array($currentUser->getId(), AppConfiguration::getIdServiceUser());

    if(empty($users)) {
        $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
    } else {
        $skip = 0;
        $maxSkip = ($page - 1) * $gridSize;

        foreach($users as $user) {
            if(!$gridUseFastLoad) {
                if($skip < $maxSkip) {
                    $skip++;
                    continue;
                }
            }

            $actionLinks = [];

            if($actionAuthorizator->checkActionRight(UserActionRights::VIEW_USER_PROFILE, null, false)) {
                $actionLinks[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showProfile', 'id' => $user->getId()), 'Profile');
            } else {
                $actionLinks[] = '-';
            }

            if($actionAuthorizator->checkActionRight(UserActionRights::MANAGE_USER_RIGHTS, null, false)) {
                $actionLinks[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showUserRights', 'id' => $user->getId(), 'filter' => 'actions'), 'Action rights');
            } else {
                $actionLinks[] = '-';
            }

            if($actionAuthorizator->checkActionRight(UserActionRights::MANAGE_USER_RIGHTS, null, false)) {
                $actionLinks[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showUserRights', 'id' => $user->getId(), 'filter' => 'bulk_actions'), 'Bulk action rights');
            } else {
                $actionLinks[] = '-';
            }

            if($actionAuthorizator->checkActionRight(UserActionRights::MANAGE_USER_RIGHTS, null, false)) {
                $actionLinks[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Users:showUserRights', 'id' => $user->getId(), 'filter' => 'panels'), 'Panel rights');
            } else {
                $actionLinks[] = '-';
            }

            if($actionAuthorizator->checkActionRight(UserActionRights::DELETE_USER, null, false) &&
               !in_array($user->getId(), $notDeletableIdUsers) &&
               $user->getUsername() != 'admin') {
                $actionLinks[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Settings:deleteUser', 'id' => $user->getId()), 'Delete');
            } else {
                $actionLinks[] = '-';
            }

            if(is_null($headerRow)) {
                $row = $tb->createRow();

                foreach($headers as $header) {
                    $col = $tb->createCol()->setText($header)
                                           ->setBold();

                    if($header == 'Actions') {
                        $col->setColspan(count($actionLinks));
                    }

                    $row->addCol($col);
                }

                $headerRow = $row;
                
                $tb->addRow($row);
            }

            $userRow = $tb->createRow();

            foreach($actionLinks as $actionLink) {
                $userRow->addCol($tb->createCol()->setText($actionLink));
            }

            $userData = array(
                $user->getFirstname() ?? '-',
                $user->getLastname() ?? '-',
                $user->getUsername() ?? '-',
                $user->getEmail() ?? '-',
                UserStatus::$texts[$user->getStatus()]
            );

            foreach($userData as $ud) {
                $userRow->addCol($tb->createCol()->setText($ud));
            }

            $tb->addRow($userRow);
        }
    }

    echo $tb->build();
}

?>