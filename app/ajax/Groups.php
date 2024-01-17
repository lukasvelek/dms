<?php

use DMS\Constants\UserActionRights;
use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

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

    $tb = TableBuilder::getTemporaryObject();

    $headers = array(
        'Actions',
        'Name',
        'Code'
    );

    $headerRow = null;

    $groups = [];
    if($gridUseFastLoad) {
        $page -= 1;

        $firstIdGroupOnPage = $groupModel->getFirstIdGroupOnAGridPage(($page * $gridSize));

        $groups = $groupModel->getAllGroupsFromId($firstIdGroupOnPage, $gridSize);
    } else {
        $groups = $groupModel->getAllGroups($gridSize);
    }

    $notDeletableIdGroups = array(1, 2);

    if(empty($groups)) {
        $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
    } else {
        foreach($groups as $group) {
            $actionLinks = [];

            if($actionAuthorizator->checkActionRight(UserActionRights::VIEW_GROUP_USERS, null, false)) {
                $actionLinks[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:showUsers', 'id' => $group->getId()), 'Users');
            } else {
                $actionLinks[] = '-';
            }

            if($actionAuthorizator->checkActionRight(UserActionRights::MANAGE_GROUP_RIGHTS, null, false)) {
                $actionLinks[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Groups:showGroupRights', 'id' => $group->getId()), 'Group rights');
            } else {
                $actionLinks[] = '-';
            }

            if($actionAuthorizator->checkActionRight(UserActionRights::DELETE_GROUP, null, false) &&
               !in_array($group->getId(), $notDeletableIdGroups)) {
                $actionLinks[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:Settings:deleteGroup', 'id' => $group->getId()), 'Delete');
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

            $groupRow = $tb->createRow();

            foreach($actionLinks as $actionLink) {
                $groupRow->addCol($tb->createCol()->setText($actionLink));
            }

            $groupData = array(
                $group->getName() ?? '-',
                $group->getCode() ?? '-'
            );

            foreach($groupData as $gd) {
                $groupRow->addCol($tb->createCol()->setText($gd));
            }

            $tb->addRow($groupRow);
        }
    }

    echo $tb->build();
}

?>