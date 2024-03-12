<?php

namespace DMS\Models;

use DMS\Constants\Metadata\RibbonGroupRightMetadata;
use DMS\Constants\Metadata\RibbonUserRightMetadata;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class RibbonRightsModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getRibbonRightsForAllRibbonsAndIdGroup(int $idGroup) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('ribbon_group_rights')
            ->where(RibbonGroupRightMetadata::ID_GROUP . ' = ?', [$idGroup])
            ->execute();

        return $qb->fetchAll();
    }

    public function getRibbonRightsForAllRibbonsAndIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('ribbon_user_rights')
            ->where(RibbonUserRightMetadata::ID_USER . ' = ?', [$idUser])
            ->execute();

        return $qb->fetchAll();
    }

    public function getAllEditableRibbonsForIdGroups(array $idGroups) {
        $qb = $this->qb(__METHOD__);

        $qb ->select([RibbonGroupRightMetadata::ID_RIBBON])
            ->from('ribbon_group_rights')
            ->where($qb->getColumnInValues(RibbonGroupRightMetadata::ID_GROUP, $idGroups))
            ->andWhere(RibbonGroupRightMetadata::CAN_EDIT . ' = 1')
            ->execute();

        return $qb->fetchAll();
    }

    public function getAllDeletableRibbonsForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select([RibbonUserRightMetadata::ID_RIBBON])
            ->from('ribbon_user_rights')
            ->where(RibbonUserRightMetadata::ID_USER . ' = ?', [$idUser])
            ->andWhere(RibbonUserRightMetadata::CAN_DELETE . ' = 1')
            ->execute();

        return $qb->fetchAll();
    }

    public function getAllEditableRibbonsForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select([RibbonUserRightMetadata::ID_RIBBON])
            ->from('ribbon_user_rights')
            ->where(RibbonUserRightMetadata::ID_USER . ' = ?', [$idUser])
            ->andWhere(RibbonUserRightMetadata::CAN_EDIT . ' = 1')
            ->execute();

        return $qb->fetchAll();
    }

    public function deleteAllRibonRightsForIdUser(int $idUser) {
        return $this->deleteByCol(RibbonUserRightMetadata::ID_USER, $idUser, 'ribbon_user_rights');
    }

    public function deleteAllRibbonRightsForIdGroup(int $idGroup) {
        return $this->deleteByCol(RibbonGroupRightMetadata::ID_GROUP, $idGroup, 'ribbon_group_rights');
    }

    public function deleteAllGroupRibbonRights(int $idRibbon) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('ribbon_group_rights')
            ->where(RibbonGroupRightMetadata::ID_RIBBON . ' = ?', [$idRibbon])
            ->execute();

        return $qb->fetchAll();
    }

    public function deleteAllUserRibbonRights(int $idRibbon) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('ribbon_user_rights')
            ->where(RibbonUserRightMetadata::ID_RIBBON . ' = ?', [$idRibbon])
            ->execute();

        return $qb->fetchAll();
    }

    public function getRibbonRightsForIdGroup(int $idRibbon, int $idGroup) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('ribbon_group_rights')
            ->where(RibbonGroupRightMetadata::ID_RIBBON . ' = ?', [$idRibbon])
            ->andWhere(RibbonGroupRightMetadata::ID_GROUP . ' = ?', [$idGroup])
            ->execute();

        return $qb->fetchAll();
    }

    public function getRibbonRightsForIdUser(int $idRibbon, int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('ribbon_user_rights')
            ->where(RibbonUserRightMetadata::ID_RIBBON . ' = ?', [$idRibbon])
            ->andWhere(RibbonUserRightMetadata::ID_USER . ' = ?', [$idUser])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateGroupRights(int $idRibbon, int $idGroup, array $rights) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select(['*'])
                  ->from('ribbon_group_rights')
                  ->where(RibbonGroupRightMetadata::ID_RIBBON . ' = ?', [$idRibbon])
                  ->andWhere(RibbonGroupRightMetadata::ID_GROUP . ' = ?', [$idGroup])
                  ->execute()
                  ->fetch();

        if($row === FALSE || $row === NULL) {
            // insert new

            return $this->insertNewGroupRibbonRight($idRibbon, $idGroup, $rights);
        }

        // update

        $qb = $this->qb(__METHOD__);

        $qb ->update('ribbon_group_rights')
            ->set($rights)
            ->where(RibbonGroupRightMetadata::ID_RIBBON . ' = ?', [$idRibbon])
            ->andWhere(RibbonGroupRightMetadata::ID_GROUP . ' = ?', [$idGroup])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateUserRights(int $idRibbon, int $idUser, array $rights) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select(['*'])
                  ->from('ribbon_user_rights')
                  ->where(RibbonUserRightMetadata::ID_RIBBON . ' = ?', [$idRibbon])
                  ->andWhere(RibbonUserRightMetadata::ID_USER . ' = ?', [$idUser])
                  ->execute()
                  ->fetch();

        if($row === FALSE || $row === NULL) {
            // insert new

            return $this->insertNewUserRibbonRight($idRibbon, $idUser, $rights);
        }

        // update

        $qb = $this->qb(__METHOD__);

        $qb ->update('ribbon_user_rights')
            ->set($rights)
            ->where(RibbonUserRightMetadata::ID_RIBBON . ' = ?', [$idRibbon])
            ->andWhere(RibbonUserRightMetadata::ID_USER . ' = ?', [$idUser])
            ->execute();

        return $qb->fetchAll();
    }

    public function getGroupRibbonRightsForIdRibbon(int $idRibbon) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('ribbon_group_rights')
            ->where(RibbonGroupRightMetadata::ID_RIBBON . ' = ?', [$idRibbon])
            ->execute();

        return $qb->fetchAll();
    }

    public function getUserRibbonRightsForIdRibbon(int $idRibbon) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('ribbon_user_rights')
            ->where(RibbonUserRightMetadata::ID_RIBBON . ' = ?', [$idRibbon])
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
            RibbonUserRightMetadata::ID_RIBBON => $idRibbon,
            RibbonUserRightMetadata::ID_USER => $idUser
        );

        foreach($rights as $key => $value) {
            $data[$key] = $value;
        }

        return $this->insertNew($data, 'ribbon_user_rights');
    }

    public function insertNewGroupRibbonRight(int $idRibbon, int $idGroup, array $rights) {
        $data = array(
            RibbonGroupRightMetadata::ID_RIBBON => $idRibbon,
            RibbonGroupRightMetadata::ID_GROUP => $idGroup
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
            ->where(RibbonUserRightMetadata::ID_RIBBON . ' = ?', [$idRibbon])
            ->andWhere(RibbonUserRightMetadata::ID_USER . ' = ?', [$idUser])
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
            ->where(RibbonGroupRightMetadata::ID_RIBBON . ' = ?', [$idRibbon])
            ->andWhere(RibbonGroupRightMetadata::ID_GROUP . ' = ?', [$idGroup])
            ->execute();

        if($qb->fetch($colname) == '1') {
            return true;
        } else {
            return false;
        }
    }
}

?>