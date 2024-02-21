<?php

namespace DMS\Enums;

use DMS\Models\GroupModel;

/**
 * Groups external enum
 * 
 * @author Lukas Velek
 */
class GroupsEnum extends AEnum {
    private GroupModel $groupModel;

    /**
     * Class constructor
     * 
     * @param GroupModel $groupModel GroupModel instance
     */
    public function __construct(GroupModel $groupModel) {
        parent::__construct('GroupsEnum');
        $this->groupModel = $groupModel;

        $this->loadValues();
    }

    /**
     * Loads enum values
     */
    private function loadValues() {
        $groups = $this->groupModel->getAllGroups();

        $this->addValue('null', '-');

        foreach($groups as $group) {
            $this->addValue($group->getId(), $group->getName());
        }
    }
}

?>