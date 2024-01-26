<?php

use DMS\Constants\UserActionRights;
use DMS\Entities\Group;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;

require_once('Ajax.php');

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
    global $groupModel, $gridSize, $gridUseFastLoad, $actionAuthorizator;

    $page = 1;

    if(isset($_GET['page'])) {
        $page = (int)(htmlspecialchars($_GET['page']));
    }

    $notDeletableIdGroups = array(1, 2);

    $groupCallback = null;
    if($gridUseFastLoad) {
        $page -= 1;

        $firstIdGroupOnPage = $groupModel->getFirstIdGroupOnAGridPage(($page * $gridSize));

        $groupCallback = function() use ($groupModel, $firstIdGroupOnPage, $gridSize) {
            return $groupModel->getAllGroupsFromId($firstIdGroupOnPage, $gridSize);
        };
    } else {
        $groupCallback = function() use ($groupModel, $gridSize) {
            return $groupModel->getAllGroups($gridSize);
        };
    }

    $gb = new GridBuilder();

    $gb->addColumns(['name' => 'Name', 'code' => 'Code']);
    $gb->addAction(function(Group $group) use ($actionAuthorizator) {
        if($actionAuthorizator->checkActionRight(UserActionRights::VIEW_GROUP_USERS, null, false)) {
            return LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:showUsers', 'id' => $group->getId()), 'Users');
        } else {
            return '-';
        }
    });
    $gb->addAction(function(Group $group) use ($actionAuthorizator) {
        if($actionAuthorizator->checkActionRight(UserActionRights::MANAGE_GROUP_RIGHTS, null, false)) {
            return LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:showGroupRights', 'id' => $group->getId(), 'filter' => 'actions'), 'Action rights');
        } else {
            return '-';
        }
    });
    $gb->addAction(function(Group $group) use ($actionAuthorizator) {
        if($actionAuthorizator->checkActionRight(UserActionRights::MANAGE_GROUP_RIGHTS, null, false)) {
            return LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:showGroupRights', 'id' => $group->getId(), 'filter' => 'bulk_actions'), 'Bulk action rights');
        } else {
            return '-';
        }
    });
    $gb->addAction(function(Group $group) use ($actionAuthorizator) {
        if($actionAuthorizator->checkActionRight(UserActionRights::MANAGE_GROUP_RIGHTS, null, false)) {
            return LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:showGroupRights', 'id' => $group->getId(), 'filter' => 'panels'), 'Panel rights');
        } else {
            return '-';
        }
    });
    $gb->addAction(function(Group $group) use ($actionAuthorizator, $notDeletableIdGroups) {
        if($actionAuthorizator->checkActionRight(UserActionRights::DELETE_GROUP, null, false) &&
           !in_array($group->getId(), $notDeletableIdGroups)) {
            return LinkBuilder::createAdvLink(array('page' => 'UserModule:Settings:deleteGroup', 'id' => $group->getId()), 'Delete');
        } else {
            return '-';
        }
    });
    $gb->addDataSourceCallback($groupCallback);

    echo $gb->build();
}

?>