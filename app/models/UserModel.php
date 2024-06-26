<?php

namespace DMS\Models;

use DMS\Constants\Metadata\DocumentMetadata;
use DMS\Constants\Metadata\UserAbsenceMetadata;
use DMS\Constants\Metadata\UserConnectionMetadata;
use DMS\Constants\Metadata\UserMetadata;
use DMS\Constants\Metadata\UserPasswordResetHashMetadata;
use DMS\Constants\Metadata\UserSubstitutesMetadata;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\User;
use DMS\Entities\UserLoginBlockEntity;

class UserModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function updateSubstitute(int $idUser, int $idSubstitute) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('user_substitutes')
            ->set([UserSubstitutesMetadata::ID_SUBSTITUTE => $idSubstitute])
            ->where(UserSubstitutesMetadata::ID_USER . ' = ?', [$idUser])
            ->execute();

        return $qb->fetch();
    }

    public function insertSubstitute(array $data) {
        return $this->insertNew($data, 'user_substitutes');
    }

    public function deleteAbsence(int $id) {
        return $this->deleteById($id, 'user_absence');
    }

    public function updateAbsence(int $id, array $data) {
        return $this->updateExisting('user_absence', $id, $data);
    }

    public function getAbsenceById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_absence')
            ->where(UserAbsenceMetadata::ID . ' = ?', [$id])
            ->execute();

        return $qb->fetch();
    }

    public function insertAbsence(array $data) {
        return $this->insertNew($data, 'user_absence');
    }

    public function getActiveBlockedIdUsers() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_login_blocks')
            ->where('is_active = 1')
            ->execute();

        $idUsers = [];
        while($row = $qb->fetchAssoc()) {
            if(strtotime($row['date_from']) > time()) {
                continue;
            }

            if(isset($row['date_to'])) {
                if(strtotime($row['date_to']) < time() && $row['is_active'] == '1') {
                    continue;
                }
            }

            $idUsers[] = $row['id_user'];
        }

        return $idUsers;
    }

    public function getActiveUserLoginBlocks() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_login_blocks')
            ->where('is_active = 1')
            ->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = $this->createUserLoginBlockEntityFromDbRow($row);
        }

        return $entities;
    }

    public function getActiveUserLoginBlockByIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_login_blocks')
            ->where('id_user = ?', [$idUser])
            ->andWhere('is_active = 1')
            ->execute();

        return $this->createUserLoginBlockEntityFromDbRow($qb->fetch());
    }

    public function updateUserLoginBlock(int $id, array $data) {
        return $this->updateExisting('user_login_blocks', $id, $data);
    }

    public function insertUserLoginBlock(array $data) {
        return $this->insertNew($data, 'user_login_blocks');
    }

    public function composeStandardLoginAttemptsQuery() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_logins');

        return $qb;
    }

    public function insertUserLoginAttempt(array $data) {
        return $this->insertNew($data, 'user_logins');
    }

    public function composeStandardUserQuery(array $selects = ['*']) {
        $qb = $this->qb(__METHOD__);

        $qb ->select($selects)
            ->from('users');

        return $qb;
    }

    public function getUsersWithOffset(int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('users')
            ->limit($limit)
            ->offset($offset)
            ->execute();

        $users = [];
        while($row = $qb->fetchAssoc()) {
            $users[] = $this->getUserObjectFromDbRow($row);
        }
    
        return $users;
    }

    public function getAllUsersPresentInDocuments() {
        $qb = $this->qb(__METHOD__);

        $qb ->select([DocumentMetadata::ID_AUTHOR, DocumentMetadata::ID_OFFICER, DocumentMetadata::ID_MANAGER])
            ->from('documents')
            ->execute();

        $docuUsers = [];
        while($row = $qb->fetchAssoc()) {
            if($row[DocumentMetadata::ID_AUTHOR] !== NULL && !in_array($row[DocumentMetadata::ID_AUTHOR], $docuUsers)) {
                $docuUsers[] = $row[DocumentMetadata::ID_AUTHOR];
            }
            if($row[DocumentMetadata::ID_OFFICER] !== NULL && !in_array($row[DocumentMetadata::ID_OFFICER], $docuUsers)) {
                $docuUsers[] = $row[DocumentMetadata::ID_OFFICER];
            }
            if($row[DocumentMetadata::ID_MANAGER] !== NULL && !in_array($row[DocumentMetadata::ID_MANAGER], $docuUsers)) {
                $docuUsers[] = $row[DocumentMetadata::ID_MANAGER];
            }
        }

        $qb->clean();

        $qb ->select(['*'])
            ->from('users')
            ->where($qb->getColumnInValues(UserMetadata::ID, $docuUsers))
            ->execute();

        $users = [];
        while($row = $qb->fetchAssoc()) {
            $users[] = $this->getUserObjectFromDbRow($row);
        }

        return $users;
    }

    public function insertLastLoginHashForIdUser(int $id, string $hash) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('users')
            ->set([UserMetadata::LAST_LOGIN_HASH => $hash])
            ->where(UserMetadata::ID . ' = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function getLastLoginHashForIdUser(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select([UserMetadata::LAST_LOGIN_HASH])
            ->from('users')
            ->where(UserMetadata::ID . ' = ?', [$id])
            ->execute();

        return $qb->fetch('last_login_hash');
    }

    public function deleteConnectionsForIdUser(int $id) {
        return $this->deleteByCol(UserConnectionMetadata::ID_USER1, $id, 'user_connections') && $this->deleteByCol(UserConnectionMetadata::ID_USER2, $id, 'user_connections');
    }

    public function deleteUserById(int $id) {
        $this->deleteById($id, 'users');
    }

    public function removeConnectionForTwoUsers(int $idUser1, int $idUser2) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('user_connections')
            ->where('WHERE ' . $this->xb()
                                    ->lb()
                                        ->where(UserConnectionMetadata::ID_USER1 . ' = ?', [$idUser1])
                                        ->andWhere(UserConnectionMetadata::ID_USER2 . ' = ?', [$idUser2])
                                    ->rb()
                                    ->or()
                                    ->lb()
                                        ->where(UserConnectionMetadata::ID_USER1 . ' = ?', [$idUser2])
                                        ->andWhere(UserConnectionMetadata::ID_USER2 . ' = ?', [$idUser1])
                                    ->rb()
                                    ->build())
            ->execute();

        return $qb->fetchAll();
    }

    public function insertNewUserConnect(array $data) {
        return $this->insertNew($data, 'user_connections');
    }

    public function getConnectedUsersForIdUser(int $idUser) {
        $ids = $this->getIdConnectedUsersForIdUser($idUser);

        if($ids === NULL || empty($ids)) {
            return [];
        }

        $users = [];
        $qb = $this->qb(__METHOD__);
        $qb ->select(['*'])
            ->from('users')
            ->where($qb->getColumnInValues(UserMetadata::ID, $ids))
            ->execute();

        while($row = $qb->fetchAssoc()) {
            $users[] = $this->getUserObjectFromDbRow($row);
        }

        return $users;
    }

    public function getIdConnectedUsersForIdUser(int $idUser) {
        $rows = $this->getUserConnectionsByIdUser($idUser);

        if($rows === NULL || $rows === FALSE) {
            return [];
        }

        $idConnectedUsers = [];
        foreach($rows as $row) {
            if($row[UserConnectionMetadata::ID_USER1] == $idUser) {
                $idConnectedUsers[] = $row[UserConnectionMetadata::ID_USER2];
            } else if($row[UserConnectionMetadata::ID_USER2] == $idUser) {
                $idConnectedUsers[] = $row[UserConnectionMetadata::ID_USER1];
            }
        }

        return $idConnectedUsers;
    }

    public function getUserConnectionsByIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_connections')
            ->where(UserConnectionMetadata::ID_USER1 . ' = ?', [$idUser])
            ->orWhere(UserConnectionMetadata::ID_USER2 . ' = ?', [$idUser])
            ->execute();

        return $qb->fetchAll();
    }

    public function getUserByUsername(string $username) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('users')
            ->where(UserMetadata::USERNAME . ' = ?', [$username])
            ->limit(1)
            ->execute();

        return $this->getUserObjectFromDbRow($qb->fetch());
    }

    public function getUserCount() {
        return $this->getRowCount('users');
    }

    public function deletePasswordResetHashByIdHash(string $hash) {
        return $this->deleteByCol(UserPasswordResetHashMetadata::HASH, $hash, 'password_reset_hashes');
    }

    public function insertPasswordResetHash(array $data) {
        return $this->insertNew($data, 'password_reset_hashes');
    }

    public function getAllUsersMeetingCondition(string $condition) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('users')
            ->where($condition)
            ->execute();

        $users = [];
        while($row = $qb->fetchAssoc()) {
            $users[] = $this->getUserObjectFromDbRow($row);
        }

        return $users;
    }

    public function nullUserPassword(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('users')
            ->setNull([UserMetadata::PASSWORD])
            ->set([UserMetadata::DATE_UPDATED => date(Database::DB_DATE_FORMAT)])
            ->where(UserMetadata::ID . ' = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateUser(int $id, array $data) {
        $qb = $this->qb(__METHOD__);

        if(!array_key_exists(UserMetadata::DATE_UPDATED, $data)) {
            $data[UserMetadata::DATE_UPDATED] = date(Database::DB_DATE_FORMAT);
        }

        $qb ->update('users')
            ->set($data)
            ->where(UserMetadata::ID . ' = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateUserStatus(int $id, int $status) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('users')
            ->set([UserMetadata::STATUS => $status, UserMetadata::DATE_UPDATED => date(Database::DB_DATE_FORMAT)])
            ->where(UserMetadata::ID . ' = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateUserPassword(int $id, string $hashedPassword) {
        $qb = $this->qb(__METHOD__);

        $date = date(Database::DB_DATE_FORMAT);

        $qb ->update('users')
            ->set(array(UserMetadata::PASSWORD => $hashedPassword, UserMetadata::DATE_PASSWORD_CHANGED => $date, UserMetadata::DATE_UPDATED => $date))
            ->where(UserMetadata::ID . ' = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function getUserForFirstLoginByUsername(string $username) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('users')
            ->where(UserMetadata::USERNAME . ' = ?', [$username])
            ->execute();

        return $this->getUserObjectFromDbRow($qb->fetch());
    }

    public function getLastInsertedUser() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('users')
            ->orderBy(UserMetadata::ID, 'DESC')
            ->limit(1)
            ->execute();

        return $this->getUserObjectFromDbRow($qb->fetch());
    }

    public function insertUser(array $data) {
        return $this->insertNew($data, 'users');
    }

    public function getUserById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('users')
            ->where(UserMetadata::ID . ' = ?', [$id])
            ->execute();

        return $this->getUserObjectFromDbRow($qb->fetch());
    }

    public function getAllUsers(int $limit = 0) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('users');

        if($limit > 0) {
            $qb->limit($limit);
        }

        $qb->execute();
        
        $users = [];
        while($row = $qb->fetchAssoc()) {
            $users[] = $this->getUserObjectFromDbRow($row);
        }

        return $users;
    }

    private function getUserObjectFromDbRow($row) {
        if($row === NULL) {
            return null;
        }

        $values = array();

        $values['id'] = $row[UserMetadata::ID];
        $values['dateCreated'] = $row[UserMetadata::DATE_CREATED];
        $values['Firstname'] = $row[UserMetadata::FIRSTNAME];
        $values['Lastname'] = $row[UserMetadata::LASTNAME];
        $values['Username'] = $row[UserMetadata::USERNAME];
        $values['Status'] = $row[UserMetadata::STATUS];
        $values['DatePasswordChanged'] = $row[UserMetadata::DATE_PASSWORD_CHANGED];
        $values['PasswordChangeStatus'] = $row[UserMetadata::PASSWORD_CHANGE_STATUS];
        
        if(isset($row[UserMetadata::EMAIL])) {
            $values['Email'] = $row[UserMetadata::EMAIL];    
        }
        if(isset($row[UserMetadata::ADDRESS_STREET])) {
            $values['AdreesStreet'] = $row[UserMetadata::ADDRESS_STREET];
        }
        if(isset($row[UserMetadata::ADDRESS_HOUSE_NUMBER])) {
            $values['AddressHouseNumber'] = $row[UserMetadata::ADDRESS_HOUSE_NUMBER];
        }
        if(isset($row[UserMetadata::ADDRESS_CITY])) {
            $values['AddressCity'] = $row[UserMetadata::ADDRESS_CITY];
        }
        if(isset($row[UserMetadata::ADDRESS_ZIP_CODE])) {
            $values['AddressZipCode'] = $row[UserMetadata::ADDRESS_ZIP_CODE];
        }
        if(isset($row[UserMetadata::ADDRESS_COUNTRY])) {
            $values['AddressCountry'] = $row[UserMetadata::ADDRESS_COUNTRY];
        }
        if(isset($row[UserMetadata::DEFAULT_USER_PAGE_URL])) {
            $values['DefaultUserPageUrl'] = $row[UserMetadata::DEFAULT_USER_PAGE_URL];
        }
        if(isset($row[UserMetadata::DEFAULT_USER_DATETIME_FORMAT])) {
            $values['DefaultUserDateTimeFormat'] = $row[UserMetadata::DEFAULT_USER_DATETIME_FORMAT];
        }

        $user = User::createUserObjectFromArrayValues($values);

        return $user;
    }
    
    private function createUserLoginBlockEntityFromDbRow($row) {
        if($row === NULL) {
            return null;
        }

        $id = $row['id'];
        $idUser = $row['id_user'];
        $idAuthor = $row['id_author'];
        $description = $row['description'];
        $dateFrom = explode(' ', $row['date_from'])[0];
        $dateTo = null;
        $dateCreated = $row['date_created'];
        $isActive = $row['is_active'];

        if($isActive == '1') {
            $isActive = true;
        } else {
            $isActive = false;
        }

        if(isset($row['date_to'])) {
            $dateTo = explode(' ', $row['date_to'])[0];
        }

        return new UserLoginBlockEntity($id, $dateCreated, $idUser, $idAuthor, $description, $dateFrom, $dateTo, $isActive);
    }
}

?>