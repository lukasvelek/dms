<?php

namespace DMS\Authenticators;

use DMS\Core\CacheManager;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

/**
 * Authenticator for users. It allows users to login or logout into the application.
 * 
 * @author Lukas Velek
 */
class UserAuthenticator extends AAuthenticator {
    /**
     * Constructor for user authenticator
     * 
     * @param Database $db Database instance
     * @param Logger $logger Logger instance
     */
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    /**
     * Checks if passed user's credentials are valid and the user can log in
     * 
     * @param string $username User's username
     * @param string $password User's password
     * @return int|bool If user's credentials are valid and the user can log in then it returns ID of that user, if not then false is returned
     */
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
                $this->logger->info('Successfully authenticated user #' . $row['id']);
                return $row['id'];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Logs out the current user
     * 
     * @return bool True
     */
    public function logoutCurrentUser() {
        $this->logger->info('Logging out current user');
        
        CacheManager::invalidateAllCache();

        unset($_SESSION['id_current_user']);
        unset($_SESSION['session_end_date']);

        return true;
    }

    /**
     * Checks if all given passwords match
     * 
     * @param array<string> $passwords Passwords to check
     * @return bool True if all passwords match or false if not
     */
    public function checkPasswordMatch(array $passwords) {
        $match = true;

        $last = null;

        foreach($passwords as $password) {
            if($last != $password) {
                $match = false;

                break;
            } else {
                $last = $password;
            }
        }

        return $match;
    }
}

?>