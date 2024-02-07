<?php

namespace DMS\Enums;

use DMS\Models\GroupModel;

class GroupsEnum extends AEnum {
    private GroupModel $groupModel;

    public function __construct(GroupModel $groupModel) {
        parent::__construct('GroupsEnum');
        $this->groupModel = $groupModel;

        $this->loadValues();
    }

    private function loadValues() {
        $groups = $this->groupModel->getAllGroups();

        $this->addValue('null', '-');

        foreach($groups as $group) {
            $this->addValue($group->getId(), $group->getName());
        }
    }
}

?>