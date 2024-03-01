<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\User;

class UserModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
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

        $qb ->select(['id_author', 'id_officer', 'id_manager'])
            ->from('documents')
            ->execute();

        $docuUsers = [];
        while($row = $qb->fetchAssoc()) {
            if(!in_array($row['id_author'], $docuUsers)) {
                $docuUsers[] = $row['id_author'];
            }
            if(!in_array($row['id_officer'], $docuUsers)) {
                $docuUsers[] = $row['id_officer'];
            }
            if(!in_array($row['id_manager'], $docuUsers)) {
                $docuUsers[] = $row['id_manager'];
            }
        }

        $qb->clean();

        $qb ->select(['*'])
            ->from('users')
            ->where($qb->getColumnInValues('id', $docuUsers))
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
            ->set(['last_login_hash' => $hash])
            ->where('id = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function getLastLoginHashForIdUser(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['last_login_hash'])
            ->from('users')
            ->where('id = ?', [$id])
            ->execute();

        return $qb->fetch('last_login_hash');
    }

    public function deleteConnectionsForIdUser(int $id) {
        return $this->deleteByCol('id_user1', $id, 'user_connections') && $this->deleteByCol('id_user2', $id, 'user_connections');
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
                                        ->where('id_user1 = ?', [$idUser1])
                                        ->andWhere('id_user2 = ?', [$idUser2])
                                    ->rb()
                                    ->or()
                                    ->lb()
                                        ->where('id_user1 = ?', [$idUser2])
                                        ->andWhere('id_user2 = ?', [$idUser1])
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
            ->where($qb->getColumnInValues('id', $ids))
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
            if($row['id_user1'] == $idUser) {
                $idConnectedUsers[] = $row['id_user2'];
            } else if($row['id_user2'] == $idUser) {
                $idConnectedUsers[] = $row['id_user1'];
            }
        }

        return $idConnectedUsers;
    }

    public function getUserConnectionsByIdUser(int $idUser) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_connections')
            ->where('id_user1 = ?', [$idUser])
            ->orWhere('id_user2 = ?', [$idUser])
            ->execute();

        return $qb->fetchAll();
    }

    public function getUserByUsername(string $username) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('users')
            ->where('username = ?', [$username])
            ->limit(1)
            ->execute();

        return $this->getUserObjectFromDbRow($qb->fetch());
    }

    public function getUserCount() {
        return $this->getRowCount('users');
    }

    public function deletePasswordResetHashByIdHash(string $hash) {
        return $this->deleteByCol('hash', $hash, 'password_reset_hashes');
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
            ->setNull(['password'])
            ->set(['date_updated' => date(Database::DB_DATE_FORMAT)])
            ->where('id = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateUser(int $id, array $data) {
        $qb = $this->qb(__METHOD__);

        if(!array_key_exists('date_updated', $data)) {
            $data['date_updated'] = date(Database::DB_DATE_FORMAT);
        }

        $qb ->update('users')
            ->set($data)
            ->where('id = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateUserStatus(int $id, int $status) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('users')
            ->set(['status' => $status, 'date_updated' => date(Database::DB_DATE_FORMAT)])
            ->where('id = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateUserPassword(int $id, string $hashedPassword) {
        $qb = $this->qb(__METHOD__);

        $date = date(Database::DB_DATE_FORMAT);

        $qb ->update('users')
            ->set(array('password' => $hashedPassword, 'date_password_changed' => $date, 'date_updated' => $date))
            ->where('id = ?', [$id])
            ->execute();

        return $qb->fetchAll();
    }

    public function getUserForFirstLoginByUsername(string $username) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('users')
            ->where('username = ?', [$username])
            ->execute();

        return $this->getUserObjectFromDbRow($qb->fetch());
    }

    public function getLastInsertedUser() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('users')
            ->orderBy('id', 'DESC')
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
            ->where('id = ?', [$id])
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

        $values['id'] = $row['id'];
        $values['dateCreated'] = $row['date_created'];
        $values['Firstname'] = $row['firstname'];
        $values['Lastname'] = $row['lastname'];
        $values['Username'] = $row['username'];
        $values['Status'] = $row['status'];
        $values['DatePasswordChanged'] = $row['date_password_changed'];
        $values['PasswordChangeStatus'] = $row['password_change_status'];
        
        if(isset($row['email'])) {
            $values['Email'] = $row['email'];    
        }
        if(isset($row['address_street'])) {
            $values['AdreesStreet'] = $row['address_street'];
        }
        if(isset($row['address_house_number'])) {
            $values['AddressHouseNumber'] = $row['address_house_number'];
        }
        if(isset($row['address_city'])) {
            $values['AddressCity'] = $row['address_city'];
        }
        if(isset($row['address_zip_code'])) {
            $values['AddressZipCode'] = $row['address_zip_code'];
        }
        if(isset($row['address_country'])) {
            $values['AddressCountry'] = $row['address_country'];
        }
        if(isset($row['default_user_page_url'])) {
            $values['DefaultUserPageUrl'] = $row['default_user_page_url'];
        }
        if(isset($row['default_user_datetime_format'])) {
            $values['DefaultUserDateTimeFormat'] = $row['default_user_datetime_format'];
        }

        $user = User::createUserObjectFromArrayValues($values);

        return $user;
    }
}

?>