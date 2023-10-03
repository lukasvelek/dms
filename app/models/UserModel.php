<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Entities\User;

class UserModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
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
        $values['IsActive'] = $row['is_active'] ? true : false;
        
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