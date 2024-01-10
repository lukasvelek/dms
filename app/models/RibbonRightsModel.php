<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class RibbonRightsModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getRibbonRightsForIdGroup(int $idRibbon, int $idGroup) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('ribbon_user_rights')
                  ->where('id_ribbon=:id_ribbon')
                  ->andWhere('id_group=:id_group')
                  ->setParams(array(
                    ':id_ribbon' => $idRibbon,
                    ':id_group' => $idGroup
                  ))
                  ->execute()
                  ->fetchSingle();

        return $row;
    }

    public function getRibbonRightsForIdUser(int $idRibbon, int $idUser) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('ribbon_user_rights')
                  ->where('id_ribbon=:id_ribbon')
                  ->andWhere('id_user=:id_user')
                  ->setParams(array(
                    ':id_ribbon' => $idRibbon,
                    ':id_user' => $idUser
                  ))
                  ->execute()
                  ->fetchSingle();

        return $row;
    }

    public function updateGroupRights(int $idRibbon, int $idGroup, array $rights) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('ribbon_group_rights')
                  ->where('id_ribbon=:id_ribbon')
                  ->andWhere('id_group=:id_group')
                  ->setParams(array(
                    ':id_ribbon' => $idRibbon,
                    ':id_group' => $idGroup
                  ))
                  ->execute()
                  ->fetchSingle();

        if($row === FALSE || $row === NULL) {
            // insert new

            return $this->insertNewGroupRibbonRight($idRibbon, $idGroup, $rights);
        }

        // update

        $qb = $this->updateExistingQb('ribbon_group_rights', $rights);
        $qb ->where('id_ribbon=:id_ribbon')
            ->andWhere('id_group=:id_group')
            ->setParams(array(
                ':id_ribbon' => $idRibbon,
                ':id_group' => $idGroup
            ));

        $result = $qb->execute()->fetch();

        return $result;
    }

    public function updateUserRights(int $idRibbon, int $idUser, array $rights) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('ribbon_user_rights')
                  ->where('id_ribbon=:id_ribbon')
                  ->andWhere('id_user=:id_user')
                  ->setParams(array(
                    ':id_ribbon' => $idRibbon,
                    ':id_user' => $idUser
                  ))
                  ->execute()
                  ->fetchSingle();

        if($row === FALSE || $row === NULL) {
            // insert new

            return $this->insertNewUserRibbonRight($idRibbon, $idUser, $rights);
        }

        // update

        $qb = $this->updateExistingQb('ribbon_user_rights', $rights);
        $qb ->where('id_ribbon=:id_ribbon')
            ->andWhere('id_user=:id_user')
            ->setParams(array(
                ':id_ribbon' => $idRibbon,
                ':id_user' => $idUser
            ));

        $result = $qb->execute()->fetch();

        return $result;
    }

    public function getGroupRibbonRightsForIdRibbon(int $idRibbon) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('ribbon_group_rights')
                   ->where('id_ribbon=:id_ribbon')
                   ->setParam(':id_ribbon', $idRibbon)
                   ->execute()
                   ->fetch();

        return $rows;
    }

    public function getUserRibbonRightsForIdRibbon(int $idRibbon) {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('ribbon_user_rights')
                   ->where('id_ribbon=:id_ribbon')
                   ->setParam(':id_ribbon', $idRibbon)
                   ->execute()
                   ->fetch();

        return $rows;
    }

    public function insertAllGrantedRightsForUser(int $idRibbon, int $idUser) {
        return $this->insertNewUserRibbonRight($idRibbon, $idUser, array(self::VIEW => '1', self::EDIT => '1', self::DELETE => '1'));
    }

    public function insertAllGrantedRightsForGroup(int $idRibbon, int $idGroup) {
        return $this->insertNewGroupRibbonRight($idRibbon, $idGroup, array(self::VIEW => '1', self::EDIT => '1', self::DELETE => '1'));
    }

    public function insertNewUserRibbonRight(int $idRibbon, int $idUser, array $rights) {
        $data = array(
            'id_ribbon' => $idRibbon,
            'id_user' => $idUser
        );

        foreach($rights as $key => $value) {
            $data[$key] = $value;
        }

        return $this->insertNew($data, 'ribbon_user_rights');
    }

    public function insertNewGroupRibbonRight(int $idRibbon, int $idGroup, array $rights) {
        $data = array(
            'id_ribbon' => $idRibbon,
            'id_group' => $idGroup
        );

        foreach($rights as $key => $value) {
            $data[$key] = $value;
        }

        return $this->insertNew($data, 'ribbon_group_rights');
    }

    public function getRightValueForIdRibbonAndIdUser(int $idRibbon, int $idUser, string $colname) {
        $qb = $this->qb(__METHOD__);

        $row = $qb  ->select($colname)
                    ->from('ribbon_user_rights')
                    ->where('id_ribbon=:ribbon')
                    ->andWhere('id_user=:user')
                    ->setParams(array(
                        ':ribbon' => $idRibbon,
                        ':user' => $idUser
                    ))
                    ->execute()
                    ->fetchSingle($colname)
               ;

        return $row ? true : false;
    }

    public function getRightValueForIdRibbonAndIdGroup(int $idRibbon, int $idGroup, string $colname) {
        $qb = $this->qb(__METHOD__);

        $row = $qb  ->select($colname)
                    ->from('ribbon_group_rights')
                    ->where('id_ribbon=:ribbon')
                    ->andWhere('id_group=:group')
                    ->setParams(array(
                        ':ribbon' => $idRibbon,
                        ':group' => $idGroup
                    ))
                    ->execute()
                    ->fetchSingle($colname)
               ;

        return $row ? true : false;
    }
}

?>