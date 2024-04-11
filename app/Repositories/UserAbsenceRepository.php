<?php

namespace DMS\Repositories;

use DMS\Constants\Metadata\UserAbsenceMetadata;
use DMS\Constants\Metadata\UserSubstitutesMetadata;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\UserAbsenceEntity;
use DMS\Entities\UserSubstituteEntity;
use DMS\Models\UserModel;

class UserAbsenceRepository extends ARepository {
    private UserModel $userModel;

    public function __construct(Database $db, Logger $logger, UserModel $userModel) {
        parent::__construct($db, $logger);

        $this->userModel = $userModel;
    }

    public function createSubstituteForIdUser(int $idUser, int $idSubstitute) {
        $data = [
            UserSubstitutesMetadata::ID_USER => $idUser,
            UserSubstitutesMetadata::ID_SUBSTITUTE => $idSubstitute
        ];

        return $this->userModel->insertSubstitute($data);
    }

    public function editSubstituteForIdUser(int $idUser, int $idSubstitute) {
        return $this->userModel->updateSubstitute($idUser, $idSubstitute);
    }

    public function getSubstituteForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_substitutes')
            ->where(UserSubstitutesMetadata::ID_USER . ' = ?', [$idUser])
            ->execute();

        return $this->createUserSubstituteEntityFromDbRow($qb->fetch());
    }

    public function deleteAbsence(int $id) {
        return $this->userModel->deleteAbsence($id);
    }
    
    public function editAbsence(int $id, string $dateFrom, string $dateTo) {
        $data = [
            UserAbsenceMetadata::DATE_FROM => $dateFrom,
            UserAbsenceMetadata::DATE_TO => $dateTo
        ];

        return $this->userModel->updateAbsence($id, $data);
    }

    public function getAbsenceById(int $id) {
        $row = $this->userModel->getAbsenceById($id);

        return $this->createUserAbsenceEntityFromDbRow($row);
    }

    public function insertAbsence(int $idUser, string $dateFrom, string $dateTo) {
        $data = [
            UserAbsenceMetadata::ID_USER => $idUser,
            UserAbsenceMetadata::DATE_FROM => $dateFrom,
            UserAbsenceMetadata::DATE_TO => $dateTo
        ];

        return $this->userModel->insertAbsence($data);
    }

    public function getAbsenceForIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_absence')
            ->where(UserAbsenceMetadata::ID_USER . ' = ?', [$idUser])
            ->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = $this->createUserAbsenceEntityFromDbRow($row);
        }

        return $entities;
    }

    private function createUserAbsenceEntityFromDbRow($row) {
        if($row === NULL) {
            return null;
        }

        $id = $row[UserAbsenceMetadata::ID];
        $idUser = $row[UserAbsenceMetadata::ID_USER];
        $dateFrom = $row[UserAbsenceMetadata::DATE_FROM];
        $dateTo = $row[UserAbsenceMetadata::DATE_TO];

        return new UserAbsenceEntity($id, $idUser, $dateFrom, $dateTo);
    }

    private function createUserSubstituteEntityFromDbRow($row) {
        if($row === NULL) {
            return null;
        }

        $id = $row[UserSubstitutesMetadata::ID];
        $idUser = $row[UserSubstitutesMetadata::ID_USER];
        $idSubstitute = $row[UserSubstitutesMetadata::ID_SUBSTITUTE];

        return new UserSubstituteEntity($id, $idUser, $idSubstitute);
    }
}

?>