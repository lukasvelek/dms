<?php

namespace DMS\Authenticators;

use DMS\Constants\CacheCategories;
use DMS\Core\CacheManager;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class UserAuthenticator extends AAuthenticator {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function authUser(string $username, string $password) {
        
        $qb = $this->qb(__METHOD__);
        $row = $qb->select('id', 'username', 'password')
                  ->from('users')
                  ->where('username=:username')
                  ->setParam(':username', $username)
                  ->execute()
                  ->fetchSingle();

        if($row !== FALSE && !is_null($row)) {
            if(password_verify($password, $row['password'])) {
                return $row['id'];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function logoutCurrentUser() {
        CacheManager::invalidateAllCache();

        unset($_SESSION['id_current_user']);
        unset($_SESSION['session_end_date']);

        return true;
    }
}

?>