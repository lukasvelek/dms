<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\User;

class UserModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
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
                     ->set(array('status' => ':status'))
                     ->where('id=:id')
                     ->setParams(array(':status' => $status, ':id' => $id))
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function updateUserPassword(int $id, string $hashedPassword) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->update('users')
                     ->set(array('password' => ':password', 'date_password_changed' => ':date_password_changed'))
                     ->where('id=:id')
                     ->setParams(array(':password' => $hashedPassword, ':id' => $id, ':date_password_changed' => date('Y-m-d H:i:s')))
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

        $user = User::createUserObjectFromArrayValues($values);

        return $user;
    }
}

?>