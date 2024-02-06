<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class RibbonRightsModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function deleteAllRibonRightsForIdUser(int $idUser) {
        return $this->deleteByCol('id_user', $idUser, 'ribbon_user_rights');
    }

    public function deleteAllRibbonRightsForIdGroup(int $idGroup) {
        return $this->deleteByCol('id_group', $idGroup, 'ribbon_group_rights');
    }

    public function deleteAllGroupRibbonRights(int $idRibbon) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('ribbon_group_rights')
            ->where('id_ribbon = ?', [$idRibbon])
            ->execute();

        return $qb->fetchAll();
    }

    public function deleteAllUserRibbonRights(int $idRibbon) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('ribbon_user_rights')
            ->where('id_ribbon = ?', [$idRibbon])
            ->execute();

        return $qb->fetchAll();
    }

    public function getRibbonRightsForIdGroup(int $idRibbon, int $idGroup) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('ribbon_group_rights')
            ->where('id_ribbon = ?', [$idRibbon])
            ->andWhere('id_group = ?', [$idGroup])
            ->execute();

        return $qb->fetchAll();
    }

    public function getRibbonRightsForIdUser(int $idRibbon, int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('ribbon_user_rights')
            ->where('id_ribbon = ?', [$idRibbon])
            ->andWhere('id_user = ?', [$idUser])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateGroupRights(int $idRibbon, int $idGroup, array $rights) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select(['*'])
                  ->from('ribbon_group_rights')
                  ->where('id_ribbon = ?', [$idRibbon])
                  ->andWhere('id_group = ?', [$idGroup])
                  ->execute()
                  ->fetch();

        if($row === FALSE || $row === NULL) {
            // insert new

            return $this->insertNewGroupRibbonRight($idRibbon, $idGroup, $rights);
        }

        // update

        $qb ->update('ribbon_group_rights')
            ->set($rights)
            ->where('id_ribbon = ?', [$idRibbon])
            ->andWhere('id_group = ?', [$idGroup])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateUserRights(int $idRibbon, int $idUser, array $rights) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select(['*'])
                  ->from('ribbon_user_rights')
                  ->where('id_ribbon = ?', [$idRibbon])
                  ->andWhere('id_user = ?', [$idUser])
                  ->execute()
                  ->fetch();

        if($row === FALSE || $row === NULL) {
            // insert new

            return $this->insertNewUserRibbonRight($idRibbon, $idUser, $rights);
        }

        // update

        $qb ->update('ribbon_user_rights')
            ->set($rights)
            ->where('id_ribbon = ?', [$idRibbon])
            ->andWhere('id_user = ?', [$idUser])
            ->execute();

        return $qb->fetchAll();
    }

    public function getGroupRibbonRightsForIdRibbon(int $idRibbon) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('ribbon_group_rights')
            ->where('id_ribbon = ?', [$idRibbon])
            ->execute();

        return $qb->fetchAll();
    }

    public function getUserRibbonRightsForIdRibbon(int $idRibbon) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('ribbon_user_rights')
            ->where('id_ribbon = ?', [$idRibbon])
            ->execute();

        return $qb->fetchAll();
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

        $qb ->select([$colname])
            ->from('ribbon_user_rights')
            ->where('id_ribbon = ?', [$idRibbon])
            ->andWhere('id_user = ?', [$idUser])
            ->execute();

        if($qb->fetch($colname) == '1') {
            return true;
        } else {
            return false;
        }
    }

    public function getRightValueForIdRibbonAndIdGroup(int $idRibbon, int $idGroup, string $colname) {
        $qb = $this->qb(__METHOD__);

        $qb ->select([$colname])
            ->from('ribbon_group_rights')
            ->where('id_ribbon = ?', [$idRibbon])
            ->andWhere('id_group = ?', [$idGroup])
            ->execute();

        if($qb->fetch($colname) == '1') {
            return true;
        } else {
            return false;
        }
    }
}

?>