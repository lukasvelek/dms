<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\User;

class UserModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }
    
    public function removeConnectionForTwoUsers(int $idUser1, int $idUser2) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->delete()
                     ->from('user_connections')
                     ->explicit(' WHERE ')
                     ->leftBracket()
                     ->where('id_user1=:id_user1', false, false)
                     ->andWhere('id_user2=:id_user2')
                     ->rightBracket()
                     ->explicit(' OR ')
                     ->leftBracket()
                     ->where('id_user1=:id_user2', false, false)
                     ->andWhere('id_user2=:id_user1')
                     ->rightBracket()
                     ->setParams(array(
                        ':id_user1' => $idUser1,
                        ':id_user2' => $idUser2
                     ))
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function insertNewUserConnect(array $data) {
        return $this->insertNew($data, 'user_connections');
    }

    public function getConnectedUsersForIdUser(int $idUser) {
        $ids = $this->getIdConnectedUsersForIdUser($idUser);

        if($ids === NULL) {
            return [];
        }

        $users = [];
        foreach($ids as $id) {
            $users[] = $this->getUserById($id);
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

        $rows = $qb->select('*')
                   ->from('user_connections')
                   ->where('id_user1=:id_user')
                   ->orWhere('id_user2=:id_user')
                   ->setParam(':id_user', $idUser)
                   ->execute()
                   ->fetch();

        return $rows;
    }

    public function getUserByUsername(string $username) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('users')
                  ->where('username=:username')
                  ->setParam(':username', $username)
                  ->limit('1')
                  ->execute()
                  ->fetchSingle();

        return $this->getUserObjectFromDbRow($row);
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

        $rows = $qb->select('*')
                   ->from('users')
                   ->explicit($condition)
                   ->execute()
                   ->fetch();

        $users = [];
        foreach($rows as $row) {
            $users[] = $this->getUserObjectFromDbRow($row);
        }

        return $users;
    }

    public function nullUserPassword(int $id) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->update('users')
                     ->setNull(array('password'))
                     ->explicit(', `date_updated`=\'' . date(Database::DB_DATE_FORMAT) . '\'')
                     ->where('id=:id')
                     ->setParam(':id', $id)
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function updateUser(int $id, array $data) {
        $qb = $this->qb(__METHOD__);

        $values = [];
        $params = [];

        foreach($data as $k => $v) {
            $values[$k] = ':' . $k;
            $params[':' . $k] = $v;
        }

        if(!array_key_exists('date_updated', $values)) {
            $values['date_updated'] = ':date_updated';
            $params[':date_updated'] = date(Database::DB_DATE_FORMAT);
        }

        $result = $qb->update('users')
                     ->set($values)
                     ->where('id=:id')
                     ->setParams($params)
                     ->setParam(':id', $id)
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function updateUserStatus(int $id, int $status) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->update('users')
                     ->set(array('status' => ':status', 'date_updated' => ':date'))
                     ->where('id=:id')
                     ->setParams(array(':status' => $status, ':id' => $id, ':date' => date(Database::DB_DATE_FORMAT)))
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function updateUserPassword(int $id, string $hashedPassword) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->update('users')
                     ->set(array('password' => ':password', 'date_password_changed' => ':date_password_changed', 'date_updated' => ':date'))
                     ->where('id=:id')
                     ->setParams(array(':password' => $hashedPassword, ':id' => $id, ':date_password_changed' => date('Y-m-d H:i:s'), ':date' => date(Database::DB_DATE_FORMAT)))
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function getUserForFirstLoginByUsername(string $username) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('users')
                  ->where('username=:username')
                  ->setParam(':username', $username)
                  ->execute()
                  ->fetchSingle();

        return $this->getUserObjectFromDbRow($row);
    }

    public function getLastInsertedUser() {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('users')
                  ->orderBy('id', 'DESC')
                  ->limit('1')
                  ->execute()
                  ->fetchSingle();

        return $this->getUserObjectFromDbRow($row);
    }

    public function insertUser(array $data) {
        return $this->insertNew($data, 'users');
    }

    public function getUserById(int $id) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('users')
                  ->where('id=:id')
                  ->setParam(':id', $id)
                  ->execute()
                  ->fetchSingle();

        return $this->getUserObjectFromDbRow($row);
    }

    public function getAllUsers() {
        $qb = $this->qb(__METHOD__);

        $rows = $qb->select('*')
                   ->from('users')
                   ->execute()
                   ->fetch();
        
        $users = [];
        foreach($rows as $row) {
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