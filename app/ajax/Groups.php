<?php

use DMS\Constants\UserActionRights;
use DMS\Core\AppConfiguration;
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
    global $groupModel, $gridSize, $actionAuthorizator;

    $page = 1;

    if(isset($_GET['page'])) {
        $page = (int)(htmlspecialchars($_GET['page']));
    }

    $notDeletableIdGroups = array(1, 2);

    $returnArray = [];

    $dataSourceCallback = function() use ($groupModel, $gridSize, $page) {
        $page -= 1;
        return $groupModel->getGroupsWithOffset($gridSize, ($page * $gridSize));
    };

    $canViewGroupUsers = $actionAuthorizator->checkActionRight(UserActionRights::VIEW_GROUP_USERS, null, false);
    $canManagerGroupUsers = $actionAuthorizator->checkActionRight(UserActionRights::MANAGE_GROUP_RIGHTS, null, false);
    $canDeleteGroups = $actionAuthorizator->checkActionRight(UserActionRights::DELETE_GROUP, null, false);

    $gb = new GridBuilder();

    $gb->addColumns(['name' => 'Name', 'code' => 'Code']);
    $gb->addAction(function(Group $group) use ($canViewGroupUsers) {
        if($canViewGroupUsers) {
            return LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:showUsers', 'id' => $group->getId()), 'Users');
        } else {
            return '-';
        }
    });
    $gb->addAction(function(Group $group) use ($canManagerGroupUsers) {
        if($canManagerGroupUsers) {
            return LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:showGroupRights', 'id' => $group->getId(), 'filter' => 'actions'), 'Action rights');
        } else {
            return '-';
        }
    });
    $gb->addAction(function(Group $group) use ($canManagerGroupUsers) {
        if($canManagerGroupUsers) {
            return LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:showGroupRights', 'id' => $group->getId(), 'filter' => 'bulk_actions'), 'Bulk action rights');
        } else {
            return '-';
        }
    });
    $gb->addAction(function(Group $group) use ($canManagerGroupUsers) {
        if($canManagerGroupUsers) {
            return LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:showGroupRights', 'id' => $group->getId(), 'filter' => 'panels'), 'Panel rights');
        } else {
            return '-';
        }
    });
    $gb->addAction(function(Group $group) use ($canDeleteGroups, $notDeletableIdGroups) {
        if($canDeleteGroups &&
           !in_array($group->getId(), $notDeletableIdGroups)) {
            return LinkBuilder::createAdvLink(array('page' => 'UserModule:Settings:deleteGroup', 'id' => $group->getId()), 'Delete');
        } else {
            return '-';
        }
    });
    $gb->addDataSourceCallback($dataSourceCallback);

    $returnArray['grid'] = $gb->build();
    $returnArray['controls'] = _createGridPageControls($page);

    return json_encode($returnArray);
}

function _createGridPageControls(int $page) {
    global $groupModel;
    $groupCount = $groupModel->getGroupCount();

    $pageControl = '';

    $firstPageLink = '<button id="grid-first-page-control-btn" type="button" onclick="loadGroups(\'';
    $previousPageLink = '<button id="grid-first-page-control-btn" type="button" onclick="loadGroups(\'';
    $nextPageLink = '<button id="grid-first-page-control-btn" type="button" onclick="loadGroups(\'';
    $lastPageLink = '<button id="grid-first-page-control-btn" type="button" onclick="loadGroups(\'';

    $pageCheck = $page - 1;

    $firstPageLink .= '1\')"';
    if($page == 1 || $groupCount <= AppConfiguration::getGridSize()) {
        $firstPageLink .= ' hidden';
    }
    $firstPageLink .= '>&lt;&lt;</button>';

    if($page >= 2) {
        $previousPageLink .= ($page - 1) . '\')';
    } else {
        $previousPageLink .= '1\')';
    }
    $previousPageLink .= '"';
    if($page == 1 || $groupCount <= AppConfiguration::getGridSize()) {
        $previousPageLink .= ' hidden';
    }
    $previousPageLink .= '>&lt;</button>';

    $nextPageLink .= ($page + 1) . '\')';
    if($groupCount < ($page * AppConfiguration::getGridSize())) {
        $nextPageLink .= ' hidden';
    }
    $nextPageLink .= '>&gt;</button>';

    $lastPageLink .= ceil($groupCount / AppConfiguration::getGridSize()) . '\')';
    $lastPageLink .= '"';
    if($groupCount <= ($page * AppConfiguration::getGridSize())) {
        $lastPageLink .= ' hidden';
    }
    $lastPageLink .= '>&gt;&gt;</button>';

    $pageControl = 'Total documents: ' . $groupCount . ' | ';
    if($groupCount > AppConfiguration::getGridSize()) {
        if($pageCheck * AppConfiguration::getGridSize() >= $groupCount) {
            $pageControl .= (1 + ($page * AppConfiguration::getGridSize()));
        } else {
            $from = 1 + ($pageCheck * AppConfiguration::getGridSize());
            $to = AppConfiguration::getGridSize() + ($pageCheck * AppConfiguration::getGridSize());

            if($to > $groupCount) {
                $to = $groupCount;
            }

            $pageControl .= $from . '-' . $to;
        }
    } else {
        $pageControl = 'Total documents: ' . $groupCount;
    }
    $pageControl .= ' | ' . $firstPageLink . ' ' . $previousPageLink . ' ' . $nextPageLink . ' ' . $lastPageLink;

    return $pageControl;
}

?>